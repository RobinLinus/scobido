<?php
//TODO deal with relative paths like url=http://example.com/subdir imgUrl=subsubdir/img.jpg
include_once 'crawler-helper.php';


function crawlImage($html,$doc,$url){
	$base = parseBase($url);

	if($img = isImageLink($url))
		return $img;
	if($img = tryMetaTags($doc))
		return $img;
	if($img = tryImgTags($doc,$base))
		return $img;
	if($img = tryRegexAbs($html))
		return $img;
	if($img = tryRegexRel($html,$base))
		return $img;
	return "placeholder";
}


function tryImgTags($doc,$base){
	$urls = array();
	$imgTags = $doc->getElementsByTagName("img");
	$len = $imgTags->length;
	$len =  $len < MAX_IMG_CAND ? $len: MAX_IMG_CAND; // only look at top images in the DOM
	$matches = 0;
	debug("<br>Stage1: DOM Rank<br>candidate count: $len");

	for ($i = 0; $i < $len; $i++){
	    $tag = $imgTags->item($i);
	    $imgUrl = $tag->getAttribute("src");
	    
	    $imgUrl = sanitizeUrl($imgUrl,$base);
	    if(!$imgUrl)
	    	continue;

	    $matches += 1;
	    $rank = calcNodeRank($tag)+(MAX_IMG_CAND-$matches)/MAX_IMG_CAND;
	    $attrRank = rankDimAttr($tag);
	    $rank += $attrRank;
	    debug("IMG_TAG: rank: $rank attr: $attrRank url: <a href=$imgUrl target=_blank>$imgUrl</a>");
	   	array_push($urls, [$imgUrl,$rank]);
	}


	if(!count($urls))
		return false;

	sortByRank($urls);

	$urls = array_slice($urls,0,7); 

 	$len = count($urls);
 	debug("<br>Stage2: File Size <br>candidate count: $len");

	for ($i = 0; $i < $len; $i++){
	    $url = $urls[$i][0];
		$rank = $urls[$i][1];
		$size = fetchFileSize($url);
	    debug("IMG_TAG: size: $size rank: $rank attr: $attrRank url: <a href=$url target=_blank>$url</a>");
		$urls[$i][1] = $rank * $size*$size;	
		if($size < MIN_MIN_FILE_SIZE){
			$urls[$i][1] = 0;
		} 
	}

	sortByRank($urls);
	debug("<br>Stage3 Greedy Download <br>candidate count: $len");

	for ($i = 0; $i < $len; $i++){
	    $url = $urls[$i][0];
	    $rank = $urls[$i][1]; 
	    if($rank < MIN_FILE_SIZE*MIN_FILE_SIZE)
	    	continue;
	    debug("IMG_TAG TRY: rank: $rank  url: $url");
		$result = tryFetchAndResize($url);
		if($result){
			return $result;
		}
	}

	return false;
}


function calcNodeRank($node){
	return 1+_calcNodeRank($node);
}

function _calcNodeRank($node){
	$parent = $node;
	$rank = 1;
	$punish = 0.5;
	for($i=1; $i<10; $i++){
		$parent = $parent->parentNode;
		$rank *= $punish;
		if(!$parent || $parent->nodeName === "body"|| $parent->nodeName === "html"){
			return $rank;
		}

		if($parent->nodeName === "article"){
			return $rank + ARTICLE_BONUS;
		}

		if($parent->nodeName === "figure"){
			return $rank + FIGURE_BONUS;
		}  

		if($parent->nodeName === "p"){
			$rank += PARAG_BONUS;
		}  



		if($parent->nodeName === "a"){
			$rank += LINK_BONUS;
		}
	}
	return 0;
}

function rankDimAttr($tag){
	$imgWidth = min( intval($tag->getAttribute("width")) , 320 );	
	if($imgWidth > 160)
		return 1.5 * $imgWidth/320;

	$imgHeight = min( intval($tag->getAttribute("height")) , 200 );
	if($imgHeight > 100)
	 	return 1.5 * $imgHeight/200;
	return 0;
}


function tryMetaTags($doc){
	// $urls = array();
    $metas = $doc->getElementsByTagName("meta");
	for ($i = 0; $i < $metas->length; $i++){
	    $meta = $metas->item($i);

	    $name = $meta->getAttribute("name");
	    if(empty($name))
	    	$name = $meta->getAttribute('property');
	    if(empty($name))
	    	continue;

	    $imgUrl = $meta->getAttribute("content");
	    if(empty($imgUrl))
	    	continue;

	    if($name == "twitter:image" || $name == "og:image" || $name == "image"){
	    	debug("META: $name $imgUrl ");
	    	$size = fetchFileSize($imgUrl);
	    	if($size > 0 && $size < MIN_FILE_SIZE * 2){
	    		debug("<br>Abort! File too small: $size </br>");
				return;
			}
			$result = tryFetchAndResize($imgUrl);
			if($result){
				return $result;
			}
	    }
	}
	return false;
}

