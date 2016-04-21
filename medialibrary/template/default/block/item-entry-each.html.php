<?php 
defined('PHPFOX') or exit('NO DICE!'); 

/**
* Template class for entry iteration
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/ 
?>
<div class="mediaitem_row" >
	<div class="photo_row_height">
		<div class="mediathek_item_clip_holder_main image_hover_holder">
			<a href="#" class="image_hover_menu_link">{phrase var='medialibrary.link'}</a>
			{if (Phpfox::getUserId() == $iUserId) && $bIsCategory}
				<div class="image_hover_menu">
					<ul>
						{if $sType == 'game'}<li class="item_platform"><a href="javascript:;" title="{phrase var='medialibrary.edit_game_settings'}" onclick="tb_show('{phrase var='medialibrary.popup_platforms'}', $.ajaxBox('medialibrary.loadGameSettings', 'id={$aItem.media_id}'));return false;">{phrase var='medialibrary.edit_game_settings'}</a></li>{/if}
					    <li class="item_delete"><a href="javascript:;" title="{phrase var='medialibrary.delete_media'}" onclick="if (confirm('{phrase var='medialibrary.are_you_sure' phpfox_squote=true}')) $.ajaxCall('medialibrary.deleteMedia', 'id={$aItem.media_id}&t={$sType}'); return false;">{phrase var='medialibrary.delete_media'}</a></li>					   
					</ul>
				</div>
			{/if}
			<div class="mediathek_item_clip_holder_border">
				<a href="javascript:;" onclick="tb_show('{phrase var='medialibrary.popup_media'}', $.ajaxBox('medialibrary.loadMedia', 'id={$aItem.media_id}&u={$iUserId}'));return false;" style="background:url('{img server_id=$aItem.server_id path=$aSource.image_url file=$aItem.image_url suffix='_240' max_width=240 max_height=240 return_url=true}') no-repeat;" class="mediathek_item_clip_holder" rel="{$aItem.media_id}" title="{$aItem.title}"></a>		
				{if (Phpfox::getUserId() != $iUserId)}<div class="mediathek_like"><a id="mediathek_like_{$aItem.media_id}" href="javascript:;" onclick="$.ajaxCall('medialibrary.switchLikeMedia', 'id={$aItem.media_id}&t={$sType}');">{if $aItem.like_me}{phrase var='medialibrary.unlike'}{else}{phrase var='medialibrary.like'}{/if}</a></div>{/if}
			</div>			
		</div>
	</div>	
	<div class="photo_row_info">			
		<div class="extra_info_link">
			{$aItem.title}
		</div>
	</div>		
</div>

