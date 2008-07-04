<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Generate metatags for current article if they are not set in article properties 
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0
 * @author     Andreas Lindner, Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2007-10-24
 *   modified 2008-07-04, bilal arslan, added security fix
 *   $Id: 
 * }}
 * 
 */
 
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("plugins", "repository/keyword_density.php");
cInclude('classes', 'class.security.php');

function cecCreateMetatags ($metatags) {
	
	global $cfg, $lang, $idart, $client, $cfgClient, $idcat, $idartlang;

	cInclude('classes', 'class.user.php');

	//Basic settings
	$cachetime = 3600; // measured in seconds
	$cachedir = $cfgClient[$client]['path']['frontend'] . "cache/";

	if (!is_array($metatags)) {
		$metatags = array();
	}
	
	$hash = 'metatag_'.md5($idart.'/'.$lang);
	$cachefilename = $cachedir.$hash.'.tmp';

	#Check if rebuilding of metatags is necessary
	$reload = true;
	
	$fileexists = false;
	
	if (file_exists($cachefilename)) {
		$fileexists = true;
		
		$diff =  mktime() - filemtime($cachefilename);
		
		if ($diff > $cachetime) {
			$reload = true;
		} else {
			$reload = false;
		}
	}
	
	if ($reload) {
		//(Re)build metatags
		$db = new DB_Contenido(); 
		
		#Get idcat of homepage
		$sql = "SELECT a.idcat
			FROM 
				".$cfg['tab']['cat_tree']." AS a,
				".$cfg['tab']['cat_lang']." AS b
			WHERE
				(a.idcat = b.idcat) AND
				(b.visible = 1) AND
				(b.idlang = ".Contenido_Security::toInteger($lang).")
			ORDER BY a.idtree LIMIT 1";
	
		$db->query($sql);
		
		if ($db->next_record()) {
			$idcat_homepage = $db->f('idcat');
		} 

		$availableTags = conGetAvailableMetaTagTypes();

		#Get first headline and first text for current article
		$oArt = new Article ($idart, $client, $lang);

		#Set idartlang, if not set
		if ($idartlang == '') {
			$idartlang = $oArt->_getIdArtLang($idart, $lang);
		}
		
		$arrHead1 = $oArt->getContent("htmlhead");
		$arrHead2 = $oArt->getContent("head");
		
		if (!is_array($arrHead1)) {
			$arrHead1 = array();
		}

		if (!is_array($arrHead2)) {
			$arrHead2 = array();
		}
		
		$arrHeadlines = array_merge($arrHead1, $arrHead2);
		
		foreach ($arrHeadlines as $key => $value) {
			if ($value != '') {
				$sHeadline = $value;
				break;
			}
		}
		
		$sHeadline = strip_tags($sHeadline);
		$sHeadline = substr(str_replace(chr(13).chr(10),' ',$sHeadline),0,100);
		
		$arrText1 = $oArt->getContent("html");
		$arrText2 = $oArt->getContent("text");
		
		if (!is_array($arrText1)) {
			$arrText1 = array();
		}

		if (!is_array($arrText2)) {
			$arrText2 = array();
		}

		$arrText = array_merge($arrText1, $arrText2);
		
		foreach ($arrText as $key => $value) {
			if ($value != '') {
				$sText = $value;
				break;
			}
		}

		$sText = strip_tags(urldecode($sText));
		$sText = keywordDensity ('', $sText);
		
		//Get metatags for homeapge
		$arrHomepageMetaTags = array();
        
		$sql = "SELECT startidartlang FROM ".$cfg["tab"]["cat_lang"]." WHERE (idcat=".Contenido_Security::toInteger($idcat_homepage).") AND(idlang=".Contenido_Security::toInteger($lang).")";
        $db->query($sql);
		
		if($db->next_record()){
			$iIdArtLangHomepage = $db->f('startidartlang');
			
			#get idart of homepage
	        $sql = "SELECT idart FROM ".$cfg["tab"]["art_lang"]." WHERE idartlang =".Contenido_Security::toInteger($iIdArtLangHomepage);
	
	        $db->query($sql);
			
			if ($db->next_record()) {
				$iIdArtHomepage = $db->f('idart');
			}
			
			$t1 = $cfg["tab"]["meta_tag"];
			$t2 = $cfg["tab"]["meta_type"];
			
			$sql = "SELECT ".$t1.".metavalue,".$t2.".metatype FROM ".$t1.
				" INNER JOIN ".$t2." ON ".$t1.".idmetatype = ".$t2.".idmetatype WHERE ".
				$t1.".idartlang =".$iIdArtLangHomepage.
				" ORDER BY ".$t2.".metatype";

			$db->query($sql);
			
			while ($db->next_record()) {
				$arrHomepageMetaTags[$db->f("metatype")] = $db->f("metavalue");
			}
			
			$oArt = new Article ($iIdArtHomepage, $client, $lang);
			
			$arrHomepageMetaTags['pagetitle'] = $oArt->getField('title');
		}
		
		//Cycle through all metatags
		foreach ($availableTags as $key => $value) {
			$metavalue = conGetMetaValue($idartlang, $key);

			if (strlen($metavalue) == 0){
				//Add values for metatags that don't have a value in the current article
				switch(strtolower($value["name"])){
					case 'author':
						//Build author metatag from name of last modifier
				        $oArt = new Article ($idart, $client, $lang);
						$lastmodifier = $oArt->getField("modifiedby");
						$oUser = new User();
						$oUser->loadUserByUserID(md5($lastmodifier));
						$lastmodifier_real = $oUser->getRealname(md5($lastmodifier));
							
						$iCheck = CheckIfMetaTagExists($metatags, 'author');
						$metatags[$iCheck]['name'] = 'author';
						$metatags[$iCheck]['content'] = htmlspecialchars($lastmodifier_real,ENT_QUOTES);

						break;
					case 'date':
						//Build date metatag from date of last modification
				        $oArt = new Article ($idart, $client, $lang);
						$lastmodified = $oArt->getField("lastmodified");
						
						$iCheck = CheckIfMetaTagExists($metatags, 'date');
						$metatags[$iCheck]['name'] = 'date';
						$metatags[$iCheck]['content'] = $lastmodified;

						break;
					case 'description':
						//Build description metatag from first headline on page
						$iCheck = CheckIfMetaTagExists($metatags, 'description');
						$metatags[$iCheck]['name'] = 'description';
						$metatags[$iCheck]['content'] = htmlspecialchars($sHeadline,ENT_QUOTES);
						
						break;
					case 'keywords':
						$iCheck = CheckIfMetaTagExists($metatags, 'keywords');
						$metatags[$iCheck]['name'] = 'keywords';
						$metatags[$iCheck]['content'] = htmlspecialchars($sText,ENT_QUOTES);

						break;
					case 'revisit-after':
					case 'robots':
					case 'expires':
						//Build these 3 metatags from entries in homepage
						$sCurrentTag = strtolower($value["name"]);
						$iCheck = CheckIfMetaTagExists($metatags, $sCurrentTag);
						$metatags[$iCheck]['name'] = $sCurrentTag;
						$metatags[$iCheck]['content'] = htmlspecialchars($arrHomepageMetaTags[$sCurrentTag],ENT_QUOTES);

						break;
				}
			}
		}
	
		$handle = fopen($cachefilename, 'w');
		fwrite($handle,serialize($metatags));
		fclose($handle);
		
	} else {
		#Get metatags from file system cache
		$metatags = unserialize(file_get_contents($cachefilename));
	}

	return $metatags;
}

function CheckIfMetaTagExists($arrMetatags, $sCheckForMetaTag) {

	if (is_array($arrMetatags)) {
		$iNextIndex = count($arrMetatags);
		
		$i = 0;
	
		foreach ($arrMetatags as $key => $value) {
			if (array_key_exists($sCheckForMetaTag, $value)) {
				$iNextIndex = $i;	
			}
			
			$i++;
		}
	} else {
		$iNextIndex = 0;
	}
	 

	return $iNextIndex;

}

?>
