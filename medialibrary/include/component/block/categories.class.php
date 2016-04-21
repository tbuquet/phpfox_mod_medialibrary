<?php 
defined('PHPFOX') or exit('NO DICE!');

/**
* Class that processes the loading of the block "categories"
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/
class Medialibrary_Component_Block_Categories extends Phpfox_Component 
{ 
	/**
	* Load the required information for the bloc "categories"
	*/
	public function process() 
	{
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
		
		$aCategories = Phpfox::getService('medialibrary')->getUserCategories($iUserId, $sType);
		
		$iNbrDivers = 0;
		foreach($aCategories as $aCategory)
		{
			if($aCategory['category_id'] == '' || $aCategory['category_id'] == 0)
			{
				$iNbrDivers = $aCategory['count'];
				break;
			}
		}

		$this->template()->assign(array(
			'aCategories' => $aCategories,
			'iNbrDivers' => $iNbrDivers,
			'sHeader' => Phpfox::getPhrase('medialibrary.block_categories')
		));

		return 'block';
	} 
} 
?>