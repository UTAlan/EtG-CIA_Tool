<?php 
$debug = false;
session_start();
require_once("functions.php");
require_once("config.db.php"); // Specify $baseUrl, $db["username"], $db["password"], and $db["database"]

$con = mysql_connect("127.0.0.1", $db["username"], $db["password"]);
mysql_select_db($db["database"], $con);

// Check Access Permission
$cardCurators = array(9);
$page["access"] = checkAccess($cardCurators);

if($page["access"] == 0 && !$debug) {
	header("Location: ../index.php");
	die('No Access.');
}