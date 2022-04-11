<?php
require "../class/actions/Dump.php";
session_start();

$vals = json_decode(file_get_contents("php://input"));
$dumpid = $vals->i;
$desc = $vals->d;

echo Dump::editDescription( $dumpid, $desc );