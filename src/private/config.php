<?php
define("DEBUG_ME", true);

if(DEBUG_ME){
	define("DB_HOST","localhost:8889");
	define("DB_USER","root");
	define("DB_PASS","root");
	define("DB_NAME","test");
	define("ALLOWED_HOST","localhost");
	
	ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);
    error_reporting(E_ALL); 
} else{
	define("DB_HOST","***********");
	define("DB_USER","***********");
	define("DB_PASS","***********");
	define("DB_NAME","***********");
	define("ALLOWED_HOST","***********");
}

define("RECAPTCHA_SECRET","***********");
?>