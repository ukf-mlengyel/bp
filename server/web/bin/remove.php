<?php
require_once "../../class/actions/Bin.php";

session_start();

$vals = json_decode(file_get_contents("php://input"));
$id = $vals->i;

echo Bin::removeBin($id);