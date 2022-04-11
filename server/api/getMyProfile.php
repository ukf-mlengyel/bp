<?php
include_once "../class/database/UserDB.php";
session_start();

if(isset($_SESSION["user_id"])) echo json_encode(UserDB::getUserDetailsShort($_SESSION["user_id"]));