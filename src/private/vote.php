<?php

$id = $_POST['id'];
$vote = $_POST['vote'];
$ip = $_SERVER['REMOTE_ADDR'];

if (empty($id) || empty($ip) || !isset($vote)) {
	die;
}
$vote = intval($vote);

include_once 'db.php';
global $mysqli;
$query = "CALL vote(?,?,?)";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("sid", $ip, $id, $vote);
$stmt->execute();

?>