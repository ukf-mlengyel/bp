<?php
include_once "../class/database/BinDB.php";
session_start();

$vals = json_decode(file_get_contents("php://input"));
$id = $vals->i;
$limit = $vals->l;

echo json_encode(BinDB::getBinListForUser($id, $limit));