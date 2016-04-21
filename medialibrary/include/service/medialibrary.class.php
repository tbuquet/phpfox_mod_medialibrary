<?php
defined('PHPFOX') or exit('NO DICE!');

/**
* Main Service Class to handle all the sql and file operations.
* Contains all the common functions
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/
class Medialibrary_Service_Medialibrary extends Phpfox_Service 
{
	/**
	 * Class constructor
	 */
	public function __construct()
	{
	}
	
	/**
	 * Save the picture associated with a media
	 *
	 * @param sourceurl 		$sSourceUrl			URL of the picture to save
	 * @param destinationpath 	$sDestinationPath	Path to the file to save
	 * @param mediaid 			$iMediaId			Media ID
	 */
	public function saveImage($sSourceUrl, $sDestinationPath, $iMediaId)
	{
		$sPictureTemp = $sDestinationPath . $iMediaId .'_temp.jpg';
		$sPictureFinal = $sDestinationPath . $iMediaId .'.jpg';
		$sPictureFinalThumb = $sDestinationPath . $iMediaId .'_thumb.jpg';
	
		$aDimensions = Phpfox::getService('medialibrary')->getPictureDimensions();
	
		$oCh = curl_init($sSourceUrl);
		$oFp = fopen($sPictureTemp, 'wb');
		curl_setopt($oCh, CURLOPT_FILE, $oFp);
		curl_setopt($oCh, CURLOPT_HEADER, 0);
		curl_exec($oCh);
		curl_close($oCh);
		fclose($oFp);
		
		Phpfox::getLib('image')->createThumbnail($sPictureTemp, $sPictureFinalThumb, $aDimensions[0], $aDimensions[1], false);
		Phpfox::getLib('image')->createThumbnail($sPictureTemp, $sPictureFinal, $aDimensions[2], $aDimensions[3], false);
		Phpfox::getLib('file')->unlink($sPictureTemp);
	}
	
	/**
	 * Get the genre ID from the genre name, insert it if it doesnt exist (cached)
	 *
	 * @param genrename 		$sGenreName			genre name
	 *
	 * @return $iId		genre ID
	 */
	public function getGenreIdFromName($sGenreName)
	{
		$sCacheId = $this->cache()->set('medialibrary.genres');
	
		if (!($aRows = $this->cache()->get($sCacheId)))
		{
			$aRows = $this->database()->select('g.*')
				->from(Phpfox::getT('medialibrary_genres'), 'g')		
				->execute('getSlaveRows');
			
			$this->cache()->save($sCacheId, $aRows);
		}
		
		//GET existing
		if(is_array($aRows) && count($aRows) > 0)
		{
			foreach($aRows as $aRow)
			{
				if($aRow['name'] == $sGenreName)
					return (int)$aRow['genre_id'];
			}
		}
		
		//INSERT
		$iId = $this->database()->insert(Phpfox::getT("medialibrary_genres"), array('name' => $sGenreName));
		$this->cache()->remove('medialibrary.genres', 'substr');
		
		return $iId;
	}
		
	/**
	 * Get the type ID from the type name, insert it if it doesnt exist (cached)
	 *
	 * @param genrename 		$sGenreName			type name
	 *
	 * @return $iId		type ID
	 */
	public function getTypeIdFromName($sTypeName)
	{
		$sTypeName = strtolower($sTypeName);
	
		$sCacheId = $this->cache()->set('medialibrary.dbtypes');
	
		if (!($aRows = $this->cache()->get($sCacheId)))
		{
			$aRows = $this->database()->select('t.*')
				->from(Phpfox::getT('medialibrary_types'), 't')		
				->execute('getSlaveRows');
			
			$this->cache()->save($sCacheId, $aRows);
		}
		
		//GET existing
		if(is_array($aRows) && count($aRows) > 0)
		{
			foreach($aRows as $aRow)
			{
				if($aRow['name'] == $sTypeName)
					return (int)$aRow['type_id'];
			}
		}
		
		//INSERT
		$iId = $this->database()->insert(Phpfox::getT("medialibrary_types"), array('name' => $sTypeName));
		$this->cache()->remove('medialibrary.dbtypes', 'substr');
		
		return $iId;
	}
	
	/**
	 * Get all the specific user categories of a specific type (cached)
	 *
	 * @param userid 		$iUserId			User ID
	 * @param typename 		$sType				Type Name
	 *
	 * @return $aRows 	Array of all the categories for a specific type
	 */
	public function getUserCategories($iUserId, $sType)
	{
		if(Phpfox::getService('user.privacy')->hasAccess($iUserId, 'medialibrary.display_on_profile'))
		{
			$iTypeId = Phpfox::getService('medialibrary')->getTypeIdFromName($sType);
			$sCacheId = $this->cache()->set('medialibrary.categories_'.(int)$iUserId.'_'.(int)$iTypeId);
		
			if (!($aRows = $this->cache()->get($sCacheId)))
			{
				$aCounts = $this->database()->select('u.category_id, count(u.user_id) as count')
					->from(Phpfox::getT('medialibrary_users'), 'u')
					->join(Phpfox::getT('medialibrary'), 'm', 'm.media_id = u.media_id')
					->where('m.type_id = ' . (int)$iTypeId . ' AND u.user_id = ' . (int)$iUserId)
					->group('u.category_id')
					->execute('getSlaveRows');	
					
				$aRows = $this->database()->select('c.*')
					->from(Phpfox::getT('medialibrary_users_categories'), 'c')	
					->where('c.type_id = ' . (int)$iTypeId . ' AND c.user_id = ' . (int)$iUserId)
					->order('c.time_stamp DESC, c.order')
					->execute('getSlaveRows');	
					
				foreach($aRows as $iKey => $aRow)
				{
					foreach($aCounts as $aCount)
					{
						if($aRow['category_id'] == $aCount['category_id'])
						{
							$aRows[$iKey]['count'] = $aCount['count'];
							break;
						}
					}
					if(!isset($aRows[$iKey]['count']))
						$aRows[$iKey]['count'] = 0;
				}
				
				$categoryNull = array();
				$categoryNull['category_id'] = 0;
				$categoryNull['name'] = Phpfox::getPhrase('medialibrary.category_divers');
				$categoryNull['count'] = 0;
				foreach($aCounts as $aCount)
				{
					if($aCount['category_id'] == 0)
					{
						$categoryNull['count'] = $aCount['count'];
						break;
					}
				}
				$aRows[] = $categoryNull;
					
				$this->cache()->save($sCacheId, $aRows);
			}
			
			
			if(!is_array($aRows) || count($aRows) == 0 )
				return array();
			return $aRows;
		}
		return array();
	}
	
	/*
	 * Get a total number of all the medias linked to a user (cached)
	 *
	 * @param userid 		$iUserId			User ID
	 *
	 * @return $aRows 	Array containing the property "count"
	 */
	public function getMediaCounts($iUserId)
	{
		if(Phpfox::getService('user.privacy')->hasAccess($iUserId, 'medialibrary.display_on_profile'))
		{
			$sCacheId = $this->cache()->set('medialibrary.medias_count_'.(int)$iUserId);
		
			if (!($aRows = $this->cache()->get($sCacheId)))
			{
				$aRows = $this->database()->select('t.*, count(m.type_id) as count')
					->from(Phpfox::getT('medialibrary_users'), 'u')
					->join(Phpfox::getT('medialibrary'), 'm', 'm.media_id = u.media_id')
					->join(Phpfox::getT('medialibrary_types'), 't', 'm.type_id = t.type_id')
					->where('u.user_id = ' . (int)$iUserId)
					->group('t.type_id')
					->execute('getSlaveRows');	
				$this->cache()->save($sCacheId, $aRows);
			}
			
			
			if(!is_array($aRows) || count($aRows) == 0 )
				return array();
			return $aRows;
		}
		return array();
	}
	
	/**
	 * Add a user category, the user id is not a parameter for security reasons
	 *
	 * @param category 		$sCategory			Category name
	 * @param typename 			$sType			Type name
	 *
	 * @return $aParams 	Array containing the properties of the new category
	 */
	public function addUserCategory($sCategory, $sType)
	{
		$iTypeId = Phpfox::getService('medialibrary')->getTypeIdFromName($sType);
		
		$aParams = array(
					'user_id' => Phpfox::getUserId(),
					'type_id' => (int)$iTypeId,
					'name' => Phpfox::getLib('parse.input')->clean($sCategory),
					'time_stamp' => PHPFOX_TIME
		);
		
		$iCategoryId = $this->database()->insert(Phpfox::getT("medialibrary_users_categories"), $aParams);
		$this->cache()->remove('medialibrary.categories_'.Phpfox::getUserId().'_'.(int)$iTypeId, 'substr');
		$aParams['category_id'] = $iCategoryId;
		return $aParams;
	}
	
	/**
	 * Rename a category for a specific user
	 *
	 * @param categoryname 		$sCategoryName			Category new name
	 * @param categoryid 		$iCategoryId			Category ID
	 * @param typeid 			$sType					Type Name
	 *
	 * @return $sCategoryName 	New category name
	 */
	public function renameUserCategory($sCategoryName, $iCategoryId, $sType)
	{
		$iTypeId = Phpfox::getService('medialibrary')->getTypeIdFromName($sType);
		$sCategoryName = Phpfox::getLib('parse.input')->clean($sCategoryName);
		$this->database()->update(Phpfox::getT('medialibrary_users_categories'), array('time_stamp' => PHPFOX_TIME, 'name' => $sCategoryName), 'category_id = ' . (int) $iCategoryId . ' AND user_id = ' . Phpfox::getUserId());
		$this->cache()->remove('medialibrary.categories_'.Phpfox::getUserId().'_'.(int)$iTypeId, 'substr');
		return $sCategoryName;
	}
	
	/**
	 * Get a complete list of medias from a user, regardless of the type in order to have faster access to compare who likes/own what. (cached)
	 *
	 * @param userid 		$iUserId			User ID
	 *
	 * @return $aRows 	An array of all the media IDs linked to a user
	 */
	public function getFullListOfMediaFromUser($iUserId)
	{
		$sCacheId = $this->cache()->set('medialibrary.medias_full_'.(int)$iUserId);
		
		if (!($aRows = $this->cache()->get($sCacheId)))
		{
			$aTempRows = $this->database()->select('u.media_id')
				->from(Phpfox::getT('medialibrary_users'), 'u')
				->where('u.user_id = '. (int)$iUserId)
				->execute('getSlaveRows');
			
			$aRows = array();
			foreach($aTempRows as $aTempRow)
				$aRows[] = (int)$aTempRow['media_id'];
				
			$this->cache()->save($sCacheId, $aRows);
		}
		
		if(!is_array($aRows) || count($aRows) == 0 )
				return array();
		return $aRows;
	}
	
	/**
	 * Method that checks if a media is already liked by the current user
	 *
	 * @param mediaid 		$iMediaId			Media ID
	 *
	 * @return true if already liked, otherwise false
	 */
	public function isMediaLikedByMe($iMediaId)
	{
		$aMedias = Phpfox::getService('medialibrary')->getFullListOfMediaFromUser(Phpfox::getUserId());
		
		return in_array($iMediaId, $aMedias);
	}
	
	/**
	 * Get a list of a given user medias
	 *
	 * @param userid 		$iUserId		User ID
	 * @param categoryid 	$iCategoryId	Platform ID
	 * @param typename 		$sType			Type name
	 * @param offset	 	$iOffset		Offset to start the query
	 *
	 * @return an array of the medias
	 */
	public function getEntriesFromUser($iUserId, $iCategoryId, $sType, $iOffset)
	{
		if(Phpfox::getService('user.privacy')->hasAccess($iUserId, 'medialibrary.display_on_profile'))
		{
			$iTypeId = Phpfox::getService('medialibrary')->getTypeIdFromName($sType);
			$aRows = $this->database()->select('f.media_id, f.title, f.original_title, f.original_id')
			->from(Phpfox::getT('medialibrary'), 'f')
			->join(Phpfox::getT('medialibrary_users'), 'u', 'u.media_id = f.media_id')
			->where('f.type_id = \'' . (int)$iTypeId . '\' AND u.user_id = '. (int)$iUserId . ' AND u.category_id = '. (int)$iCategoryId)
			->order('u.time_stamp DESC, u.order')
			->limit($iOffset,Phpfox::getService('medialibrary')->getNumberMaxPerGetQuery())
			->execute('getSlaveRows');
			
			$sLanguage = Phpfox::getService('medialibrary')->getLanguage();
			$sTitleField = 'title';
			if($sLanguage != 'fr-FR')
				$sTitleField = 'original_title';
				
			$iMeId = Phpfox::getUserId();
			foreach($aRows as $iKey => $aRow)
			{
				//$aRows[$iKey]['link'] = sprintf($aRow['source_url'], $aRow['original_id']);
				$aRows[$iKey]['title'] = $aRows[$iKey][$sTitleField];
				$aRows[$iKey]['image_url'] = $aRow['original_id'].'_thumb.jpg';
				$aRows[$iKey]['server_id'] = Phpfox::getLib('request')->getServer('PHPFOX_SERVER_ID');
				if($iMeId != $iUserId && Phpfox::getService('medialibrary')->isMediaLikedByMe($aRows[$iKey]['media_id']))
					$aRows[$iKey]['like_me'] = true;
				else
					$aRows[$iKey]['like_me'] = false;
			}
			return $aRows;
		}
		return array();
	}
	
	/**
	 * Get information about a specific media
	 *
	 * @param mediaid 		$iMediaId		Media ID
	 *
	 * @return $aRow an array of properties for the specific media
	 */
	public function getEntry($iMediaId)
	{
		$aRow = $this->database()->select('m.*, t.name as type')
		->from(Phpfox::getT('medialibrary'), 'm')
		->join(Phpfox::getT('medialibrary_types'), 't', 'm.type_id = t.type_id')
		->where('m.media_id = '.(int)$iMediaId)
		->execute('getSlaveRow');
		
		if(is_array($aRow) && count($aRow) > 0)
		{
			$iMeId = Phpfox::getUserId();
			$sType = $aRow['type'];
			$aSource = Phpfox::getService('medialibrary')->getSource($sType);
			$sLanguage = Phpfox::getService('medialibrary')->getLanguage();
			$sTitleField = 'title';
			if($sLanguage != 'fr-FR')
				$sTitleField = 'original_title';
		
			$aGenres = $this->database()->select('g.name')
			->from(Phpfox::getT('medialibrary_genres_medias'), 'gm')
			->join(Phpfox::getT('medialibrary_genres'), 'g', 'g.genre_id = gm.genre_id')
			->where('gm.media_id = '.(int)$iMediaId)
			->execute('getSlaveRows');
			$sGenres = '';
			if(is_array($aGenres) && count($aGenres) > 0)
			{
				$aOutputGenres = array();
				foreach($aGenres as $aGenre)
				{
					$aOutputGenres[] = $aGenre['name'];
				}
				$sGenres = implode(", ", $aOutputGenres);
			}
		
			
			if($sType == 'game')
			{
				$aPlatforms = $this->database()->select('p.name')
				->from(Phpfox::getT('medialibrary_games_platforms_medias'), 'pm')
				->join(Phpfox::getT('medialibrary_games_platforms'), 'p', 'p.platform_id = pm.platform_id')
				->where('pm.media_id = '.(int)$iMediaId)
				->execute('getSlaveRows');
				$sPlatforms = '';
				if(is_array($aPlatforms) && count($aPlatforms) > 0)
				{
					$aOutputPlatforms = array();
					foreach($aPlatforms as $aPlatform)
					{
						$aOutputPlatforms[] = $aPlatform['name'];
					}
					$sPlatforms = implode(", ", $aOutputPlatforms);
				}
				$aRow['platforms'] = $sPlatforms;
			}
			
			$aRow['url'] = sprintf($aSource['source_id_url'],$aRow['original_id']);
			$aRow['genres'] = $sGenres;
			$aRow['title'] = $aRow[$sTitleField];
			$aRow['image_url'] = $aRow['original_id'].'.jpg';
			$aRow['server_id'] = Phpfox::getLib('request')->getServer('PHPFOX_SERVER_ID');
			if(Phpfox::getService('medialibrary')->isMediaLikedByMe($aRow['media_id']))
				$aRow['like_me'] = true;
			else
				$aRow['like_me'] = false;
		}
		
		return $aRow;
	}
	
	/**
	 * Get a list of all the users who also like a media
	 *
	 * @param media 		$aMedia		Media object containing at least "media_id" and "type"
	 *
	 * @return $aOutput an array of user objects who also like this media
	 */
	public function getEntryPerFan($aMedia)
	{
		$iMediaId = (int)$aMedia['media_id'];
		$sType = $aMedia['type'];
		$aRows = $this->database()->select('u.full_name, u.user_id, u.user_name, u.user_image as image_link')
			->from(Phpfox::getT('medialibrary_users'), 'fu')
			->join(Phpfox::getT('user'), 'u', 'fu.user_id = u.user_id')
			->where('fu.user_id != '.Phpfox::getUserId().' AND fu.media_id = '.$iMediaId)
			->execute('getSlaveRows');
		
		$aOutput = array();
		foreach($aRows as $aRow)
		{
			$iUserId = $aRow['user_id'];
			$aMediasUser = Phpfox::getService('medialibrary')->getFullListOfMediaFromUser($iUserId);
			if(in_array($iMediaId, $aMediasUser) && Phpfox::getService('user.privacy')->hasAccess($iUserId, 'medialibrary.display_on_profile'))
			{
				$aRow['server_id'] = Phpfox::getLib('request')->getServer('PHPFOX_SERVER_ID');
				$aRow['medialibrary_url'] = '/'.$aRow['user_name'].'/medialibrary/'.$sType;
				$aOutput[] = $aRow;
				
			}
		}
		
		return $aOutput;
	}
	
	/**
	 * Get the number of all the users who also like this media
	 *
	 * @param mediaid 		$aMediaId		Media ID
	 *
	 * @return the number of fans
	 */
	public function getEntryNumberFans($iMediaId)
	{
		$aNumberFans = $this->database()->select('COUNT(*) as count')
				->from(Phpfox::getT('medialibrary_users'), 'u')
				->where('u.media_id = '.(int)$iMediaId)
				->execute('getSlaveRow');
		if(is_array($aNumberFans) && count($aNumberFans) > 0)
		{
			return $aNumberFans['count'];
		}
		return 0;
	}
	
	/**
	 * Add a new media in SQL, using a dedicaced service class per type
	 *
	 * @param sourceid 		$iSourceId		Source ID of the media to retrieve
	 * @param typename 		$sType			Type name
	 * @param categoryid	$iCategoryId	Category ID
	 *
	 * @return $aRow an array of the new media properties
	 */
	public function addData($iSourceId, $sType, $iCategoryId)
	{
		$sSearchId = preg_replace("/[^0-9]/", '', $iSourceId);
		
		//Check if title exist
		$iTypeId = Phpfox::getService('medialibrary')->getTypeIdFromName($sType);
		$aRow = $this->database()->select('f.media_id, f.title, f.original_title, f.original_id')
				->from(Phpfox::getT('medialibrary'), 'f')
				->where('f.type_id = ' . (int)$iTypeId . ' AND f.original_id = \''.$sSearchId.'\'')
				->execute('getSlaveRow');
				
		//Exists
		if(is_array($aRow) && count($aRow) >= 1)
		{
			$iMediaId = $aRow['media_id'];
			$this->database()->update(Phpfox::getT("medialibrary"), array('time_update' => PHPFOX_TIME), 'media_id = ' . (int) $iMediaId);	
		}	
		else
		{
			$aRow = Phpfox::getService('medialibrary.'.$sType)->addData($sSearchId, $iTypeId);
		}
		$aRow['image_url'] = $aRow['original_id'].'_thumb.jpg';
		$aRow['server_id'] = Phpfox::getLib('request')->getServer('PHPFOX_SERVER_ID');
		$sLanguage = Phpfox::getService('medialibrary')->getLanguage();
		if($sLanguage != 'fr-FR')
			$aRow['title'] = $aRow['original_title'];
		
		//Check if category belongs to the current user
		if($iCategoryId != 0)
		{
			$aCategories = Phpfox::getService('medialibrary')->getUserCategories(Phpfox::getUserId(), $sType);
			$bCatFound = false;
			foreach($aCategories as $aValue)
			{
				if($aValue['category_id'] == $iCategoryId)
				{
					$bCatFound = true;
					break;
				}
			}
		}
		if($iCategoryId != 0 && !$bCatFound)
			return;
		
		//Check if like exists
		$iMediaId = (int)$aRow['media_id'];
		$aRowLike = $this->database()->select('fu.media_id')
				->from(Phpfox::getT('medialibrary_users'), 'fu')
				->where('fu.media_id = ' . (int)$iMediaId . ' AND fu.user_id = '.Phpfox::getUserId())
				->execute('getSlaveRow');
		if(!is_array($aRowLike) || count($aRowLike) == 0)
		{
			$this->database()->insert(Phpfox::getT("medialibrary_users"), array(
					'media_id' => (int)$iMediaId,
					'user_id' => Phpfox::getUserId(),
					'category_id' => (int)$iCategoryId,
					'time_stamp' => PHPFOX_TIME
				)
			);
		}
		else
			$aRow['exists'] = 1;
			
		$this->cache()->remove('medialibrary.categories_'.Phpfox::getUserId().'_'.(int)$iTypeId, 'substr');	
		$this->cache()->remove('medialibrary.medias_count_'.Phpfox::getUserId(), 'substr');	
		$this->cache()->remove('medialibrary.medias_full_'.Phpfox::getUserId(), 'substr');
		
		return $aRow;
	}
	
	/**
	* Update the order of the media list for a specific user.
	*
	* @param ids 		$aOrderRules	Array of ids to reorder, in the new order
	* @param categoryid	$iCategoryId	Category ID
	*/
	public function updateOrderMedias($aOrderRules, $iCategoryId)
	{
		$ids = implode(',', $aOrderRules);
		$sql = 'UPDATE '.Phpfox::getT('medialibrary_users').' SET `time_stamp` = '.PHPFOX_TIME.', `order` = (CASE media_id ';
		foreach ($aOrderRules as $id => $ordinal) {
			$sql .= sprintf("WHEN %d THEN %d ", (int)$ordinal, (int)$id);
		}
		$sql .= 'END) WHERE `media_id` IN ('.$ids.') AND `category_id`='.(int)$iCategoryId.' AND `user_id`='.(int)Phpfox::getUserId();
		$this->database()->query($sql);
	}
	
	/**
	* Update the order of the media categories for a specific type
	*
	* @param ids 		$aOrderRules	Array of ids to reorder, in the new order
	* @param typename	$sType			Type name
	*/
	public function updateOrderCategories($aOrderRules, $sType)
	{
		$iTypeId = Phpfox::getService('medialibrary')->getTypeIdFromName($sType);
		$ids = implode(',', $aOrderRules);
		$sql = 'UPDATE '.Phpfox::getT('medialibrary_users_categories').' SET `time_stamp` = '.PHPFOX_TIME.', `order` = (CASE category_id ';
		foreach ($aOrderRules as $id => $ordinal) {
			$sql .= sprintf("WHEN %d THEN %d ", (int)$ordinal, (int)$id);
		}
		$sql .= 'END) WHERE `category_id` IN ('.$ids.') AND `type_id`='.(int)$iTypeId.' AND `user_id`='.(int)Phpfox::getUserId();
		$this->database()->query($sql);
		$this->cache()->remove('medialibrary.categories_'.Phpfox::getUserId().'_'.(int)$iTypeId, 'substr');
	}
	
	/**
	* Delete the media from the list of a specific user. Note that it will keep the media in the medialibrary table, it will just unassign it from a user
	*
	* @param mediaid 		$aMediaId		Media ID
	* @param typename		$sType			Type name
	*/
	public function deleteMedia($iMediaId, $sType)
	{
		$iUserId = Phpfox::getUserId();
		$iTypeId = Phpfox::getService('medialibrary')->getTypeIdFromName($sType);
		$this->database()->delete(Phpfox::getT('medialibrary_users'), 'media_id = ' . (int)$iMediaId . ' AND user_id = ' . (int)$iUserId);
		if($sType == 'game')
			$this->database()->delete(Phpfox::getT('medialibrary_games_accounts'), 'media_id = ' . (int)$iMediaId . ' AND user_id = ' . (int)$iUserId);
		$this->cache()->remove('medialibrary.categories_'.$iUserId.'_'.(int)$iTypeId, 'substr');
		$this->cache()->remove('medialibrary.medias_count_'.$iUserId, 'substr');	
		$this->cache()->remove('medialibrary.medias_full_'.$iUserId, 'substr');
		$this->cache()->remove('medialibrary.platforms_count_'.(int)$iUserId, 'substr');
	}
	
	/**
	* Set the boolean "like" to true or false for a specific user, depending of its current stage
	*
	* @param mediaid 		$aMediaId		Media ID
	* @param typename		$sType			Type name
	*
	* @return true if liked after the change, otherwise false
	*/
	public function switchLikeMedia($iMediaId, $sType)
	{
		$iUserId = Phpfox::getUserId();
		$iTypeId = Phpfox::getService('medialibrary')->getTypeIdFromName($sType);
		$bLiked = Phpfox::getService('medialibrary')->isMediaLikedByMe($iMediaId);
		if($bLiked)
		{
			$this->database()->delete(Phpfox::getT('medialibrary_users'), 'media_id = ' . (int)$iMediaId . ' AND user_id = ' . (int)$iUserId);
			if($sType == 'game')
				$this->database()->delete(Phpfox::getT('medialibrary_games_accounts'), 'media_id = ' . (int)$iMediaId . ' AND user_id = ' . (int)$iUserId);
		}
		else
			$this->database()->insert(Phpfox::getT("medialibrary_users"), array('media_id' => (int)$iMediaId, 'user_id' => $iUserId, 'category_id' => 0, 'time_stamp' => PHPFOX_TIME));
		$this->cache()->remove('medialibrary.categories_'.$iUserId.'_'.(int)$iTypeId, 'substr');
		$this->cache()->remove('medialibrary.medias_count_'.$iUserId, 'substr');	
		$this->cache()->remove('medialibrary.medias_full_'.$iUserId, 'substr');
		
		return !$bLiked;
	}
	
	/**
	* Delete a category for a specific user
	*
	* @param categoryid		$iCategoryId	Category ID
	* @param typename		$sType			Type name
	*/
	public function deleteUserCategory($iCategoryId, $sType)
	{
		$iTypeId = Phpfox::getService('medialibrary')->getTypeIdFromName($sType);
		$this->database()->delete(Phpfox::getT('medialibrary_users_categories'), 'category_id = ' . (int)$iCategoryId . ' AND user_id = ' . (int)Phpfox::getUserId());
		$this->database()->update(Phpfox::getT('medialibrary_users'), array('`order`' => '0', 'time_stamp' => PHPFOX_TIME, 'category_id' => 0), 'category_id = ' . (int) $iCategoryId . ' AND user_id = ' . (int)Phpfox::getUserId());
		$this->cache()->remove('medialibrary.categories_'.Phpfox::getUserId().'_'.(int)$iTypeId, 'substr');
	}
	
	/**
	* Change the category of a media for a specific user and assign it to another category
	*
	* @param mediaid 		$aMediaId		Media ID
	* @param categoryid		$iCategoryId	Category ID
	* @param typename		$sType			Type name
	*/
	public function sendMediaToCategory($iMediaId, $iCategoryId, $sType)
	{
		$iTypeId = Phpfox::getService('medialibrary')->getTypeIdFromName($sType);
		$this->database()->update(Phpfox::getT('medialibrary_users'), array('`order`' => '0', 'time_stamp' => PHPFOX_TIME, 'category_id' => (int)$iCategoryId), 'media_id = ' . (int) $iMediaId . ' AND user_id = ' . Phpfox::getUserId());
		$this->cache()->remove('medialibrary.categories_'.Phpfox::getUserId().'_'.(int)$iTypeId, 'substr');
	}
	
	/**
	 * Get some generic information about the source of the webservice, using the specific class service for that type of media
	 *
	 * @param typename		$sType			Type name
	 *
	 * @return $aOutput	List of all the properties about a source
	 */
	public function getSource($sType)
	{
		return Phpfox::getService('medialibrary.'.$sType)->getSourceData($sType);
	}
	
	/**
	 * Get a list of all allowed types, if a non allowed type is entered manually by the user in the url, nothing will happen
	 *
	 * @return array of all allowed types
	 */
	public function getAllowedTypes()
	{
		return ['movie', 'series', 'game'];
	}
	
	/**
	 * Get the maximum number of medias that can be returned at once by a GetEntries type of request
	 *
	 * @return max entries per query
	 */
	public function getNumberMaxPerGetQuery()
	{
		return 40; //todo settings
	}
	
	/**
	 * Get the maximum number of categories to be added by a user per type
	 *
	 * @return max categories per user per type
	 */
	public function getNumberMaxCategories()
	{
		return 20; //todo settings
	}
	
	/**
	 * Get the picture dimensions that a picture must be resized into to fit into the media library
	 *
	 * @return an array of the picture dimensions
	 */
	public function getPictureDimensions()
	{
		return array(90, 120, 180, 240);
	}
	
	/**
	 * Get the language the search engine should be set too before consulting the webservice
	 *
	 * @return a string of the ISO norm of the language
	 */
	public function getLanguage()
	{
		$aUser = Phpfox::getService('user')->get(Phpfox::getUserId(), true);
		$sLanguage = 'en-US';
		if($aUser['language_id'] == 'fr')
			$sLanguage = 'fr-FR';
		return $sLanguage;
	}
	
	/**
	 * Security function that makes sure that the search function and webservice aren't used too fast/too often by a specific user
	 *
	 * @param action 		$sAction		Type of action attempted
	 * @param time			$iTime			Time to wait between each action of the same type (in seconds)
	 *
	 * @return true if the action can be attempted, otherwise false
	 */
	public function CanUseTheAPI($sAction, $iTime)
	{
		$sCacheId = $this->cache()->set('medialibrary.user_lock_'.Phpfox::getUserId());
		if (!($aLock = $this->cache()->get($sCacheId)))
		{
			$aLock[$sAction] = PHPFOX_TIME;
			$this->cache()->save($sCacheId, $aLock);
			return true;
		}
		if(isset($aLock[$sAction]) && $aLock[$sAction] > PHPFOX_TIME - $iTime)
			return false;
		$aLock[$sAction] = PHPFOX_TIME;
		$sCacheId = $this->cache()->set('medialibrary.user_lock_'.Phpfox::getUserId());
		$this->cache()->save($sCacheId, $aLock);
		
		return true;
	}
}

?>
