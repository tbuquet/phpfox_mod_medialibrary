<?php 
defined('PHPFOX') or exit('NO DICE!');

/**
* Class that processes the loading of the block "platforms"
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/
class Medialibrary_Component_Block_Platforms extends Phpfox_Component 
{ 
	/**
	* Load the required information for the bloc "platforms"
	*/
	public function process() 
	{
		$aUser = $this->getParam('aUser');
		$iUserId = (int)$aUser['user_id'];
		
		//Get type
		$sType = $this->request()->get('req3');
		$bIsVisible = true;
		if($sType != 'game')
		{
			$bIsVisible = false;
			
			$this->template()->assign(array(
			'bIsVisible' => $bIsVisible
			));
			return 'block';
		}
		
		//Security
		if (!Phpfox::getService('user.privacy')->hasAccess($iUserId, 'profile.view_profile'))
			return Phpfox::getLib('module')->setController('profile.private');			
		
		if (!Phpfox::getService('user.privacy')->hasAccess($iUserId, 'medialibrary.display_on_profile'))
			return;

		//Get plateformes
		$aPlatforms = Phpfox::getService('medialibrary.game')->getGamePlatformCountsPerUser($iUserId);

		$this->template()->assign(array(
			'aPlatforms' => $aPlatforms,
			'bIsVisible' => $bIsVisible,
			'sHeader' => Phpfox::getPhrase('medialibrary.block_platforms')
		));
		return 'block';
	} 
} 
?>