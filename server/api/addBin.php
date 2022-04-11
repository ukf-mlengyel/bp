<?php
require "../class/actions/Bin.php";

session_start();
echo Bin::addBin($_POST["lon"], $_POST["lat"], $_FILES["picture"], $_POST["types"], $_POST["description"]);
