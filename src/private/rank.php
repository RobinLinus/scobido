<?php 
include "last-update.php";

//update ranking
include_once "db.php";
global $mysqli;

$query = "UPDATE posts 
			SET 
			rank_raw= ( 
						  0.3 * ( SELECT IFNULL(sum(users.trust),0) FROM clicks JOIN users ON users.id=clicks.user_id WHERE clicks.post_id = posts.id )
						+ 0.7 * ( SELECT IFNULL(sum( IF(votes.vote=1 ,users.trust , 0-users.trust)),0) FROM votes JOIN users ON users.id=votes.user_id WHERE votes.post_id = posts.id )
					  ),
			age = ( time_to_sec(timediff(CURRENT_TIMESTAMP(), posts.created_at)) / 3600.0 ),
			rank = ( rank_raw / pow(age , 1.3) ),
			rank_new = ( rank_raw / pow(age, 1.8) )";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->close();


//stamp templates
stampTemplate("hot","index");
stampTemplate("new","new");
die;

function stampTemplate($name,$to){
	ob_start();
	require "templates/view-$name-tpl.php";
	$htmlStr = ob_get_contents();
	ob_end_clean(); 
	file_put_contents($_SERVER['DOCUMENT_ROOT']."/public/$to.html", $htmlStr);	
}
?>