<?php
include_once "../class/actions/Comment.php";
session_start();

$vals = json_decode(file_get_contents("php://input"));
$id = $vals->id;
$parent = $vals->parent;
$offset = $vals->offset;

echo json_encode(Comment::getComments($parent, $id, 0));

// Comment::getComments(3, $userid, 0)