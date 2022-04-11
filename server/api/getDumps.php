<?php
include_once "../class/actions/Account.php";
include_once "../class/actions/Dump.php";
session_start();

$vals = json_decode(file_get_contents("php://input"));
$lon = $vals->lon;
$lat = $vals->lat;

Account::setLocation($lon, $lat);
echo Dump::getDumpArrShort($lon, $lat);
