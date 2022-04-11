<?php
include_once "../class/actions/Dump.php";
session_start();

echo json_encode(Dump::getCleanupsToAttend());