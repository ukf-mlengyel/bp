<?php
require_once "../class/actions/Like.php";

session_start();

$vals = json_decode(file_get_contents("php://input"));
$type = $vals->t;
$id = $vals->i;
$state = $vals->s;

echo Like::like($type, $id, $state);

