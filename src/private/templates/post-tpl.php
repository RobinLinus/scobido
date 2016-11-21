<?php 

function postTemplate($id,$url,$title,$img,$time,$clicks,$upvotes,$downvotes,$inlineImg=false){
	if($img=="placeholder"){
        $img = "public/img/thumbnails/p/p".( $id % 8).".png";
    } else {
        $img = "public/img/thumbnails/".$img.".jpg";
    }
	$host = parse_url($url, PHP_URL_HOST);
    $host = str_replace("www.","",$host);
	$time = time_elapsed_string($time);
    $url = urlencode($url);

    // if($inlineImg){
    //     include_once "libs/image-resize.php";
    //     $img = new \Eventviva\ImageResize($img);
    //     $img = "data:img/jpg;base64,".base64_encode($img->getImageAsString());
    // }

	echo <<<EOT
		<div class="card">
			<a href="/click?url=$url" target="_blank">
				<img src="$img" class="image">
    			<div class="card-content">
    				<div class="title">$title</div>
    				<div class="details">$time | $host</div>
    			</div>
            </a>
			<div class="card-actions" title="Vote up.">
				<img class="icon" src="/img/icons/views.svg"> $clicks 
				<div class="vote on">
					<div id="vote-$id-up">
                        <svg fill="#373d3f" height="16" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0 0h24v24H0z" fill="none"/>
                            <path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-1.91l-.01-.01L23 10z"/>
                        </svg> 
                        <span>$upvotes</span>
                    </div>
					<div id="vote-$id-down" title="Vote down.">
                        <svg fill="#373d3f" height="16" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0 0h24v24H0z" fill="none"/>
                            <path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v1.91l.01.01L1 14c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z"/>
                        </svg> 
                        <span>$downvotes</span>
                    </div>
				</div>
			</div>
		</div>
EOT;
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'y',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'h',
        'i' => 'm',
        's' => 's',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . '' . $v; //. ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>