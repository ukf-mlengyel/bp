<?php
require "../class/actions/Dump.php";

$vals = json_decode(file_get_contents("php://input"));
$id = $vals->i;
$date = $vals->d;

session_start();
echo Dump::planCleanup($id, $date);