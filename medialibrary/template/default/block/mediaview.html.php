<?php 
defined('PHPFOX') or exit('NO DICE!'); 

/**
* Template class for block "mediaview"
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/ 
?>
<div id="media_view" ref="{$aMedia.media_id}">
	<div id="media_view_card">
		<div id="media_view_picture">
			<a href="{$aMedia.url}" target="__blank" title="{$aMedia.title}"><img src="{img server_id=$aMedia.server_id path=$aSource.image_url file=$aMedia.image_url suffix='_240' max_width=240 max_height=240 return_url=true}"/></a>
		</div>
		<div id="media_view_details">
			<ul>
				{if $aMedia.title != ''}
				<li class="media_view_info_field">{phrase var='medialibrary.field_title'}</li>
				<li class="media_view_info_value">{$aMedia.title}</li>
				{/if}
				{if $aMedia.original_title != ''}
				<li class="media_view_info_field">{phrase var='medialibrary.field_original_title'}</li>
				<li class="media_view_info_value">{$aMedia.original_title}</li>
				{/if}
				{if $aMedia.year != ''}
				<li class="media_view_info_field">{phrase var='medialibrary.field_year'}</li>
				<li class="media_view_info_value">{$aMedia.year}</li>
				{/if}
				{if $aMedia.genres != ''}
				<li class="media_view_info_field">{phrase var='medialibrary.field_genres'}</li>
				<li class="media_view_info_value">{$aMedia.genres}</li>
				{/if}
				{if $aMedia.type == 'game'}
				<li class="media_view_info_field">{phrase var='medialibrary.field_platforms'}</li>
				<li class="media_view_info_value">{$aMedia.platforms}</li>
				{/if}
				<li>
					{if $iCurrentUserId != Phpfox::getUserId()}
					<!--<small><a href="#" onclick="$.ajaxCall('medialibrary.switchLikeMedia', 'id={$aMedia.media_id}&t={$aMedia.type}');" class="js_like_link_like">{if $aMedia.like_me}{phrase var='medialibrary.unlike'}{else}{phrase var='medialibrary.like'}{/if}</a></small> - -->
					{/if}
					<small>{$iNbrFans} {if $iNbrFans > 1}{phrase var='medialibrary.fans'}{else}{phrase var='medialibrary.fan'}{/if}</small>
				</li>
			</ul>
		</div>
		<div class="clear"></div>
	</div>
	{if $iNbrFriends > 0}
	<div id="media_likes">
		<h3>{phrase var='medialibrary.view_friends_like'}</h3>
		{foreach from=$aUsers name=users item=aUser}
			<a href="{$aUser.medialibrary_url}" target="__blank" title="{$aUser.full_name}">{img server_id=$aUser.server_id path='core.url_user' file=$aUser.image_link suffix='_50_square' max_width='32' max_height='32'}</a>		
		{/foreach}
	</div>
	{/if}
	{if $aMedia.type == 'game' && $iNbrAccounts > 0}
		<div id="media_accounts">
			<h3>{phrase var='medialibrary.people_accounts'}</h3>
			<table style="width:100%"><tr class="media_account_cnt"><th>{phrase var='medialibrary.field_user'}</th><th>{phrase var='medialibrary.field_platform'}</th><th>{phrase var='medialibrary.field_account'}</th></tr>
			{foreach from=$aAccounts name=account item=aAccount}
				<tr class="media_account_cnt">
					<td class="media_account_picture">{img server_id=$aAccount.server_id path='core.url_user' file=$aAccount.image_link suffix='_50_square' max_width='32' max_height='32'}</td>
					<td class="media_account_platform">{$aAccount.platform}</td>
					<td class="media_account_info">{$aAccount.account}</td>
				</tr>
			{/foreach}
			</table>
			<div class="clear"></div>
		</div>
	{/if}
</div>