function tryRegexAbs($html){
	$pattern = "!https?://([a-z0-9\-\.\/\_]+\.(?:jpe?g|png|gif))!Ui";

	$m = preg_match_all($pattern,$html,$matches);
	if(!$m){
		return;
	}
    foreach($matches as $match){
    	$imgUrl = $match[0];
    	debug ("REGEX ABS: $imgUrl");
    	if(fetchFileSize($imgUrl) < MIN_FILE_SIZE){
			return;
		}
		$result = tryFetchAndResize($imgUrl);
		if($result){
			return $result;
		}
    }
    return false;
}

function tryRegexRel($html,$base){
	$urls = array();
	$pattern = "!([a-z0-9\-\.\/\_]+\.(?:jpe?g|png|gif))!Ui";
    $m = preg_match_all($pattern,$html,$matches);
	if(!$m){
		return;
	}
    foreach($matches as $match){ 
    	$imgUrl = rel2abs($match[0],$base);
    	debug("REGEX REL: $imgUrl");
    	if(fetchFileSize($imgUrl) < MIN_MIN_FILE_SIZE){
			return;
		}
		$result = tryFetchAndResize($imgUrl);
		if($result){
			return $result;
		}
	}
	return false;
}


function tryFetchAndResize($url){
	if(empty($url)){
		return false;
	}

	$uid = md5('cRoCrAsEc'.$url);
	$rootDir =  $_SERVER['DOCUMENT_ROOT'];
	$tmp = $rootDir."/private/crawler/tmp/$uid.jpg";  //never save user-given files in public folder
	try{
		debug("<br>FETCH & RESIZE: $url");
		downloadImg($url, $tmp); 
	    $image = new \Eventviva\ImageResize($tmp);
		unlink($tmp);
		$image->crop(300, 200, $allow_enlarge=true);
		$image->save($rootDir."/public/img/thumbnails/$uid.jpg", IMAGETYPE_JPEG, $quality=80);
		return $uid;
	} catch(Exception $e){
		unlink($tmp); //delete file if ImageResize fails!
		debug( $e );
	}
	return false;
}


function fetchFileSize($url){
     $ch = curl_init($url);

     curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
     curl_setopt($ch, CURLOPT_HEADER, TRUE);
     curl_setopt($ch, CURLOPT_NOBODY, TRUE);
     curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	 curl_setopt($ch, CURLOPT_MAXREDIRS, 3);       // stop after 3 redirects
     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);     // Disabled SSL Cert checks
     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    // Disabled SSL Cert checks

     curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
     curl_setopt($ch, CURLOPT_TIMEOUT, 4);

     $data = curl_exec($ch);
     $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

     curl_close($ch);

     $size = intval($size);

     //limit image size and therefore download time 
     if($size > MAX_FILE_SIZE){
     	$size = 0;
     }
     return $size;
}



function downloadImg($url,$saveto){
    $ch = curl_init ($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5); 

    $raw=curl_exec($ch);
    curl_close ($ch);
    file_put_contents($saveto, $raw);
}


function sanitizeUrl($url,$base){
	if(strlen($url)>MAX_URL_SIZE)
	    	return false;
	if(preg_match('/^data:.*/' ,$url))
	    	return false;
	if(preg_match('/^\\/\\/.*/' ,$url))
	    	$url = str_replace('//','http://',$url);
	if(!preg_match('/^(http|https):\\/\\/.*/' ,$url))
	    	$url = rel2abs($url,$base);
	return $url;
}


function parseBase($url){
	$host = parse_url($url, PHP_URL_HOST);
	$scheme = parse_url($url, PHP_URL_SCHEME);
	return "http://$host/";
}


function isImageLink($url){
	$path = parse_url($url, PHP_URL_PATH);
	$ext = strtolower(pathinfo($path)['extension']);
	if(!$ext)
		return;
	$supported_image = array('gif','jpg','jpeg','png','gifv');
	if(in_array($ext,$supported_image)){
		return tryFetchAndResize($url);
	}
}

function rel2abs($rel, $base){
    /* return if already absolute URL */
    if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;

    /* queries and anchors */
    if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel;

    /* parse base URL and convert to local variables:
       $scheme, $host, $path */
    extract(parse_url($base));

    /* remove non-directory element from path */
    $path = preg_replace('#/[^/]*$#', '', $path);

    /* destroy path if relative url points to root */
    if ($rel[0] == '/') $path = '';

    /* dirty absolute URL */
    $abs = "$host$path/$rel";

    /* replace '//' or '/./' or '/foo/../' with '/' */
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

    /* absolute URL is ready! */
    return $scheme.'://'.$abs;
}

?>