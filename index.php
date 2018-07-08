<?php
if(isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https") {
	$_SERVER["HTTPS"] = "on";
	$_SERVER["SERVER_PORT"] = "443";
}

// Uncomment this line if you must temporarily take down your site for maintenance.
// require ".maintenance.php";

require_once __DIR__."/vendor/autoload.php";

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// Add Nette
require_once __DIR__."/app/bootstrap.php";

