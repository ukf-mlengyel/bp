<?php
include_once "../class/actions/Bin.php";
session_start();

$vals = json_decode(file_get_contents("php://input"));
$id = $vals->id;

echo json_encode(Bin::getBinShort($id));