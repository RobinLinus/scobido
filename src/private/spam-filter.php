<?php
/******* Answer Request imediatly to hide internals of processing *******/

ob_end_clean();
header("Location: /sweeet\r\n");
header("Connection: close\r\n");
header("Content-Encoding: none\r\n");
ignore_user_abort(true); // optional
ob_start();
echo ('');
$size = ob_get_length();
header("Content-Length: $size");
ob_end_flush();     // Strange behaviour, will not work
flush();            // Unless both are called !
ob_end_clean();

/*****************************************/


$recaptchaResponse = $_POST['g-recaptcha-response'];

if (!isset($recaptchaResponse)){
	header('Location: /shooot.html');
    die;
}

$url = $_POST['url'];

if(!isset($url) || filter_var($url, FILTER_VALIDATE_URL) === false){
	header('Location: /shooot.html');
	exit;
}

include_once 'libs/vendor/autoload.php';
include_once 'config.php';
$recaptcha = new \ReCaptcha\ReCaptcha(RECAPTCHA_SECRET);

$resp = $recaptcha->verify($recaptchaResponse, $_SERVER['REMOTE_ADDR']);

if (!$resp->isSuccess()){
	die;
}

include "classifier/domain/classify.php";
$spamP = spamProbability($url);
if($spamP > 0.9){
	// header('Location: /shooot.html#spam!');
	die;
}

include "libs/tor-detector.php";
if(isTorRequest()){
	die;
}

include_once "db.php";
global $mysqli;
$ip = $_SERVER["REMOTE_ADDR"];
$query = "SELECT count(*) FROM posts WHERE user_id=getUserByIp(?)";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $ip);
$stmt->execute();
$stmt->bind_result($postsCount);
$stmt->fetch();
$stmt->close();
if($postsCount >= 2){
	// header('Location: /shooot.html#Too%20many%20posts!');
	die;
}

include_once 'post.php';
?>