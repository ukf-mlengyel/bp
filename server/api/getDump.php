<?php
include_once "../class/actions/Dump.php";
session_start();

$vals = json_decode(file_get_contents("php://input"));
$id = $vals->id;

echo json_encode(Dump::getDumpShort($id, $_SESSION["user_id"]));