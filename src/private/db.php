<?php
session_set_cookie_params(3600 * 24 * 30, "/");
session_start();

include_once "config.php";

global $mysqli;
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_errno) {
    abort("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
}

if (!$mysqli->set_charset("utf8")) {
    abort("Error loading character set utf8: ". $mysqli->error);
}

function debug($string){
    if(DEBUG_ME){
         echo $string."<br>";
    }
}

function abort($string=""){
    debug($string);
    if(DEBUG_ME){
        echo "<br><br><br> die!!! <br><br><br>";
    } else {
        header("Location: /shooot.html");
        die;
    }
}

?>