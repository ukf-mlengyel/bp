<?php
include_once "../class/actions/Account.php";
session_start();

$vals = json_decode(file_get_contents("php://input"));
$username = $vals->username;
$password = $vals->password;

echo Account::login($username, $password, false);
