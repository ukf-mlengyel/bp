<?php
include_once "../class/database/DumpDB.php";
session_start();

$vals = json_decode(file_get_contents("php://input"));
$id = $vals->i;
$limit = $vals->l;

echo json_encode(DumpDB::getDumpListForUser($id, $limit));