<?php
defined('PHPFOX') or exit('NO DICE!');

/**
* Service Class to handle the type "game" using giantbomb as a source
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/
class Medialibrary_Service_Game extends Phpfox_Service 
{
	//Private API key for GiantBOMB
	private constant GIANTBOMB_KEY = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
	
	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->type = 'movie';
	}
	
	/**
	 * Get content from the webservice
	 *
	 * @param query $sQuery	name or part of the name of the media
	 *
	 * @return an array of the possible results
	 */
	public function getData($sQuery)
	{
		$aOptions = array(
			'api_key' => GIANTBOMB_KEY,
			'query' => urlencode($sQuery),
			'format' => 'json',
			'limit' => 10,
			'resources' => 'game',
			'field_list' => 'id,name,platforms'
		);
		
		$aElements = (array) json_decode(Phpfox::getLib('request')->send('http://www.giantbomb.com/api/search/', $aOptions, 'GET', $_SERVER['HTTP_USER_AGENT']));

		if(isset($aElements) && isset($aElements['error']) && $aElements['error'] == 'OK')
		{
			$aOutput = array();
			foreach ($aElements['results'] as $oElement) {
				$sName = html_entity_decode($oElement->name);
				$aPlatforms = $oElement->platforms;
				$aEntity['label'] = $sName;
				if(is_array($aPlatforms) && count($aPlatforms) >= 1)
				{
					$aLabelPlatforms = array();
					foreach($aPlatforms as $oPlatform)
						$aLabelPlatforms[] = $oPlatform->name;
					$aEntity['label'] .= ' ('.implode(', ', $aLabelPlatforms).')';
				}
				$aEntity['value'] = $sName;
				$aEntity['valueid'] = $oElement->id;
				$aOutput[] = $aEntity;
			}
			return $aOutput;
		}
		return array();
	}
	
	/**
	 * Confirm that a platform exists or not by checking its sql table (cached)
	 *
	 * @param platformid $iPlatformId	source id of the platform searching
	 *
	 * @return true if exists, otherwise false
	 */
	public function isPlatformExists($iPlatformId)
	{
		$sCacheId = $this->cache()->set('medialibrary.platforms');
	
		if (!($aRows = $this->cache()->get($sCacheId)))
		{
			$aRows = $this->database()->select('p.*')
				->from(Phpfox::getT('medialibrary_games_platforms'), 'p')		
				->execute('getSlaveRows');
			
			$this->cache()->save($sCacheId, $aRows);
		}
		
		//GET existing
		if(is_array($aRows) && count($aRows) > 0)
		{
			foreach($aRows as $aRow)
			{
				if($aRow['platform_id'] == $iPlatformId)
					return true;
			}
		}
		return false;
	}
	
	/**
	 * Get a platform ID from its name, and add platform into sql if that name doesnt exist (cached)
	 *
	 * @param platformname $sPlatformName	Name of the platform
	 *
	 * @return platformID
	 */
	public function getPlatformIdFromName($sPlatformName)
	{
		$sCacheId = $this->cache()->set('medialibrary.platforms');
	
		//Normalisation
		switch ($sPlatformName)
		{
			case 'PlayStation Network (Vita)':
				$sPlatformName = 'PlayStation Vita';
				break;
			case 'PlayStation Network (PS3)':
				$sPlatformName = 'PlayStation 3';
				break;
			case 'PlayStation Network (PSP)':
				$sPlatformName = 'PlayStation Portable';
				break;
			case 'Nintendo 3DS eShop':
				$sPlatformName = 'Nintendo 3DS';
				break;
			case 'Xbox 360 Games Store':
				$sPlatformName = 'Xbox 360';
				break;
			case 'Wii Shop':
				$sPlatformName = 'Wii';
				break;
		}
	
		if (!($aRows = $this->cache()->get($sCacheId)))
		{
			$aRows = $this->database()->select('p.*')
				->from(Phpfox::getT('medialibrary_games_platforms'), 'p')		
				->execute('getSlaveRows');
			
			$this->cache()->save($sCacheId, $aRows);
		}
		
		//GET existing
		if(is_array($aRows) && count($aRows) > 0)
		{
			foreach($aRows as $aRow)
			{
				if($aRow['name'] == $sPlatformName)
					return (int)$aRow['platform_id'];
			}
		}
		
		//INSERT
		$iId = $this->database()->insert(Phpfox::getT("medialibrary_games_platforms"), array('name' => $sPlatformName));
		$this->cache()->remove('medialibrary.platforms', 'substr');
		
		return $iId;
	}
	
	/**
	 * Add a new media in SQL
	 *
	 * @param searchid $sSearchId	Source ID of the media to retrieve
	 * @param typeid $iTypeId		Main type of the media
	 *
	 * @return an array of the new media properties
	 */
	public function addData($sSearchId, $iTypeId)
	{
		$aOptions = array(
		'api_key' => GIANTBOMB_KEY,
		'id' => $sSearchId,
		'format' => 'json',
		'field_list' => 'id,image,name,platforms,site_detail_url,genres,original_release_date'
		);
		$aElements = (array) json_decode(Phpfox::getLib('request')->send('http://www.giantbomb.com/api/game/3030-'.$sSearchId.'/', $aOptions, 'GET', $_SERVER['HTTP_USER_AGENT']));

		//Check error message
		if($aElements['error'] != 'OK')
			return;
			
		$oItem = $aElements['results'];
		
		//Add Title & Get SourceID
		$title = Phpfox::getLib('parse.input')->clean($oItem->name);
		$date = $oItem->original_release_date;
		if($date != '')
			$date = substr($date, 0, 4);
		$iMediaId = $this->database()->insert(Phpfox::getT("medialibrary"), array(
				'original_id' => $sSearchId,
				'title' => $title,
				'year' => $date,
				'type_id' => $iTypeId,
				'time_stamp' => PHPFOX_TIME,
				'time_update' => PHPFOX_TIME
			)
		);
		
		//Get Genres
		$aGenres = $oItem->genres;
		if(is_array($aGenres) && count($aGenres) >= 1)
		{
			foreach($aGenres as $aGenre)
			{
				$iGenreId = Phpfox::getService('medialibrary')->getGenreIdFromName($aGenre->name);
				$this->database()->insert(Phpfox::getT("medialibrary_genres_medias"), array(
				'media_id' => (int)$iMediaId,
				'genre_id' => (int)$iGenreId
					)
				);
			}
		}
		
		//Get Platform
		$aPlatforms = $oItem->platforms;
		if(is_array($aPlatforms) && count($aPlatforms) >= 1)
		{
			foreach($aPlatforms as $aPlatform)
			{
				$iPlatformId = Phpfox::getService('medialibrary.game')->getPlatformIdFromName($aPlatform->name);
				
				$aRow = $this->database()->select('p.*')
				->from(Phpfox::getT('medialibrary_games_platforms_medias'), 'p')
				->where('p.media_id = '. (int)$iMediaId . ' AND p.platform_id= ' . (int)$iPlatformId)
				->execute('getSlaveRow');
				if(!is_array($aRow) || count($aRow) == 0)
				{
					$this->database()->insert(Phpfox::getT('medialibrary_games_platforms_medias'), array(
					'media_id' => (int)$iMediaId,
					'platform_id' => (int)$iPlatformId
						)
					);
				}
			}
		}
		
		//Get picture
		Phpfox::getService('medialibrary')->saveImage($oItem->image->medium_url, Phpfox::getParam('medialibrary.dir_media_game'), $sSearchId);
		
		//Create aRow for returning
		$aRow = array();
		$aRow['media_id'] = $iMediaId;
		$aRow['original_id'] = $sSearchId;
		$aRow['title'] = $title;
		

		return $aRow;
	}
	
	/**
	 * Get a list of a given user games, by platform
	 *
	 * @param userid 	$iUserId		User ID
	 * @param platformid $iPlatformId	Platform ID
	 * @param offset	 $iOffset		Offset to start the query
	 *
	 * @return an array of the games
	 */
	public function getEntriesFromUserPlatform($iUserId, $iPlatformId, $iOffset)
	{
		if(Phpfox::getService('user.privacy')->hasAccess($iUserId, 'medialibrary.display_on_profile'))
		{
			$iTypeId = Phpfox::getService('medialibrary')->getTypeIdFromName('game');
			$aRows = $this->database()->select('f.media_id, f.title, f.original_title, f.original_id')
			->from(Phpfox::getT('medialibrary'), 'f')
			->join(Phpfox::getT('medialibrary_games_accounts'), 'ga', 'ga.media_id = f.media_id')
			->where('f.type_id = \''.$iTypeId.'\' AND ga.user_id = '. (int)$iUserId . ' AND ga.platform_id = '. (int)$iPlatformId)
			->order('f.title')
			->limit($iOffset,Phpfox::getService('medialibrary')->getNumberMaxPerGetQuery())
			->execute('getSlaveRows');

			$iMeId = Phpfox::getUserId();
			foreach($aRows as $iKey => $aRow)
			{
				//$aRows[$iKey]['link'] = sprintf($aRow['source_url'], $aRow['original_id']);
				$aRows[$iKey]['title'] = $aRows[$iKey]['title'];
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
	 * Get the number of games owner for a specific user, per platform (cached)
	 *
	 * @param userid 	$iUserId		User ID
	 *
	 * @return an array of the platforms with their specific number of games
	 */
	public function getGamePlatformCountsPerUser($iUserId)
	{
		if(Phpfox::getService('user.privacy')->hasAccess($iUserId, 'medialibrary.display_on_profile'))
		{
			$sCacheId = $this->cache()->set('medialibrary.platforms_count_'.(int)$iUserId);
		
			if (!($aRows = $this->cache()->get($sCacheId)))
			{
				$aRows = $this->database()->select('gf.*, count(ga.media_id) as count')
					->from(Phpfox::getT('medialibrary_games_platforms'), 'gf')
					->join(Phpfox::getT('medialibrary_games_accounts'), 'ga', 'gf.platform_id = ga.platform_id')
					->where('ga.user_id = '. (int)$iUserId)
					->order('gf.name')
					->group('ga.platform_id')
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
	 * Get a list of all platforms in which a specific user own a game
	 *
	 * @param mediaid 	$iMediaId		Media ID
	 *
	 * @return an array of medias
	 */
	public function loadGamePlatforms($iMediaId)
	{
		$aRows = $this->database()->select('p.*, m.title, m.media_id, gm.account')
				->from(Phpfox::getT('medialibrary_games_platforms'), 'p')
				->join(Phpfox::getT('medialibrary_games_platforms_medias'), 'pm', 'p.platform_id = pm.platform_id')
				->join(Phpfox::getT('medialibrary'), 'm', 'm.media_id = pm.media_id')
				->leftJoin(Phpfox::getT('medialibrary_games_accounts'), 'gm', 'p.platform_id = gm.platform_id AND m.media_id = gm.media_id AND user_id='.Phpfox::getUserId())
				->where('m.media_id = ' . (int)$iMediaId)
				->execute('getSlaveRows');
				
		return $aRows;
	}
	
	/**
	 * Save a list of all platforms in which the current user own a game
	 *
	 * @param mediaid 	$iMediaId		Media ID
	 * @param input 	$aInputs		List of all selected platforms
	 */
	public function saveGamePlatforms($iMediaId, $aInputs)
	{
		if(!Phpfox::getService('medialibrary')->isMediaLikedByMe($iMediaId))
			return;
			
		$iUserId = Phpfox::getUserId();
		$this->database()->delete(Phpfox::getT('medialibrary_games_accounts'), 'media_id = ' . (int)$iMediaId . ' AND user_id = ' . $iUserId);
		foreach($aInputs as $aInput)
		{
			if($aInput->a == Phpfox::getPhrase('medialibrary.online_info'))
				$aInput->a = '';
			$this->database()->insert(Phpfox::getT("medialibrary_games_accounts"), array('media_id' => (int)$iMediaId, 'user_id' => $iUserId, 'platform_id' => (int)$aInput->p/*, 'account' => Phpfox::getLib('parse.input')->clean($aInput->a)*/));
			//TODO: Account management for admins
		}
		$this->cache()->remove('medialibrary.platforms_count_'.(int)$iUserId, 'substr');
	}
	
	/**
	 * Get a list of all users owning a specific game
	 *
	 * @param mediaid 	$iMediaId		Media ID
	 *
	 * @return $aOutput	List of all accounts owning the same game
	 */
	public function getEntryPerGameAccount($iMediaId)
	{
		$aAccounts = $this->database()->select('ga.account, u.full_name, u.user_id, u.user_name, u.user_image as image_link, p.name as platform')
			->from(Phpfox::getT('medialibrary_games_accounts'), 'ga')
			->join(Phpfox::getT('medialibrary_games_platforms'), 'p', 'p.platform_id = ga.platform_id')
			->join(Phpfox::getT('user'), 'u', 'ga.user_id = u.user_id')
			->where('ga.account != \'\' AND ga.user_id != '.Phpfox::getUserId().' AND ga.media_id = '.(int)$iMediaId)
			->execute('getSlaveRows');
			
		$aOutput = array();
		foreach($aAccounts as $aAccount)
		{
			if(Phpfox::getService('user.privacy')->hasAccess($aAccount['user_id'], 'medialibrary.display_on_profile'))
			{
				$aAccount['server_id'] = Phpfox::getLib('request')->getServer('PHPFOX_SERVER_ID');
				$aOutput[] = $aAccount;
			}
		}
		
		return $aOutput;
	}
	
	/**
	 * Get some generic information about the source of the webservice
	 *
	 * @return $aOutput	List of all the properties about a source
	 */
	public function getSourceData()
	{
		$aOutput = array();
		$aOutput['type'] = $this->type;
		$aOutput['name'] = 'GiantBomb';
		$aOutput['image_url'] = 'medialibrary.url_media_game';
		$aOutput['source_url'] = 'http://www.giantbomb.com';
		$aOutput['source_id_url'] = 'http://www.giantbomb.com/game/3030-%s/';
		return $aOutput;
	}
}

?>
