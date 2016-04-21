<?php
defined('PHPFOX') or exit('NO DICE!');

/**
* Service Class to handle the type "movie" using imdbsearch as a source
*
* @package	medialibrary
* @author	Thibault Buquet
* @link		https://github.com/tbuquet/phpfox_mod_medialibrary/
* @version	1.0
*/
class Medialibrary_Service_Movie extends Phpfox_Service 
{
	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->type = 'movie';
	}
	
	/**
	 * Get content from the webservice
	 *
	 * @param query $sQuery	name or part of the name of the media
	 *
	 * @return an array of the possible results
	 */
	public function getData($sQuery)
	{
		if (!defined('imbdSearch')) {
			//TODO: Proper integration of IMDB
			require($_SERVER['DOCUMENT_ROOT'].'/thirdpartylibs/imdb/imdbsearch.class.php');
			define('imbdSearch', 1);
		}	
		
		$aFilterType = array();
		$aFilterType[] = imdbsearch::MOVIE;
		//$aFilterType[] = imdbsearch::TV_MOVIE;
		$aFilterType[] = imdbsearch::VIDEO;
	
		// create an instance of the search class
		$oSearch = new imdbsearch();
		$oResults = $oSearch->search($sQuery, $aFilterType, 10, Phpfox::getService('medialibrary')->getLanguage());

		$aOutput = array();
		foreach ($oResults as $oRes) {
			$iMid = $oRes->imdbid();
			$sName = html_entity_decode($oRes->title());
			$aEntity['label'] = $sName;
			$sYear = $oRes->year();
			if($sYear != '')
				$aEntity['label'] .= ' ('.$sYear.')';
			$aEntity['value'] = $sName;
			$aEntity['valueid'] = $iMid;
			$aOutput[] = $aEntity;
		}
		return $aOutput;
	}
	
	/**
	 * Add a new media in SQL
	 *
	 * @param searchid $sSearchId	Source ID of the media to retrieve
	 * @param typeid $iTypeId		Main type of the media
	 *
	 * @return an array of the new media properties
	 */
	public function addData($sSearchId, $iTypeId)
	{
		if (!defined('imbdSearch')) {
			//require('../../service/DB_imdb/imdbsearch.class.php');
			require($_SERVER['DOCUMENT_ROOT'].'/thirdpartylibs/imdb/imdb.class.php');
			define('imbdSearch', 1);
		}
	
		$oItem = new imdb($sSearchId); 
		
		//Get TypeID for verification
		$sTypeVerif = strtolower($oItem->movietype());
		if($sTypeVerif == "video")
			$sTypeVerif = "movie";
		if($sTypeVerif != $this->type)
			return;
		
		//Add Title & Get SourceID
		$title = Phpfox::getLib('parse.input')->clean($oItem->title());
		$originalTitle = Phpfox::getLib('parse.input')->clean($oItem->orig_title());
		$iMediaId = $this->database()->insert(Phpfox::getT("medialibrary"), array(
				'original_id' => $sSearchId,
				'title' => $title,
				'original_title' => $originalTitle,
				'year' => Phpfox::getLib('parse.input')->clean($oItem->year()),
				'type_id' => $iTypeId,
				'time_stamp' => PHPFOX_TIME,
				'time_update' => PHPFOX_TIME
			)
		);
		
		//Get Genres
		$aGenres = $oItem->genres();
		if(is_array($aGenres) && count($aGenres) >= 1)
		{
			foreach($aGenres as $aGenre)
			{
				$iGenreId = Phpfox::getService('medialibrary')->getGenreIdFromName($aGenre);
				$this->database()->insert(Phpfox::getT("medialibrary_genres_medias"), array(
				'media_id' => (int)$iMediaId,
				'genre_id' => (int)$iGenreId
					)
				);
			}
		}
		
		//Get picture
		$aDimensions = Phpfox::getService('medialibrary')->getPictureDimensions();
		$sPictureTemp = Phpfox::getParam('medialibrary.dir_media_movie') . $sSearchId .'_temp.jpg';
		$sPictureFinal = Phpfox::getParam('medialibrary.dir_media_movie') . $sSearchId .'.jpg';
		$sPictureFinalThumb = Phpfox::getParam('medialibrary.dir_media_movie') . $sSearchId .'_thumb.jpg';
		
		$oItem->savephoto($sPictureTemp, false);
		Phpfox::getLib('image')->createThumbnail($sPictureTemp, $sPictureFinalThumb, $aDimensions[0], $aDimensions[1], false);
		Phpfox::getLib('image')->createThumbnail($sPictureTemp, $sPictureFinal, $aDimensions[2], $aDimensions[3], false);
		Phpfox::getLib('file')->unlink($sPictureTemp);
		
		//Create aRow for returning
		$aRow = array();
		$aRow['media_id'] = $iMediaId;
		$aRow['original_id'] = $sSearchId;
		$aRow['title'] = $title;
		$aRow['original_title'] = $originalTitle;

		return $aRow;
	}
	
	/**
	 * Get some generic information about the source of the webservice
	 *
	 * @return $aOutput	List of all the properties about a source
	 */
	public function getSourceData()
	{
		$aOutput = array();
		$aOutput['type'] = $this->type;
		$aOutput['name'] = 'Imbd';
		$aOutput['image_url'] = 'medialibrary.url_media_movie';
		$aOutput['source_url'] = 'http://www.imdb.com';
		$aOutput['source_id_url'] = 'http://www.imdb.com/title/tt%s/';
		return $aOutput;
	}
}

?>
