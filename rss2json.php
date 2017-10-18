<?php


/**
 * @author Abdul Wahid <wahid.pmt@gmail.com>
 * @copyright Idm Store 2017
 * @package kemenag_barsel
 * 
 * 
 * Created using Ionic App Builder
 * http://codecanyon.net/item/ionic-mobile-app-builder/15716727
 */


/** --- START CONFIG --- **/
$append_url = "?ref=codegenerator";
$url_feeds["berita"] = "https://kalteng.kemenag.go.id/rss";
$date_format = "l, jS \of F Y h:i:s A";
/** --- END CONFIG --- **/


$rest_api = array();
function get_images($content){
	$images = array();
	libxml_use_internal_errors(true);
	$doc = new DOMDocument();
	$doc->loadHTML($content);
	libxml_clear_errors();
	$imageTags = $doc->getElementsByTagName("img");
	foreach ($imageTags as $tag)
	{
		$images[] = $tag->getAttribute("src");
	}
	return $images;
}
function get_rss($url,$date_format) {
	global $append_url;
	$rss_content = file_get_contents($url);
	$obj = simplexml_load_string($rss_content,"SimpleXMLElement",LIBXML_NOCDATA);
	$arr = json_decode(json_encode($obj), true);
	$z = 0;
	$new_entry = array();
	if(!isset($arr["entry"])){
		$arr["entry"]=$arr["channel"]["item"] ;
	}
	foreach ($arr['entry'] as $entry){
		$new_entry[$z] = $entry;
		//fix id
		$new_entry[$z]['id'] = $z;
		//fix link
		if (isset($entry['link'])){
			if (isset($entry['link']['@attributes'])){
				$new_entry[$z]['x_link']['attributes'] = $entry['link']['@attributes'];
				if (isset($new_entry[$z]['x_link']['attributes']['href'])){
					$new_entry[$z]['x_link']['attributes']['href'] = $new_entry[$z]['x_link']['attributes']['href'].$append_url ;
				}
			}
			if (count($entry['link']) > 1){
				$y = 0;
					foreach ($entry['link'] as $link){
						$new_entry[$z]['x_link'][$y]['attributes'] = $link['@attributes'] ;
						if (isset($link['@attributes']['href'])){
							$new_entry[$z]['x_link'][$y]['attributes']['href'] = $link['@attributes']['href'].$append_url ;
						}
					$y++;
				}
			}
		}
		if (isset($entry['category']['@attributes'])){
			$new_entry[$z]['x_category']['attributes'] = $entry['category']['@attributes'];
		}
		if (isset($entry['updated'])){
			$new_entry[$z]['x_updated'] = date($date_format, strtotime($entry['updated']));
		}
		if (isset($entry['published'])){
			$new_entry[$z]['x_published'] = date($date_format, strtotime($entry['published']));
		}
		if (isset($entry['description'])){
			$new_entry[$z]['content'] = $entry['description'];
			unset($new_entry[$z]['description']);
			$images = get_images($new_entry[$z]['content']);
			if (isset($images[0]))
			{
				$new_entry[$z]['thumbnail'] = $images[0];
			}
			$new_entry[$z]['images'] = $images;
		}
		if (isset($entry['content'])){
			$images = get_images($new_entry[$z]['content']);
			if (isset($images[0]))
			{
				$new_entry[$z]['thumbnail'] = $images[0];
			}
			$new_entry[$z]['images'] = $images;
		}
		if (isset($entry['pubDate'])){
			$new_entry[$z]['x_published'] = date($date_format, strtotime($entry['pubDate']));
			unset($new_entry[$z]['pubDate']);
		}
		if (isset($entry['link'])){
			$new_entry[$z]['x_link']['attributes']['href'] = $entry['link'];
		}
		if (isset($entry['enclosure']['@attributes'])){
			$new_entry[$z]['enclosure']['attributes'] = $entry['enclosure']['@attributes'];
		}
		if (isset($entry['link']['@attributes']['href'])){
			$new_entry[$z]['x_link']['attributes']['href'] = $entry['link']['@attributes']['href'];
		}
		$z++;
	}
	return $new_entry;
}


if(!isset($_GET["json"])){
	$_GET["json"]= "route";
}
switch($_GET["json"]){
	case "berita": 
		$rest_api = get_rss($url_feeds["berita"],$date_format);
		break;
	case "route":
		$rest_api["routes"][0] = array("namespace"=>"berita","methods"=>"GET","link"=>$_SERVER["PHP_SELF"]."?json=berita");
		break;
}

header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
if(defined("JSON_UNESCAPED_UNICODE")){
	echo json_encode($rest_api,JSON_UNESCAPED_UNICODE);
}else{
	echo json_encode($rest_api);
}
