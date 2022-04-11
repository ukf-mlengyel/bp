<?php
require "../class/actions/Dump.php";

$vals = json_decode(file_get_contents("php://input"));
$dumpid = $vals->i;
$lon = $vals->lon;
$lat = $vals->lat;

session_start();
echo Dump::attendCleanup( $dumpid, $_SESSION["user_id"], $lon, $lat);
