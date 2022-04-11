<?php
require "../class/actions/Bin.php";
session_start();

$vals = json_decode(file_get_contents("php://input"));
$binid = $vals->i;
$desc = $vals->d;

echo Bin::editDescription( $binid, $desc );