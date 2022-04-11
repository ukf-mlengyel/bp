<?php
require "../class/actions/Dump.php";

session_start();
echo Dump::addDump($_POST["lon"], $_POST["lat"], $_FILES["picture"], 0, $_POST["description"]);
