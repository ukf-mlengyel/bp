<?php
require "../class/actions/Dump.php";

$vals = json_decode(file_get_contents("php://input"));
$dumpid = $vals->i;

session_start();
echo Dump::toggleAttendance( $dumpid, $_SESSION["user_id"] );
