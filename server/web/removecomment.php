<?php
require_once "../class/actions/Comment.php";

session_start();

$id = json_decode(file_get_contents("php://input"))->i;

echo Comment::deleteComment($id);