<?php
include_once "../class/database/UserDB.php";
session_start();

echo UserDB::getUserDescription($_SESSION["user_id"]);