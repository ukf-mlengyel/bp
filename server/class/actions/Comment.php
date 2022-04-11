<?php

require_once "Action.php";

require_once serverRoot . "class/Antiflood.php";
require_once serverRoot . "class/InputValidator.php";
require_once serverRoot . "class/database/CommentDB.php";

class Comment{
    public static function addComment(int $parent_type, int $parent_id, string $content) : array{
        if($parent_type > 3 || $parent_type < 0) return array(false, 0);
        if(empty($content)) return array(false, 0);
        if(Antiflood::exceedsLimits("comment")) return array(false, 0);
        return CommentDB::addComment($parent_type, $parent_id, $_SESSION["user_id"], InputValidator::sanitizeText($content, 300));
    }

    public static function deleteComment(int $id) : bool{
        if(Antiflood::exceedsLimits("generic_request")) return false;
        return CommentDB::removeComment($id, $_SESSION["user_id"]);
    }

    public static function getComments(int $parent_type, int $parent_id, int $offset) : array{
        if($parent_type > 3 || $parent_type < 0) return array();
        $userid = $_SESSION["user_id"] ?? 0;
        return CommentDB::getComments($parent_type, $parent_id, $userid, $offset);
    }
}