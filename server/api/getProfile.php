<?php
include_once "../class/database/UserDB.php";
session_start();

$vals = json_decode(file_get_contents("php://input"));
$id = $vals->i;

echo json_encode(UserDB::getUserDetails($id));