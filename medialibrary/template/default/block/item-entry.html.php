<?php 
defined('PHPFOX') or exit('NO DICE!'); 

/**
* Template class for a specific media entry
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/ 
?>
{foreach from=$aItems item=aItem name=items}
	<li class="mediathek_item_holder" id="js_item_id_{$aItem.media_id}" ref="{$aItem.media_id}">
		{template file='medialibrary.block.item-entry-each'}
	</li> 
{/foreach}