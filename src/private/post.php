<?php
set_time_limit(15);
$url = $_POST['url']; //PROD ensure it's POST!
// $url = $_GET['url']; //PROD ensure it's POST!

if(!isset($url) || filter_var($url, FILTER_VALIDATE_URL) === false){
	exit;
}

include_once('db.php');
require 'crawler/crawler.php';
crawl($url);
include_once('rank.php');
?>