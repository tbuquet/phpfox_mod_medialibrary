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
<span id="media_cat_save_categories" style="display:none">
<small><a href="javascript:;" onclick="updateOrderCategories()">{phrase var='medialibrary.save_order'}</a><span class="updateMediaWaiting" style="display:none">{img theme='ajax/small.gif'}</span></small>
</span>
<ul id="media_cat" class="action">
	{foreach from=$aCategories item=aCategory name=categories}
		{if $aCategory.category_id != ''}
			<li class="{if $iCurrentCategory == $aCategory.category_id}active{else}media_draggable{/if}" id="js_media_category_{$aCategory.category_id}" ref="{$aCategory.category_id}">
				<a href="{url link=$aUser.user_name}medialibrary/{$sType}/{$aCategory.category_id}"><span id="js_media_category_name_{$aCategory.category_id}">{$aCategory.name}</span> <span id="js_media_category_number_{$aCategory.category_id}" nbr="{$aCategory.count}" class="media_nb">({$aCategory.count})</span></a>{if $iCurrentCategory != $aCategory.category_id && Phpfox::getUserId() == $iUserId}<span class="media_category_delete"><small><a href="javascript:;" onclick="deleteCategory({$aCategory.category_id})">{phrase var='medialibrary.delete_category'}</a></small></span>{/if}
			</li> 
		{/if}
	{/foreach}
</ul>
<div class="clear"></div>
<ul id="media_cat_divers" class="action">
	<li class="{if $iCurrentCategory == 0}active{else}media_draggable{/if}"><a href='{url link=$aUser.user_name}medialibrary/{$sType}/divers'>{phrase var='medialibrary.category_divers'} <span id="js_media_category_number_0" nbr="{$iNbrDivers}" class="media_nb">({$iNbrDivers})</span></a></li>
</ul>