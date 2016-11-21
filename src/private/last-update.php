<?
$lastUpdate = 1479699484;
$time = time();
if($time - $lastUpdate < 15){
	die;
}
$str = file_get_contents($_SERVER['DOCUMENT_ROOT']."/private/last-update.php");
$str = str_replace("$lastUpdate", "$time",$str);
file_put_contents($_SERVER['DOCUMENT_ROOT']."/private/last-update.php", $str);
?>