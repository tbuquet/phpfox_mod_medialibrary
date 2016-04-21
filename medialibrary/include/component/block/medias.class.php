<?php 
defined('PHPFOX') or exit('NO DICE!');

/**
* Class that processes the loading of the block "medias"
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/
class Medialibrary_Component_Block_Medias extends Phpfox_Component 
{ 
	/**
	* Load the required information for the bloc "medias"
	*/
	public function process() 
	{
		$aUser = $this->getParam('aUser');
		$iUserId = (int)$aUser['user_id'];
		
		$aMediaCounts = Phpfox::getService('medialibrary')->getMediaCounts($iUserId);
		$iNbrMovie = 0;
		$iNbrSeries = 0;
		$iNbrGame = 0;
		foreach($aMediaCounts as $aMediaCount)
		{
			if($aMediaCount['name'] == 'movie')
				$iNbrMovie = $aMediaCount['count'];
			else if($aMediaCount['name'] == 'series')
				$iNbrSeries = $aMediaCount['count'];
			else if($aMediaCount['name'] == 'game')
				$iNbrGame = $aMediaCount['count'];
		}
	
		$this->template()->assign(array(
		'sHeader' => Phpfox::getPhrase('medialibrary.block_medias'),
		'iNbrMovie' => $iNbrMovie,
		'iNbrSeries' => $iNbrSeries,
		'iNbrGame' => $iNbrGame
		));
		return 'block';
	} 
} 
?>