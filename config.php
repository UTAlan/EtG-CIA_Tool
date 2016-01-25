<?php 
$debug = true;
session_start();
require_once("config.db.php"); // Specify $baseUrl, $db_config["username"], $db_config["password"], and $db_config["database"]
$db = new mysqli($db_config['hostname'], $db_config["username"], $db_config["password"], $db_config['database']);
require_once("functions.php");

// Check Access Permission
$cardCurators = array(9);
$page["access"] = checkAccess($cardCurators);
if($page["access"] == 0 && !$debug) {
	header("Location: ../index.php");
	die('No Access.');
}