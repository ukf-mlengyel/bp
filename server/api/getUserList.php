<?php
include_once "../class/database/UserDB.php";
session_start();

$vals = json_decode(file_get_contents("php://input"));
$limit = $vals->l;
$offset = $vals->o;

echo json_encode(UserDB::getUserListArr($limit, $offset));