<?php
require_once "../class/actions/Comment.php";

session_start();

$vals = json_decode(file_get_contents("php://input"));
$type = $vals->t;
$id = $vals->i;
$content = $vals->c;

$result = Comment::addComment($type, $id, $content);

echo $result[0] .",". $result[1];

