<?php

require_once "DB.php";

// koľko bodov uživateľ obdrží za akcie
const POINTS = array(
    "add_dump" => 5,
    "add_bin" => 5,
    "like" => 1,
    "cleanup_attendance" => 100,
);

class KarmaDB extends DB{
    public static function addPoints($conn, int $userid, string $type){
        if(!array_key_exists($type, POINTS)) return;
        $points = POINTS[$type];

        $conn->query("UPDATE ".self::USERTABLE." SET points = points + $points WHERE id = $userid");
    }

    public static function removePoints($conn, int $userid, string $type){
        if(!array_key_exists($type, POINTS)) return;
        $points = POINTS[$type];

        $conn->query("UPDATE ".self::USERTABLE." SET points = points - $points WHERE id = $userid");
    }
}
