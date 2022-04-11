<?php
include_once "../class/actions/Account.php";
include_once "../class/actions/Bin.php";
session_start();

$vals = json_decode(file_get_contents("php://input"));
$lon = $vals->lon;
$lat = $vals->lat;
$filter = $vals->filter;

Account::setLocation($lon, $lat);
echo Bin::getBinArrShort($lon, $lat, $filter);
