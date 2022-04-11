<?php
include_once "../class/actions/Dump.php";
session_start();

$vals = json_decode(file_get_contents("php://input"));
$id = $vals->dumpid;

echo json_encode(Dump::getUserAttendance($id, $_SESSION["user_id"]));