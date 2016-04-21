<?php 
defined('PHPFOX') or exit('NO DICE!');

/**
* Class that processes the loading of the block "mediaview", that display informations about a media
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/
class Medialibrary_Component_Block_Mediaview extends Phpfox_Component 
{ 
	/**
	* Load the required information for the bloc "mediaview"
	*/
	public function process() 
	{
		$iUserId = (int)Phpfox::getUserId();
		$iMediaId = (int)$this->getParam('iMediaId');
		
		$aMedia = Phpfox::getService('medialibrary')->getEntry($iMediaId);
		$sType = $aMedia['type'];
		$aSource = Phpfox::getService('medialibrary')->getSource($aMedia['type']);
		$aUsers = Phpfox::getService('medialibrary')->getEntryPerFan($aMedia);
		$iNbrFans = Phpfox::getService('medialibrary')->getEntryNumberFans($iMediaId);
		$aAccounts = array();
		if($sType == 'game')
			$aAccounts = Phpfox::getService('medialibrary.game')->getEntryPerGameAccount($iMediaId);
		
		$this->template()->assign(array(
			'aMedia' => $aMedia,
			'aSource' => $aSource,
			'aUsers' => $aUsers,
			'aAccounts' => $aAccounts,
			'iNbrFans' => $iNbrFans,
			'iNbrFriends' => count($aUsers),
			'iNbrAccounts' => count($aAccounts),
			'iCurrentUserId' => (int)$this->getParam('iCurrentUserId')
		));

		return 'block';
	} 
} 
?>