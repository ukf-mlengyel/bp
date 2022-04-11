<?php

require_once "Action.php";

require_once serverRoot . "class/Antiflood.php";
require_once serverRoot . "class/database/LikeDB.php";

class Like{
    public static function like(int $type, int $id, bool $state) : bool{
        if(Antiflood::exceedsLimits("like")) return false;
        return LikeDB::like($type, $id, $_SESSION["user_id"], $state);
    }

    public static function getLike(string $type, int $id) : bool{
        return LikeDB::getLike($type, $id, $_SESSION["user_id"]);
    }

    public static function getLikeCount(int $type, int $id) : int{
        return LikeDB::getLikeCount($type, $id);
    }
}
