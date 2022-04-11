<?php
include_once "../class/actions/Dump.php";
session_start();

$vals = json_decode(file_get_contents("php://input"));
$id = $vals->dumpid;
$status = $vals->status;

echo json_encode(Dump::getAttendantsShort($id, $status));