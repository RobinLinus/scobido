<?php

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

switch ($path) {
    case "/click":
    	endpoint("GET","click");
        break;
    case "/post":
    	endpoint("POST","spam-filter");
        break;
    case "/vote":
        endpoint("POST","vote");
        break;
    case "/rank":
        endpoint("GET","rank");
        break;
}

header("Location: /shooot.html");
die;

function endpoint($method,$endpoint){
    if($method != $_SERVER["REQUEST_METHOD"]){
        return;
    }

    //Helps agains CSRF?
    set_include_path(get_include_path() . PATH_SEPARATOR . "private");
    include "config.php";
    $host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if(substr($host, 0 - strlen(ALLOWED_HOST)) != ALLOWED_HOST) {
        header("Location: /");
        die;
    }

    include $endpoint.".php";
    die;
}
?>