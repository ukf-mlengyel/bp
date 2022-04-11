<?php

require_once "DB.php";
require_once "KarmaDB.php";

class BinDB extends DB {
    public static function addBin(int $id, float $lon, float $lat, string $image, string $description, int $types) : array{
        $location = self::getLocationFromCoords($lon, $lat);
        if(!str_contains($location, "Slovakia")) return array(false, "Kôš sa musí nachádzať na Slovensku. <br> Zvolili ste: " . $location);

        $conn = self::getConnection();
        if($conn->connect_error) return array(false, "Nastala chyba pri pripájaní na databázu: " . $conn->connect_error);

        $description = mysqli_real_escape_string($conn, $description);
        $stmt = $conn->prepare("INSERT INTO ". self::BINTABLE ."(user_id, location_lon, location_lat, location_name, image, description, types) VALUES (?,?,?,?,?,?,?);");
        $stmt->bind_param("iddsssi", $id, $lon, $lat, $location, $image, $description, $types);

        $result = $stmt->execute();
        $binid = $conn->insert_id;
        $stmt->close();

        if ($result){
            KarmaDB::addPoints($conn, $id, "add_bin");
            $conn->close();
            return array(true, "1;".$binid);
        }else {
            $conn->close();
            return array(false, "Nastala chyba pri komunikácii s databázou.");
        }
    }

    public static function removeBin(int $id, int $userid) : bool{
        // zistime ci je spravne user id
        $conn = self::getConnection();
        if(!self::userValid($conn, $id, $userid)) return false;

        // zmazeme liky na komentare
        $conn->query("DELETE cl FROM ".self::COMMENTLIKETABLE." cl 
                           INNER JOIN ".self::COMMENTTABLE." c ON c.id = cl.comment_id
                           WHERE c.parent_type = 1 AND c.parent_id = $id");
        // zmazeme komentare
        $conn->query("DELETE FROM ".self::COMMENTTABLE." WHERE parent_type = 1 AND parent_id = $id");

        // zmazeme liky
        $conn->query("DELETE FROM ".self::BINLIKETABLE." WHERE bin_id = $id");

        // zmazeme dump
        $result = $conn->query("DELETE FROM ".self::BINTABLE." WHERE id = $id");

        // odobereme body
        KarmaDB::removePoints($conn, $userid, "add_bin");

        $conn->close();

        return $result;
    }

    public static function editDescription(int $id, int $userid, string $description) : bool{
        // zistime ci je spravne user id
        $conn = self::getConnection();
        if(!self::userValid($conn, $id, $userid)) return false;

        $description = mysqli_real_escape_string($conn, $description);
        $stmt = $conn->prepare("UPDATE ".self::BINTABLE." SET description = ? WHERE id = ?");
        $stmt->bind_param("si", $description, $id);

        $result = $stmt->execute();
        $stmt->close();
        $conn->close();

        return $result;
    }

    public static function changeTypes(int $id, int $types, int $userid) : bool{
        // zistime ci je spravne user id
        $conn = self::getConnection();
        if(!self::userValid($conn, $id, $userid)) return false;
        $result = $conn->query("UPDATE ".self::BINTABLE." SET types = $types WHERE id = $id");
        $conn->close();
        return $result;
    }

    public static function getBinList(float $lon, float $lat, float $range, int $filter) : string{
        $range_lon = $range * 0.01;
        $range_lat = $range * 0.005;

        $conn = self::getConnection();
        if($conn->connect_error) return "";

        $query = "
            SELECT b.id, b.location_lon, b.location_lat, b.image, b.types
            FROM ".self::BINTABLE." b
            INNER JOIN ".self::USERTABLE." u ON b.user_id = u.id
            WHERE u.flags & 4
            AND $lon - b.location_lon BETWEEN -$range_lon AND $range_lon
            AND $lat - b.location_lat BETWEEN -$range_lat AND $range_lat";

        if ($filter < 255 && $filter > 0){
            // bitwise magic ;)
            $query.=" AND types & $filter = $filter";
        }

        $result = $conn->query($query);

        $rows = array();
        while($r = $result->fetch_assoc()){
            $rows[] = $r;
        }
        $conn->close();
        return json_encode($rows);
    }

    public static function getBinListShort(float $lon, float $lat, float $range, int $filter) : string{
        $range_lon = $range * 0.01;
        $range_lat = $range * 0.005;

        $conn = self::getConnection();
        if($conn->connect_error) return "";

        $query = "
            SELECT b.id AS i, b.location_lon AS lon, b.location_lat AS lat
            FROM ".self::BINTABLE." b
            INNER JOIN ".self::USERTABLE." u ON b.user_id = u.id
            WHERE u.flags & 4
            AND $lon - b.location_lon BETWEEN -$range_lon AND $range_lon
            AND $lat - b.location_lat BETWEEN -$range_lat AND $range_lat";

        if ($filter < 255 && $filter > 0){
            // bitwise magic ;)
            $query.=" AND types & $filter = $filter";
        }

        $result = $conn->query($query);

        $rows = array();
        while($r = $result->fetch_assoc()){
            $rows[] = $r;
        }
        $conn->close();
        return json_encode($rows);
    }

    public static function getBinListForUser(int $id, bool $limit) : array{
        $conn = self::getConnection();
        if($conn->connect_error) return array();

        $limit_results = $limit ? "LIMIT 8" : "";

        $query = "
            SELECT id, location_name, image, types, DATE_FORMAT(creation_date, '%d.%m.%Y') AS creation_date
            FROM ".self::BINTABLE."
            WHERE user_id = $id
            ORDER BY creation_date DESC
        " . $limit_results;

        $result = $conn->query($query);
        $conn->close();

        $rows = array();
        while($r = $result->fetch_assoc()){
            $rows[] = $r;
        }
        return $rows;
    }

    public static function getBin(int $id) : array{
        $query = "
            SELECT b.id, b.location_lon, b.location_lat, b.location_name,
                   b.image, b.description, b.types, DATE_FORMAT(b.creation_date, '%d.%m.%Y, %H:%i') AS creation_date,
                   b.user_id, u.username, u.image AS user_image
            FROM ".self::BINTABLE." b
            INNER JOIN ".self::USERTABLE." u
            ON b.user_id = u.id
            WHERE b.id = $id;
        ";

        $conn = self::getConnection();
        $result = $conn->query($query);
        $conn->close();

        return $result->fetch_assoc() ?? array();
    }

    public static function getBinShort(int $id) : array{
        $query = "
            SELECT b.id, b.location_lon, b.location_lat, b.location_name,
                   b.image, b.description, b.types, DATE_FORMAT(b.creation_date, '%d.%m.%Y, %H:%i') AS creation_date,
                   b.user_id, u.username, u.image AS user_image,
                   (SELECT COUNT(*) FROM ". self::LIKETABLES[1] ." WHERE ".self::LIKETYPES[1]."_id = $id AND user_id = b.user_id) AS isliked,
                   (SELECT COUNT(*) AS count FROM ". self::LIKETABLES[1] ." WHERE ".self::LIKETYPES[1]."_id = $id) AS likecount
            FROM ".self::BINTABLE." b
            INNER JOIN ".self::USERTABLE." u
            ON b.user_id = u.id
            WHERE b.id = $id;
        ";

        $conn = self::getConnection();
        $result = $conn->query($query);
        $conn->close();

        return $result->fetch_assoc() ?? array();
    }

    private static function userValid($conn, string $id, string $userid){
        $binuid = $conn->query("SELECT user_id FROM ".self::BINTABLE." WHERE id = $id")->fetch_array()[0];
        return $binuid == $userid;
    }
}