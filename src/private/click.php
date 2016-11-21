<?php
// TODO: trust url in db?
// TODO: trust ip?
// TODO: do not count clicks from tor browsers

$url = $_GET['url'];
$ip = $_SERVER['REMOTE_ADDR'];

if (empty($url) || empty($ip)) {
	header("Location: /shooot.html");
	die;
}

include_once "db.php";

global $mysqli;
$query = "SELECT id FROM posts WHERE url=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $url);
$stmt->execute();
$stmt->bind_result($post_id);
$stmt->fetch();
$stmt->close();
if($post_id){
	header("Cache-control: max-age=2592000");
	header("Location: ".$url);
	$query2 = "CALL click(?,?);";
	$stmt2 = $mysqli->prepare($query2);
	$stmt2->bind_param("si", $ip, $post_id);
	$stmt2->execute();
}
die;
?>