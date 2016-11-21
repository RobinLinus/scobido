<?php 
include_once 'libs/vendor/autoload.php';
include 'crawl-image.php';
include 'crawl-title.php';

function crawl($url){
	if(urlInDB($url)){
		abort('already crawled!');
	}
	$res = get_web_page($url);
	if(!$res){
		abort('no response!');
	}
	$html = $res[0];
	$effUrl = $res[1];

	if(isSpam($html)){
		abort('this is spam!!');
	}

	$hash = md5($html);
	if(responseInDB($effUrl,$hash)){
		abort('already crawled!!');
	}


	// file_put_contents('crawler-dump.txt',$html); //Prod
	$doc = new DOMDocument();
	@$doc->loadHTML($html);
	$title = crawlTitle($doc,$url);
	$img = crawlImage($html,$doc,$effUrl);
	
	$url = htmlspecialchars($url);

	$ip =  $_SERVER['REMOTE_ADDR'];

	debug("<h3>title: $title<br><a href='$url' href='_blank'>url: $url</a><br> <img src='/img/thumbnails/$img.jpg'></h3>"); //PROD
	global $mysqli;
	$sql = "CALL post(?,?,?,?,?);";
	$stmt = $mysqli->prepare($sql);
	$stmt->bind_param("sssss",$ip,$title,$effUrl,$img,$hash);
	$stmt->execute();
}

function isSpam($html){
	$badwords = ["porn","ass fuck","white pride"];
	$numBadwords = 0;
	$html = strtolower($html);
	foreach ($badwords as $badword) {
		$numBadwords += substr_count($html, $badword);
	}
	return $numBadwords > 10;
}

function urlInDB($url){
	global $mysqli;
	$sql = "SELECT true FROM posts WHERE url=?";
	$stmt = $mysqli->prepare($sql);
	$stmt->bind_param("s",$url);
	$stmt->execute();
	$res = $stmt->fetch();
	return $res;
}

function responseInDB($url,$hash){
	global $mysqli;
	$sql = "SELECT true FROM posts WHERE url=? OR hash=?";
	$stmt = $mysqli->prepare($sql);
	$stmt->bind_param("ss",$url,$hash);
	$stmt->execute();
	$res = $stmt->fetch();
	return $res;
}

function get_web_page( $url ){
	    $options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "UTF8",       // handle all encodings
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_CONNECTTIMEOUT => 10,      // timeout on connect
	        CURLOPT_TIMEOUT        => 10,      // timeout on response
	        CURLOPT_MAXREDIRS      => 5,       // stop after 3 redirects
	        CURLOPT_SSL_VERIFYPEER => 1,     // Disabled SSL Cert checks
	        CURLOPT_SSL_VERIFYHOST => 2,     // Disabled SSL Cert checks
	        CURLOPT_COOKIESESSION => true,     // Disabled SSL Cert checks
	        CURLOPT_COOKIEJAR => '',     // Disabled SSL Cert checks
	        CURLOPT_USERAGENT => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.87 Safari/537.36",
	    );

	    $ch      = curl_init( $url );
	    curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );

	    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if($httpCode < 500 && $httpCode >= 400) {
    		return;
		}

	   	$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

	    curl_close( $ch );

	    if($err || !$content){
	    	debug($errmsg);
	    	return;
	    }

	    $header['content'] = $content;
    return [$content,$effectiveUrl];
}

?>