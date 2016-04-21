<?php
defined('PHPFOX') or exit('NO DICE!');

/**
* Template class for block "medias"
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/ 
?>
<ul id="media_medias" class="action">
	<li class="category{if $sType == 'movie'} active{/if}"><a href='{url link=$aUser.user_name}medialibrary/movie'>{phrase var='medialibrary.media_movie'} <span id="js_media_media_movie" nbr="{$iNbrMovie}" class="media_nb">({$iNbrMovie})</span></a></li>
	<li class="category{if $sType == 'series'} active{/if}"><a href='{url link=$aUser.user_name}medialibrary/series'>{phrase var='medialibrary.media_series'} <span id="js_media_media_series" nbr="{$iNbrSeries}" class="media_nb">({$iNbrSeries})</span></a></li>
	<li class="category{if $sType == 'game'} active{/if}"><a href='{url link=$aUser.user_name}medialibrary/game'>{phrase var='medialibrary.media_game'} <span id="js_media_media_game" nbr="{$iNbrGame}" class="media_nb">({$iNbrGame})</span></a></li>
</ul>