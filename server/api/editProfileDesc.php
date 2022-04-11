<?php
require "../class/actions/Account.php";

$vals = json_decode(file_get_contents("php://input"));
$desc = $vals->d;

session_start();
echo Account::changeDescription( $_SESSION["user_id"], $desc );
