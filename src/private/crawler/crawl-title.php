<?php

include_once 'crawler-helper.php';

function crawlTitle($doc,$url){
	if ($title = crawlTitleRaw($doc,$url)){
		$title = \ForceUTF8\Encoding::toUTF8($title);
		$title = truncateTitle($title);
		return htmlspecialchars($title);
	}
	return DEFAULT_TITLE;
}

function crawlTitleRaw($doc,$url){
	if ($title = crawlArticleHeading($doc))
		return $title;
	if ($title = crawlMetaTags($doc))
		return $title;
	if ($title = crawlTagName($doc,'h1'))
		return $title;
	if ($title = crawlTagName($doc,'title'))
		return $title;
	if ($title = crawlTagName($doc,'h2'))
		return $title;
	if ($title = crawlTagName($doc,'h3'))
		return $title;
	if ($title = crawlTitleInUrl($url))
		return $title;
}

function crawlArticleHeading($doc){
	if($article = $doc->getElementsByTagName('article')->item(0))
		return $article->getElementsByTagName('h1')->item(0)->nodeValue;
}

function crawlTagName($doc,$tagName){
	if($tag = $doc->getElementsByTagName($tagName)->item(0))
		return $tag->nodeValue;
}

function crawlMetaTags($doc){
	//in reversed order 
	$tagNames = ['description','og:description','title','twitter:title','og:title'];
	$metas = $doc->getElementsByTagName('meta');
	$metaTitles = array();
	for ($i = 0; $i < $metas->length; $i++){
	    $meta = $metas->item($i);
	    $name = $meta->getAttribute('name');
	    if(empty($name))
	    	$name = $meta->getAttribute('property');
	    $content = $meta->getAttribute('content');
	    if(empty($content))
	    	continue;
	    if($rank = array_search($name,$tagNames)){
	    	array_push($metaTitles,[$content,$rank]);
	    	debug("META TITLE: $name rank: $rank title: $content <br>");
	    }
	}
	if(!count($metaTitles))
		return false;
	
	sortByRank($metaTitles);

	return $metaTitles[0][0];
}

function crawlTitleInUrl($url){
	$path = ucfirst(basename(parse_url($url, PHP_URL_PATH)));
	if($path){
		$path = str_replace('-',' ',$path);
		$path = str_replace('.html','',$path);
		return $path;
	}
}

function truncateTitle($title){
	if(mb_strwidth($title, 'UTF-8') > MAX_TITLE_SIZE){
		$title = mb_strimwidth($title,0,MAX_TITLE_SIZE).'...';
	}	
	return $title;
}

?>