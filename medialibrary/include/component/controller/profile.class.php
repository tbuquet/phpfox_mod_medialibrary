<?php
defined('PHPFOX') or exit('NO DICE!');

/**
* Controller class for medialibrary profile page
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/
class Medialibrary_Component_Controller_Profile extends Phpfox_Component
{
	/**
	* Load the required information for the page profile of "medialibrary"
	*/
	public function process()
	{
		$this->setParam('bIsProfile', true);
		$aUser = $this->getParam('aUser');
		$iUserId = (int)$aUser['user_id'];

		//Security
		if (!Phpfox::getService('user.privacy')->hasAccess($iUserId, 'profile.view_profile'))
			return Phpfox::getLib('module')->setController('profile.private');			
		
		if (!Phpfox::getService('user.privacy')->hasAccess($iUserId, 'medialibrary.display_on_profile'))
			return;
		
		//Get type
		$sType = $this->request()->get('req3');
		if(!in_array($sType, Phpfox::getService('medialibrary')->getAllowedTypes()))
			$sType = 'movie';
		
		//Get category safely (to clean)
		$aCategories = Phpfox::getService('medialibrary')->getUserCategories($iUserId, $sType);
		$aFlatCategories = array();
		foreach($aCategories as $aCategory)
		{
			$aFlatCategories[] = $aCategory['category_id'];
		}
		
		//Figuring out the current category. A category "divers" is the default category, used when the user hasnt setted any category yet.
		$iCategoryId = $this->request()->get('req4');
		$bIsCategory = true;
		if($iCategoryId == 'divers')
			$iCategoryId = 0;
		else if (strpos($iCategoryId,'-') !== false) {
			$bIsCategory = false;
			$iCategoryId = (int)str_replace('p-', '', $iCategoryId);
		}
		else if(!is_numeric($iCategoryId) || !in_array($iCategoryId, $aFlatCategories))
		{
			if(count($aCategories) > 0)
				$iCategoryId = (int)$aCategories[0]['category_id'];
			else
				$iCategoryId = 0;
		}
		else
			$iCategoryId = (int)$iCategoryId;
		
			
		//Get items
		if($bIsCategory)
		{
			//Category
			$aItems = Phpfox::getService('medialibrary')->getEntriesFromUser($iUserId, $iCategoryId, $sType, 0);
			$iPlatformId = 0;
			$sPlatformJs = '';
			
			$aCategory = array('name' => '');
			foreach($aCategories as $value)
			{
				if($value['category_id'] == $iCategoryId)
					$aCategory = $value;
			}
		}
		else
		{
			//Plateform
			$aItems = Phpfox::getService('medialibrary.game')->getEntriesFromUserPlatform($iUserId, $iCategoryId, 0);
			$iPlatformId = $iCategoryId;
			$sPlatformJs = 'var platformId = "'.$iPlatformId.'";';
			$iCategoryId = -1;
			$aCategory = array('name' => '', 'category_id' => $iCategoryId);
			$aPlatforms = Phpfox::getService('medialibrary.game')->getGamePlatformCountsPerUser($iUserId);
			foreach($aPlatforms as $aPlatform)
			{
				if($aPlatform['platform_id'] == $iPlatformId)
					$aCategory['name'] = $aPlatform['name'];
			}
		}
		
		$aSource = Phpfox::getService('medialibrary')->getSource($sType);
		
		//Set Template
		$sHeaderAdmin = '';
		if($iUserId == Phpfox::getUserId() && $bIsCategory)
			$sHeaderAdmin = 'var bMediaAdmin = true;';
		else
			$sHeaderAdmin = 'var bMediaAdmin = false;';
		
		$aHeaders = array(
			'medialibrary.css' => 'module_medialibrary',
			'medialibrary.js' => 'module_medialibrary',
			'<script type="text/javascript">'.$sHeaderAdmin.';var dragaction = false;var searchType = "'.$sType.'";'.$sPlatformJs.'var categoryId = "'.$iCategoryId.'";var elementsLoaded='.Phpfox::getService('medialibrary')->getNumberMaxPerGetQuery().';var categoriesMax='.Phpfox::getService('medialibrary')->getNumberMaxCategories().';var mediaLock=false;var iUserId = '.$iUserId.';</script>'
			);
		$this->template()
		->setPhrase(array('medialibrary.popup_platforms', 'medialibrary.new_category_name', 'medialibrary.test_string_minimum','medialibrary.categories_max','medialibrary.confirm_leave_window','medialibrary.confirm_delete_category', 'medialibrary.exists', 'medialibrary.too_many_updates', 'medialibrary.test_string_maximum'))
		->setHeader($aHeaders)
		->assign(array(
				'aItems' => $aItems,
				'aSource' => $aSource,
				'aCurrentCategory' => $aCategory,
				'sType' => $sType,
				'bHasAccess' => true,
				'iCurrentCategory' => $iCategoryId,
				'iCurrentPlatform' => $iPlatformId,
				'bIsCategory' => $bIsCategory,
				'iUserId' => $iUserId,
				'iNbrCategories' => count($aCategories) - 1,
				'iNbrMaxCategories' => Phpfox::getService('medialibrary')->getNumberMaxCategories()
			));
	}
	
	/**
	 * Garbage collector.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('photo.component_controller_profile_clean')) ? eval($sPlugin) : false);
	}
}

?>