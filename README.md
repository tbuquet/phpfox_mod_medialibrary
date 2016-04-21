module_medialibrary
===========
Medialibrary module is an extension for plugin I developped for one of my own sites.
I wanted something that could allow a user to easily add a movie, series, game they like to their profile, and see who else like the same medias.

There is still room for improvement, but this is fairly functionnal in the environnment I use (nginx).

Tested with version 3.8.0 of PHPFox.

##Requirements##
- Version 3.8.x of PHPFox
- Apache or NGINX server with SQL, PHP
- For games, an API Key to GiantBomb > [Link](http://www.giantbomb.com/api/)

##Features##
- Currently supports movie and series searching from IMDB and games from GiantBomb
- Let the user search for any title, with an autocomplete feature to give suggestions
- Save a picture of the media, along with some basic information (genre, year, platform for games, localized name)
- Let the user add categories to sort their medias
- Let the user reorder their medias
- Let the user see if any other member like the same media
- Let the user browse the list of medias liked by another member, and add them to their own list
- For games: Let the user specify in which platform(s) they own a game
- For games: Let the user see a still of game owned per platform
- For IMDB: Should search using the language of the user interface (experimental)

##How to install##
- Copy/paste the "medialibrary" folder in the "module" folder of your PHPFox installation
- In the administration panel of PHPFox, go to Extensions > Module management
- Find medialibrary in the list, and click to the install icon.
- Get a API Gui for GiantBomb, then replace the 'xxxxxxxx' of the constant GIANTBOMB_KEY in "module/medialibrary/include/service/game.class.php"
- Edit "include/setting/common.sett.php" and add the following lines at the end:
```php
$_CONF['medialibrary.dir_media_movie'] = $_CONF['core.dir_pic'] . 'mediathek' . PHPFOX_DS . 'movie' . PHPFOX_DS;
$_CONF['medialibrary.url_media_movie'] = $_CONF['core.url_pic'] . 'mediathek/movie/';
$_CONF['medialibrary.dir_media_series'] = $_CONF['core.dir_pic'] . 'mediathek' . PHPFOX_DS . 'series' . PHPFOX_DS;
$_CONF['medialibrary.url_media_series'] = $_CONF['core.url_pic'] . 'mediathek/series/';
$_CONF['medialibrary.dir_media_game'] = $_CONF['core.dir_pic'] . 'mediathek' . PHPFOX_DS . 'game' . PHPFOX_DS;
$_CONF['medialibrary.url_media_game'] = $_CONF['core.url_pic'] . 'mediathek/game/';
```

- Get the IMDBPHP library [Link](https://sourceforge.net/p/imdbphp/) and extract it to "www/thirdpartylibs/imdb/"

##TODO##
- Proper integration of IMDBPHP searching class
- Admin Settings, specifically for the GiantBomb API Key

##Thanks##
- Giorgos Giagas for his IMDB searching library.
