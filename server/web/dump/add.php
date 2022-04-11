<?php
require "../../class/actions/Dump.php";

session_start();
if(isset($_POST["submit"]))
    echo Dump::addDump($_POST["lon"], $_POST["lat"], $_FILES["picture"], $_POST["status"], $_POST["description"]);
