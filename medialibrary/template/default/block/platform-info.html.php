<?php
defined('PHPFOX') or exit('NO DICE!'); 

/**
* Template class for block "categories"
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/ 
?>
<div id="media_platforms" ref="{$sGameId}">
<div id="media_platforms_title"><h2>{phrase var='medialibrary.game_settings'} {$sGameName}</h2></div>
<div id="media_platforms_subtitle"><small>{phrase var='medialibrary.game_settings_info'}</small></div>
<ul>
	{foreach from=$aPlatforms item=aPlatform name=platforms}
		<li class="media_platform_item_holder" id="js_platform_id_{$aPlatform.platform_id}" ref="{$aPlatform.platform_id}">
			<li><div class="media_platform_line" ref="{$aPlatform.platform_id}"><div class="media_platform_checkbox"><input type="checkbox" {if isset($aPlatform.account)}checked="checked"{/if} name="{$aPlatform.name}" id="js_platform_checkbox_{$aPlatform.platform_id}"/></div><div class="media_platform_name">{$aPlatform.name}</div><div class="media_platform_info" {if isset($aPlatform.account)}style="display:block"{/if}><input id="js_platform_input_{$aPlatform.platform_id}" type="text" maxlength="30" value="{if isset($aPlatform.account)}{$aPlatform.account}{else}{phrase var='medialibrary.online_info'}{/if}"/></div></div></li>
			<li><hr/></li>
		</li> 
	{/foreach}
</ul>
<div id="media_platforms_actions"><input type="button" onclick="saveGameSettings()" value="{phrase var='medialibrary.save'}"/> <input type="button" onclick="unloadGameSettings();$('#js_media_game_settings').hide()" value="{phrase var='medialibrary.close'}"/></div>
<div class="clear"></div>
</div>

