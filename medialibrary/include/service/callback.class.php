<?php
defined('PHPFOX') or exit('NO DICE!');

/**
* Callback class for medialibrary. This class is commonly used by PHPFox to add content to several pages or features.
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/
class Medialibrary_Service_Callback extends Phpfox_Service 
{
	/**
	 * Class constructor
	 */	
	public function __construct()
	{	
	}
	
	/**
	* Show a specific line for media stats in the admin panel
	*
	* @param starttime	$iStartTime date in which the query will start
	* @param endtime	$iEndTime	date in which the query will end
	*/
	public function getSiteStatsForAdmin($iStartTime, $iEndTime)
	{
		$aCond = array();
		if ($iStartTime > 0)
		{
			$aCond[] = 'AND time_stamp >= \'' . $this->database()->escape($iStartTime) . '\'';
		}	
		if ($iEndTime > 0)
		{
			$aCond[] = 'AND time_stamp <= \'' . $this->database()->escape($iEndTime) . '\'';
		}			
		
		$iCnt = (int) $this->database()->select('COUNT(*)')
			->from(Phpfox::getT('medialibrary'))
			->where($aCond)
			->execute('getSlaveField');
		
		return array(
			'phrase' => 'medialibrary.admin',
			'total' => $iCnt
		);
	}	
	
	/**
	* Show a link for medialibrary in the user profile
	*/
	public function getProfileLink()
	{
		return 'profile.medialibrary';
	}	
	
	/**
	 * Action to take when user cancelled their account
	 *
	 * @param int $iUser
	 */
	public function onDeleteUser($iUser)
	{
		$this->database()->delete(Phpfox::getT('medialibrary_users'), 'user_id = ' . (int)$iUser);
		$this->database()->delete(Phpfox::getT('medialibrary_users_categories'), 'user_id = ' . (int)$iUser);
	}
		
	/**
	* Show some additional settings related to this module
	*/
	public function getProfileSettings()
	{
		return array(
			'medialibrary.display_on_profile' => array(
				'phrase' => Phpfox::getPhrase('medialibrary.view_media_within_your_profile'),
				'default' => '0'				
			)
		);
	}
	
	/**
	* Add a treenode (usually, just one node/link) to the current list of features for a user profile
	*
	* @param user	$aUser array containing the current user properties
	*/
	public function getProfileMenu($aUser)
	{	
		if(Phpfox::getService('user.privacy')->hasAccess($aUser['user_id'], 'medialibrary.display_on_profile'))
		{
			$medias = count(Phpfox::getService('medialibrary')->getFullListOfMediaFromUser($aUser['user_id']));
		
			$aMenus[] = array(
				'phrase' => Phpfox::getPhrase('medialibrary.mediathek'),
				'url' => 'medialibrary/movie',
				'total' => (int) $medias,
				'icon' => 'feed/mediathek.png'
			);	
		
			return $aMenus;
		}
	}
	
	/**
	* Get a link to the ajax controller
	*/
	public function getAjaxProfileController()
	{
		return 'medialibrary.profile';
	}
		
	/**
	* Provide the actual method (sql query) used to retrieve the stats for the admin panel stats
	*/
	public function getSiteStatsForAdmins()
	{
		$iToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		
		return array(
			'phrase' => Phpfox::getPhrase('medialibrary.admin'),
			'value' => $this->database()->select('COUNT(*)')
				->from(Phpfox::getT('medialibrary'))
				->where('time_stamp >= ' . $iToday)
				->execute('getSlaveField')
		);
	}
}

?>
