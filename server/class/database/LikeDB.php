<?php

require_once "DB.php";
require_once "KarmaDB.php";

class LikeDB extends DB{
    public static function like(int $type, int $id, int $userid, bool $state) : bool{
        $conn = self::getConnection();
        if ($conn->connect_error) return false;

        // queries pre vloženie alebo odstránenie
        if($state){
            $result = $conn->query("INSERT IGNORE INTO ". self::LIKETABLES[$type] ." (". self::LIKETYPES[$type] ."_id, user_id) VALUES ($id, $userid)");
            KarmaDB::addPoints($conn, $userid, "like");
        }else{
            $result = $conn->query("DELETE FROM ". self::LIKETABLES[$type] ." WHERE ".self::LIKETYPES[$type]."_id = $id AND user_id = $userid");
            KarmaDB::removePoints($conn, $userid, "like");
        }

        $conn->close();
        return $result;
    }

    public static function getLike(int $type, int $id, int $userid) : bool{
        $query = "SELECT * FROM ". self::LIKETABLES[$type] ." WHERE ".self::LIKETYPES[$type]."_id = $id AND user_id = $userid";

        $conn = self::getConnection();
        if ($conn->connect_error) return false;

        $result = $conn->query($query);
        $conn->close();

        return $result->num_rows > 0;
    }

    public static function getLikeCount(int $type, int $id) : int{
        $query = "SELECT COUNT(*) AS count FROM ". self::LIKETABLES[$type] ." WHERE ".self::LIKETYPES[$type]."_id = $id";

        $conn = self::getConnection();
        if ($conn->connect_error) return 0;

        $result = $conn->query($query);
        $conn->close();

        return $result->fetch_assoc()["count"];
    }
}
