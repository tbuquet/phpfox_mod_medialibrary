<?php
defined('PHPFOX') or exit('NO DICE!');

/**
* Class to handle the server side of the Ajax calls
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/
class Medialibrary_Component_Ajax_Ajax extends Phpfox_Ajax
{
	/**
	* Research media given a specific query
	*
	* @param t	title requested
	* @param q	query
	*/
	public function getMedias(){
		if(Phpfox::getService('medialibrary')->CanUseTheAPI('get', 3))
		{
			$sType = strtolower($this->get('t'));
			if(in_array($sType, Phpfox::getService('medialibrary')->getAllowedTypes()))
			{
				$sSearchString = $this->get('q');
				
				//Check size category
				if(mb_strlen($sSearchString, 'UTF-8') > 32 && mb_strlen($sSearchString, 'UTF-8') <= 2)
					return;
				
				$aOutput = Phpfox::getService('medialibrary.'.$sType)->getData($sSearchString);	
				$this->call(json_encode($aOutput));
			}
		}
		else
		{
			$this->call('setTimeout(function(){$( "#selectMedia" ).autocomplete( "search", "'.$this->get('q').'" );},2000);');
		}
	}
	
	/**
	* Request the full information about a specific media
	*
	* @param id	id of the media
	* @param u	id of the user
	*/
	public function loadMedia()
	{
		$iMediaId = (int)$this->get('id');
		$uUserId = (int)$this->get('u');
		
		Phpfox::getBlock('medialibrary.mediaview', array(
				'iMediaId' => $iMediaId,
				'iCurrentUserId' => $uUserId
			)
		);
	}

	/**
	* Get the platforms associated with a game for a specific user
	*
	* @param id	id of the media (game)
	*/
	public function loadGameSettings(){
		$iGameId = (int)$this->get('id');
		$aPlatforms = Phpfox::getService('medialibrary.game')->loadGamePlatforms($iGameId);	

		if(count($aPlatforms) > 0)
		{
			$sGameName = $aPlatforms[0]['title'];
			$sGameId = $aPlatforms[0]['media_id'];
			
			Phpfox::getLib('template')->assign(array('aPlatforms' => $aPlatforms, 'sGameName' => $sGameName, 'sGameId' => $sGameId))->getTemplate('medialibrary.block.platforminfo');
		}
	}
	
	/**
	* Save the platforms associated with a game for a specific user
	*
	* @param id	id of the media (game)
	* @param d	serialized array of the list of platforms available for the game
	*/
	public function saveGameSettings()
	{
		$iGameId = (int)$this->get('id');
		$aPlatforms = Phpfox::getService('medialibrary.game')->loadGamePlatforms($iGameId);	
		
		if(count($aPlatforms) > 0 || count($aPlatforms) <= 20)
		{
			$aInputs = json_decode($this->get('d'));
			if(!is_array($aInputs))
				return;
			
			//Verif input
			foreach($aInputs as $aInput)
			{
				if(!isset($aInput->p) || !isset($aInput->a) || !is_numeric($aInput->p) || mb_strlen($aInput->a, 'UTF-8') > 30 || !Phpfox::getService('medialibrary.game')->isPlatformExists($aInput->p))
					return;
			}
			Phpfox::getService('medialibrary.game')->saveGamePlatforms($iGameId, $aInputs);
			$aPlatforms = Phpfox::getService('medialibrary.game')->getGamePlatformCountsPerUser(Phpfox::getUserId());
			$aUser = Phpfox::getService('user')->getUser(Phpfox::getUserId());
			Phpfox::getLib('template')->assign(array('aPlatforms' => $aPlatforms, 'bIsVisible' => true, 'bReload' => true, 'iCurrentPlatform' => -1, 'aUser' => $aUser, 'sType' => 'game'))->getTemplate('medialibrary.block.platforms');	
			$this->call('$("#media_plat").html("' . $this->getContent() . '");');
		}
	}
	
	/**
	* Get saved media entries from a user by type
	*
	* @param t	type of the media
	* @param u	user ID
	* @param c	category ID
	* @param s	offset in which the query needs to start (pagination)
	*/
	public function getEntriesFromType()
	{
		$sType = strtolower($this->get('t'));
		$iUserId = (int)$this->get('u');
		if(in_array($sType, Phpfox::getService('medialibrary')->getAllowedTypes()))
		{
			$iCategoryId = (int)$this->get('c');
			$iStart = (int)$this->get('s');
			$aOutput = Phpfox::getService('medialibrary')->getEntriesFromUser($iUserId, $iCategoryId, $sType, $iStart);
			$iMaxQuery = Phpfox::getService('medialibrary')->getNumberMaxPerGetQuery();
			$removeStr = '';
			if(count($aOutput) < $iMaxQuery)
				$removeStr = '$("#loadMoreMedias").remove();';

			Phpfox::getLib('template')->assign(array('aItems' => $aOutput, 'iUserId' => $iUserId, 'aSource' => Phpfox::getService('medialibrary')->getSource($sType)))->getTemplate('medialibrary.block.item-entry');	
			$this->call('$("#like_content").append("' . $this->getContent() . '").highlightFade();elementsLoaded+='.$iMaxQuery.';resetItemsActions();'.$removeStr);
		}
	}
	
	/**
	* Get saved media entries from a user by platform
	*
	* @param t	type of the media
	* @param u	user ID
	* @param s	offset in which the query needs to start (pagination)
	*/
	public function getEntriesFromUserPlatform()
	{
		$iUserId = (int)$this->get('u');
		$iPlatformId = (int)$this->get('p');
		$iStart = (int)$this->get('s');
		$aOutput = Phpfox::getService('medialibrary.game')->getEntriesFromUserPlatform($iUserId, $iPlatformId, $iStart);
		$iMaxQuery = Phpfox::getService('medialibrary')->getNumberMaxPerGetQuery();
		$removeStr = '';
		if(count($aOutput) < $iMaxQuery)
			$removeStr = '$("#loadMoreMedias").remove();';
		Phpfox::getLib('template')->assign(array('aItems' => $aOutput, 'iUserId' => $iUserId, 'aSource' => Phpfox::getService('medialibrary')->getSource('game')))->getTemplate('medialibrary.block.item-entry');	
		$this->call('$("#like_content").append("' . $this->getContent() . '").highlightFade();elementsLoaded+='.$iMaxQuery.';resetItemsActions();'.$removeStr);
	}
	
	/**
	* Add a media as soon as it's selected by a user. Note that the user ID is not a parameter to avoid security issues.
	*
	* @param t	type of the media
	* @param id	media source ID
	* @param c	category ID, if selected by the user
	*/
	public function addMedia()
	{
		if(Phpfox::getService('medialibrary')->CanUseTheAPI('add', 3))
		{
			$sType = strtolower($this->get('t'));
			if(in_array($sType, Phpfox::getService('medialibrary')->getAllowedTypes()))
			{
				$sId = $this->get('id');
				$cId = (int)$this->get('c');
				$aOutput = Phpfox::getService('medialibrary')->addData($sId, $sType, $cId);
				if(!isset($aOutput['exists']))
				{
					$gameAddJS = '';
					if($sType == 'game')
						$gameAddJS = 'tb_show(oTranslations[\'medialibrary.popup_platforms\'], $.ajaxBox(\'medialibrary.loadGameSettings\', \'id='.$aOutput['media_id'].'\'));';
					Phpfox::getLib('template')->assign(array('sType' => $sType, 'bIsCategory' => true, 'aItem' => $aOutput, 'iUserId' => Phpfox::getUserId(), 'aSource' => Phpfox::getService('medialibrary')->getSource($sType)))->getTemplate('medialibrary.block.item-entry-each');	
					$this->call('$("#like_content").prepend("<li class=\"mediathek_item_holder\" id=\"js_item_id_'.$aOutput['media_id'].'\" ref=\"'.$aOutput['media_id'].'\">' . $this->getContent() . '</li>").highlightFade();elementsLoaded+=1;waitingOperation(false);resetItemsActions();$("#selectMedia").val("");addCategoryNumber();modifyMediaNumber("'.$sType.'", 1);'.$gameAddJS);
				}
				else
					$this->call('alert(oTranslations[\'medialibrary.exists\']);waitingOperation(false);');
			}
		}
		else
		{
			$this->call('setTimeout(function(){$.ajaxCall(\'medialibrary.addMedia\', \'id='.$this->get('id').'&t='.$this->get('t').'\');},2000);');
		}
	}
	
	/**
	* Set the boolean "like" to true or false for a specific user, depending of its current stage
	*
	* @param t	type of the media
	* @param id	media ID
	*/
	public function switchLikeMedia()
	{
		$sType = strtolower($this->get('t'));
		if(in_array($sType, Phpfox::getService('medialibrary')->getAllowedTypes()))
		{
			$sId = $this->get('id');
			$bLiked = Phpfox::getService('medialibrary')->switchLikeMedia($sId, $sType);
			if($bLiked)
				$this->call('$("#mediathek_like_'.$sId.'").text("'.Phpfox::getPhrase('medialibrary.unlike').'");');
			else
				$this->call('$("#mediathek_like_'.$sId.'").text("'.Phpfox::getPhrase('medialibrary.like').'");');
		}
	}
	
	/**
	* Update the order of the media list for a specific user.
	*
	* @param ids	array of ids to reorder, in the new order
	* @param c		category ID, if selected by the user
	*/
	public function updateOrderMedias()
	{
		if(Phpfox::getService('medialibrary')->CanUseTheAPI('update', 5))
		{
			$aIds = json_decode($this->get('ids'));
			$iCategoryId = (int)($this->get('c'));
			if(count($aIds) > 0)
				Phpfox::getService('medialibrary')->updateOrderMedias($aIds, $iCategoryId);
			$this->call('$(".like_status").fadeOut("slow");$(window).unbind(\'beforeunload\');$(".like_status .updateMediaWaiting").hide();');
		}
		else
		{
			$this->call('alert(oTranslations[\'medialibrary.too_many_updates\']);$(".like_status .updateMediaWaiting").hide();');
		}
	}
	
	/**
	* Update the order of the media categories for a specific type
	*
	* @param ids	array of ids to reorder, in the new order
	* @param t		type of the media
	*/
	public function updateOrderCategories()
	{
		if(Phpfox::getService('medialibrary')->CanUseTheAPI('update', 5))
		{
			$aIds = json_decode($this->get('ids'));
			$sType = $this->get('t');
			if(in_array($sType, Phpfox::getService('medialibrary')->getAllowedTypes()))
			{
				if(count($aIds) > 0)
					Phpfox::getService('medialibrary')->updateOrderCategories($aIds, $sType);
				$this->call('$("#media_cat_save_categories").fadeOut("slow");$(window).unbind(\'beforeunload\');$("#media_cat_save_categories .updateMediaWaiting").hide();');
			}
		}
		else
		{
			$this->call('alert(oTranslations[\'medialibrary.too_many_updates\']);$("#media_cat_save_categories .updateMediaWaiting").hide();');
		}
	}
	
	/**
	* Change the category of a media for a specific user and assign it to another category
	*
	* @param id	media ID
	* @param c	new category of the media
	* @param t	type of the media
	*/
	public function sendMediaToCategory()
	{
		$iMediaId = (int)$this->get('id');
		$iCategoryId = (int)$this->get('c');
		$sType = $this->get('t');
		if(in_array($sType, Phpfox::getService('medialibrary')->getAllowedTypes()))
		{
			Phpfox::getService('medialibrary')->sendMediaToCategory($iMediaId, $iCategoryId, $sType);
			$this->call('$("#js_item_id_'.$iMediaId.'").remove();elementsLoaded-=1;reloadCategoryNumbers('.$iCategoryId.');');
		}
	}
	
	/**
	* Add a new category for a specific user
	*
	* @param c	new category
	* @param t	type of the media
	*/
	public function addUserCategory()
	{
		$sCategory = $this->get('c');
		$sType = $this->get('t');
		if(in_array($sType, Phpfox::getService('medialibrary')->getAllowedTypes()))
		{
			//Check size category
			if(mb_strlen($sCategory, 'UTF-8') > 16 && mb_strlen($sCategory, 'UTF-8') <= 2)
				return;
			
			$iCurrentUserId = Phpfox::getUserId();
			
			//Check number categories
			$aCategories = Phpfox::getService('medialibrary')->getUserCategories($iCurrentUserId, $sType);
			if(count($aCategories) > Phpfox::getService('medialibrary')->getNumberMaxCategories())
				return;
				
			$aCategory = Phpfox::getService('medialibrary')->addUserCategory($sCategory, $sType);
			$aUser = Phpfox::getService('user')->getUser(Phpfox::getUserId());
			$this->call('$("#media_cat").prepend("<li id=\"js_media_category_'.$aCategory['category_id'].'\" class=\"media_draggable ui-droppable\" ref=\"'.$aCategory['category_id'].'\"><a href=\"/'.$aUser['user_name'].'/medialibrary/'.$sType.'/'.$aCategory['category_id'].'\"><span id=\"js_media_category_name_'.$aCategory['category_id'].'\">'.str_replace('"','\"',$aCategory['name']).'</span> <span id=\"js_media_category_number_'.$aCategory['category_id'].'\" nbr=\"0\" class=\"media_nb\">(0)</span></a><span class=\"media_category_delete\"><small><a href=\"javascript:;\" onclick=\"deleteCategory('.$aCategory['category_id'].')\">'.Phpfox::getPhrase('medialibrary.delete_category').'</a></small></span></li>").highlightFade();$("#nbrCategories").text(parseInt($("#nbrCategories").text())+1);reloadDroppable();');
		}
	}
	
	/**
	* Rename a category for a specific user
	*
	* @param i	category to rename
	* @param c	new name for the category
	* @param t	type of the media
	*/
	public function renameUserCategory()
	{
		$iCategoryId = (int)$this->get('i');
		$sCategoryName = $this->get('c');
		$sType = $this->get('t');
		if(in_array($sType, Phpfox::getService('medialibrary')->getAllowedTypes()))
		{
			//Check size category
			if(mb_strlen($sCategoryName, 'UTF-8') > 16 && mb_strlen($sCategoryName, 'UTF-8') <= 2)
				return;
			
			$iCurrentUserId = Phpfox::getUserId();
				
			$sNewCategoryName = Phpfox::getService('medialibrary')->renameUserCategory($sCategoryName, $iCategoryId, $sType);
			$aUser = Phpfox::getService('user')->getUser(Phpfox::getUserId());
			$this->call('$("#mediaCatTitle").text("'.str_replace('"','\"',$sNewCategoryName).'").highlightFade();$("#js_media_category_name_'.$iCategoryId.'").text("'.str_replace('"','\"',$sNewCategoryName).'").highlightFade()');
		}
	}
	
	/**
	* Delete a category for a specific user
	*
	* @param c	id of the category to delete
	* @param t	type of the media
	*/
	public function deleteUserCategory()
	{
		$iCategoryId = (int)$this->get('c');
		$sType = $this->get('t');
		if(in_array($sType, Phpfox::getService('medialibrary')->getAllowedTypes()))
		{
			Phpfox::getService('medialibrary')->deleteUserCategory($iCategoryId, $sType);
			$this->call('$("#js_media_category_'.$iCategoryId.'").remove();$("#nbrCategories").text(parseInt($("#nbrCategories").text())-1)');
		}
	}
	
	/**
	* Delete the media from the list of a specific user. Note that it will keep the media in the medialibrary table, it will just unassign it from a user
	*
	* @param id	media ID
	* @param t	type of the media
	*/
	public function deleteMedia()
	{
		$sId = $this->get('id');
		$sType = $this->get('t');
		if(in_array($sType, Phpfox::getService('medialibrary')->getAllowedTypes()))
		{
			Phpfox::getService('medialibrary')->deleteMedia($sId, $sType);
			if($sType == 'game')
			{
				$aPlatforms = Phpfox::getService('medialibrary.game')->getGamePlatformCountsPerUser(Phpfox::getUserId());
				$aUser = Phpfox::getService('user')->getUser(Phpfox::getUserId());
				Phpfox::getLib('template')->assign(array('aPlatforms' => $aPlatforms, 'bIsVisible' => true, 'bReload' => true, 'iCurrentPlatform' => -1, 'aUser' => $aUser, 'sType' => 'game'))->getTemplate('medialibrary.block.platforms');	
				$this->call('$("#js_item_id_'.$sId.'").remove();elementsLoaded-=1;removeCategoryNumber();modifyMediaNumber("'.$sType.'", -1);$("#media_plat").html("' . $this->getContent() . '")');
			}
			else
			{
				$this->call('$("#js_item_id_'.$sId.'").remove();elementsLoaded-=1;removeCategoryNumber();modifyMediaNumber("'.$sType.'", -1);');
			}
		}
	}
}

?>
