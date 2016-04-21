<?php 
defined('PHPFOX') or exit('NO DICE!'); 

/**
* Template class for controller of the profile page
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/ 
?>
{if isset($bHasAccess)}
	{if (Phpfox::getUserId() == $aUser.user_id) && $bIsCategory}
		<div class="media_admin_cnt"><div class="media_admin">{phrase var='medialibrary.search_on'} <a href="{$aSource.source_url}" title="{$aSource.name}">{$aSource.name}</a>: </div><div><input id="selectMedia" type="text" maxlength="32" /> <span id="selectMediaWaiting" style="display:none">{img theme='ajax/small.gif'}</div></div>
		<div class="media_admin_cnt"><div class="media_admin">{phrase var='medialibrary.add_category'}:</div><div><input id="inputCategory" type="text" maxlength="16"/><input id="addCategory" type="button" onclick="addCategory()" value="{phrase var='medialibrary.add'}"/><span {if $iNbrCategories >= 20}style="color:red"{/if}>(<span id="nbrCategories">{$iNbrCategories}</span> {phrase var='medialibrary.out_of'} {$iNbrMaxCategories})</span></div></div>
		<div class="clear"></div>
		<hr/>
	{/if}
	<div>
		{if $aCurrentCategory.name != "" && $iNbrCategories != 0}<div class="mediaTitle"><h3><span id="mediaCatTitle">{$aCurrentCategory.name}{if (Phpfox::getUserId() == $aUser.user_id && $bIsCategory) && $aCurrentCategory.category_id != 0}</span> <span class="media_cat_rename"><small><a href="javascript:;" onclick="renameCategory()" title="{phrase var='medialibrary.rename_category'}">{phrase var='medialibrary.rename_category'}</a></small></span>{/if}</h3></div>{/if}
		{if (Phpfox::getUserId() == $aUser.user_id)}
			<div class="like_status" style="display:none"><a href="javascript:;" onclick="updateOrderMedia()">{phrase var='medialibrary.save_media_order'}</a><span class="updateMediaWaiting" style="display:none">{img theme='ajax/small.gif'}</span></div> 
		{/if}
		<ul id="like_content" class="viewimage">
			{template file='medialibrary.block.item-entry'}
		</ul>
		<div class="clear"></div>
		{if count($aItems) >= Phpfox::getService('medialibrary')->getNumberMaxPerGetQuery()}
			<div id="loadMoreMedias" onclick="mediaLoadElements()">{phrase var='medialibrary.load_more'}</div>
		{/if}
	</div>
{else}
	{phrase var='medialibrary.no_access'}
{/if}