<?php
defined('PHPFOX') or exit('NO DICE!');

/**
* Template class for block "platforms"
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/ 
?>
{if $bIsVisible}
	{if !isset($bReload)}
		<ul id="media_plat" class="action">
	{/if}
		{foreach from=$aPlatforms item=aPlatform name=platforms}
			<li class="{if $iCurrentPlatform == $aPlatform.platform_id}active{/if}" id="js_media_platform_{$aPlatform.platform_id}" ref="{$aPlatform.platform_id}">
				<a href="{url link=$aUser.user_name}medialibrary/{$sType}/p-{$aPlatform.platform_id}"><span id="js_media_category_name_{$aPlatform.platform_id}">{$aPlatform.name}</span> <span id="js_media_category_number_{$aPlatform.platform_id}" nbr="{$aPlatform.count}" class="media_nb">({$aPlatform.count})</span></a>
			</li> 
		{/foreach}
	{if !isset($bReload)}
		</ul>
	{/if}
{/if}