<?php

require_once "DB.php";
require_once "KarmaDB.php";
require_once __DIR__ . "/../../class/Calculator.php";

class DumpDB extends DB {
    public static function addDump(int $id, float $lon, float $lat, string $image, string $description, int $status) : array{
        $location = self::getLocationFromCoords($lon, $lat);
        if(!str_contains($location, "Slovakia")) return array(false, "Skládka sa musí nachádzať na Slovensku. <br> Zvolili ste: " . $location);

        $conn = self::getConnection();
        if($conn->connect_error) return array(false, "Nastala chyba pri pripájaní na databázu: " . $conn->connect_error);

        $description = mysqli_real_escape_string($conn, $description);
        $stmt = $conn->prepare("INSERT INTO ". self::DUMPTABLE ."(user_id, location_lon, location_lat, location_name, image, description, status) VALUES (?,?,?,?,?,?,?);");
        $stmt->bind_param("iddsssi", $id, $lon, $lat, $location, $image, $description, $status);

        $result = $stmt->execute();
        $dumpid = $conn->insert_id;
        $stmt->close();

        if ($result){
            KarmaDB::addPoints($conn, $id, "add_dump");
            $conn->close();
            return array(true, "1;".$dumpid);
        }else{
            $conn->close();
            return array(false, "Nastala chyba pri komunikácii s databázou.");
        }
    }

    public static function setCleaned(int $id, int $userid, bool $status) : bool{
        $newstatus = $status ? 3 : 0;

        // zistime ci je spravne user id
        $conn = self::getConnection();
        if(!self::userValid($conn, $id, $userid)) return false;

        $result = $conn->query("UPDATE ".self::DUMPTABLE." SET status = $newstatus WHERE id = $id");
        $conn->close();
        return $result;
    }

    public static function removeDump(int $id, int $userid) : bool{
        // zistime ci je spravne user id
        $conn = self::getConnection();
        if(!self::userValid($conn, $id, $userid)) return false;

        // zmazeme cistiace akcie
        $conn->query("DELETE FROM ".self::CLEANUPATTENDTABLE." WHERE dump_id = $id");

        // zmazeme liky na komentare
        $conn->query("DELETE cl FROM ".self::COMMENTLIKETABLE." cl 
                           INNER JOIN ".self::COMMENTTABLE." c ON c.id = cl.comment_id
                           WHERE c.parent_type = 0 AND c.parent_id = $id");
        // zmazeme komentare
        $conn->query("DELETE FROM ".self::COMMENTTABLE." WHERE parent_type = 0 AND parent_id = $id");

        // zmazeme liky
        $conn->query("DELETE FROM ".self::DUMPLIKETABLE." WHERE dump_id = $id");

        // zmazeme dump
        $result = $conn->query("DELETE FROM ".self::DUMPTABLE." WHERE id = $id");

        // odobereme body
        KarmaDB::removePoints($conn, $userid, "add_dump");

        $conn->close();

        return $result;
    }

    public static function editDescription(int $id, int $userid, string $description) : bool{
        // zistime ci je spravne user id
        $conn = self::getConnection();
        if(!self::userValid($conn, $id, $userid)) return false;

        $description = mysqli_real_escape_string($conn, $description);
        $stmt = $conn->prepare("UPDATE ".self::DUMPTABLE." SET description = ? WHERE id = ?");
        $stmt->bind_param("si", $description, $id);

        $result = $stmt->execute();
        $stmt->close();
        $conn->close();

        return $result;
    }

    public static function planCleanup(int $id, int $userid, string $date) : bool{
        // zistime ci je spravne user id
        $conn = self::getConnection();
        if(!self::userValid($conn, $id, $userid)) return false;

        //zistime ci uz nie je naplanovane cistenie
        if($conn->query("SELECT status FROM ".self::DUMPTABLE." WHERE id = $id")->fetch_array()[0] == 1) return false;

        $date = mysqli_real_escape_string($conn, $date);
        $stmt = $conn->prepare("UPDATE ".self::DUMPTABLE." SET planned_cleanup = ?, status = 1 WHERE id = ?");
        $stmt->bind_param("si", $date, $id);

        $result = $stmt->execute();
        $stmt->close();
        $conn->close();

        return $result;
    }

    public static function getDumpList(float $lon, float $lat, float $range) : string{
        $range_lon = $range * 0.01;
        $range_lat = $range * 0.005;

        $conn = self::getConnection();
        if($conn->connect_error) return "";

        $query = "
            SELECT d.id, d.location_lon, d.location_lat, d.image, d.status 
            FROM ".self::DUMPTABLE." d
            INNER JOIN ".self::USERTABLE." u ON d.user_id = u.id
            WHERE d.status != 3
            AND u.flags & 2
            AND $lon - d.location_lon BETWEEN -$range_lon AND $range_lon
            AND $lat - d.location_lat BETWEEN -$range_lat AND $range_lat;
        ";

        $result = $conn->query($query);

        $rows = array();
        while($r = $result->fetch_assoc()){
            $rows[] = $r;
        }
        $conn->close();
        return json_encode($rows);
    }

    public static function getDumpListShort(float $lon, float $lat) : string{
        $range_lon = 0.1;
        $range_lat = 0.05;

        $conn = self::getConnection();
        if($conn->connect_error) return "";

        $query = "
            SELECT d.id AS i, d.location_lon AS lon, d.location_lat AS lat, d.status AS s 
            FROM ".self::DUMPTABLE." d
            INNER JOIN ".self::USERTABLE." u ON d.user_id = u.id
            WHERE d.status != 3
            AND u.flags & 2
            AND $lon - d.location_lon BETWEEN -$range_lon AND $range_lon
            AND $lat - d.location_lat BETWEEN -$range_lat AND $range_lat;
        ";

        $result = $conn->query($query);

        $rows = array();
        while($r = $result->fetch_assoc()){
            $rows[] = $r;
        }
        $conn->close();
        return json_encode($rows);
    }

    public static function getDumpListForUser(int $id, bool $limit) : array{
        $conn = self::getConnection();
        if($conn->connect_error) return array();

        $limit_results = $limit ? "LIMIT 8" : "";

        $query = "
            SELECT id, location_name, image, status, DATE_FORMAT(creation_date, '%d.%m.%Y') AS creation_date
            FROM ".self::DUMPTABLE."
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

    public static function getDump(int $id) : array{
        $query = "
            SELECT d.id, d.location_lon, d.location_lat, d.location_name,
                   d.image, d.description, DATE_FORMAT(d.creation_date, '%d.%m.%Y, %H:%i') AS creation_date, DATE_FORMAT(d.planned_cleanup, '%d.%m.%Y, %H:%i') AS planned_cleanup,
                   d.user_id, d.status, u.username, u.image AS user_image
            FROM ".self::DUMPTABLE." d
            INNER JOIN ".self::USERTABLE." u
            ON d.user_id = u.id
            WHERE d.id = $id;
        ";

        $conn = self::getConnection();
        $result = $conn->query($query);
        $conn->close();

        return $result->fetch_assoc() ?? array();
    }

    public static function getDumpShort(int $id, int $userid) : array{
        $query = "
            SELECT d.id, d.location_lon, d.location_lat, d.location_name,
                   d.image, d.description, DATE_FORMAT(d.creation_date, '%d.%m.%Y, %H:%i') AS creation_date, DATE_FORMAT(d.planned_cleanup, '%d.%m.%Y, %H:%i') AS planned_cleanup,
                   d.user_id, d.status, u.username, u.image AS user_image,
                   (SELECT COUNT(*) FROM ". self::LIKETABLES[0] ." WHERE ".self::LIKETYPES[0]."_id = $id AND user_id = $userid) AS isliked,
                   (SELECT COUNT(*) AS count FROM ". self::LIKETABLES[0] ." WHERE ".self::LIKETYPES[0]."_id = $id) AS likecount
            FROM ".self::DUMPTABLE." d
            INNER JOIN ".self::USERTABLE." u
            ON d.user_id = u.id
            WHERE d.id = $id;
        ";

        $conn = self::getConnection();
        $result = $conn->query($query);
        $conn->close();

        return $result->fetch_assoc() ?? array();
    }

    public static function getUserAttendance(int $dumpid, int $userid) : int{
        $conn = self::getConnection();
        $result = $conn->query("SELECT attendance FROM ". self::CLEANUPATTENDTABLE." WHERE dump_id = $dumpid AND user_id = $userid");
        $conn->close();

        return $result->num_rows == 0 ? -1 : $result->fetch_array()[0];
    }

    public static function toggleAttendance(int $dumpid, int $userid) : bool{
        // ospravedlnujem sa tomu kto toto bude po mne citat ale fakt uz nemam energiu aby som vsetko pisal lepsim sposobom >_<
        $conn = self::getConnection();

        $result = match (self::getUserAttendance($dumpid, $userid)) {
            -1 => $conn->query("INSERT INTO " . self::CLEANUPATTENDTABLE . " (user_id, dump_id, attendance) VALUES ($userid, $dumpid, 1)"),
            0 => $conn->query("UPDATE " . self::CLEANUPATTENDTABLE . " SET attendance = 1 WHERE dump_id = $dumpid AND user_id = $userid"),
            2 => $conn->query("UPDATE " . self::CLEANUPATTENDTABLE . " SET attendance = 3 WHERE dump_id = $dumpid AND user_id = $userid"),

            1 => $conn->query("UPDATE " . self::CLEANUPATTENDTABLE . " SET attendance = 0 WHERE dump_id = $dumpid AND user_id = $userid"),
            3 => $conn->query("UPDATE " . self::CLEANUPATTENDTABLE . " SET attendance = 2 WHERE dump_id = $dumpid AND user_id = $userid"),
        };

        $conn->close();
        return $result;
    }

    private static function userValid($conn, string $id, string $userid){
        $dumpuid = $conn->query("SELECT user_id FROM ".self::DUMPTABLE." WHERE id = $id")->fetch_array()[0];
        return $dumpuid == $userid;
    }

    public static function getAttendants(int $dumpid, bool $status) : array{
        $conn = self::getConnection();
        if($status){
            $result = $conn->query( "SELECT uc.user_id, u.username 
                                            FROM ".self::CLEANUPATTENDTABLE." uc
                                            INNER JOIN ".self::USERTABLE." u ON uc.user_id = u.id
                                            WHERE uc.dump_id = $dumpid AND uc.attendance IN (2,3)");
        }else{
            $result = $conn->query( "SELECT uc.user_id, u.username 
                                            FROM ".self::CLEANUPATTENDTABLE." uc
                                            INNER JOIN ".self::USERTABLE." u ON uc.user_id = u.id
                                            WHERE uc.dump_id = $dumpid AND uc.attendance IN (1,3)");
        }
        $conn->close();
        $rows = array();
        while($r = $result->fetch_assoc()){
            $rows[] = $r;
        }
        return $rows;
    }

    public static function getAttendantsShort(int $dumpid, bool $status) : array{
        $conn = self::getConnection();
        if($status){
            $result = $conn->query( "SELECT u.username AS u
                                            FROM ".self::CLEANUPATTENDTABLE." uc
                                            INNER JOIN ".self::USERTABLE." u ON uc.user_id = u.id
                                            WHERE uc.dump_id = $dumpid AND uc.attendance IN (2,3)");
        }else{
            $result = $conn->query( "SELECT u.username AS u
                                            FROM ".self::CLEANUPATTENDTABLE." uc
                                            INNER JOIN ".self::USERTABLE." u ON uc.user_id = u.id
                                            WHERE uc.dump_id = $dumpid AND uc.attendance IN (1,3)");
        }
        $conn->close();
        $rows = array();
        while($r = $result->fetch_assoc()){
            $rows[] = $r;
        }
        return $rows;
    }

    public static function attendCleanup(int $dumpid, int $userid, float $lon, float $lat) : string{
        $dump = self::getDump($dumpid);

        // ak je uzivatel dalej ako 50m
        if (Calculator::calculateDistance($dump["location_lon"], $dump["location_lat"], $lon, $lat) > 50) return "Pre potvrdenie prítomnosti musíte byť najviac 50m od skládky.";

        // ak uz cistenie neprebieha
        if ($dump["status"] != 2) return "Čistenie momentálne neprebieha.";

        $attendance = self::getUserAttendance($dumpid, $userid);
        $conn = self::getConnection();
        // ak uzivatel este nedostal body za ucast
        if ($attendance < 2) KarmaDB::addPoints($conn, $userid, "cleanup_attendance");

        match ($attendance) {
            -1 => $conn->query("INSERT INTO " . self::CLEANUPATTENDTABLE . " (user_id, dump_id, attendance) VALUES ($userid, $dumpid, 2)"),
            0, 3, 1 => $conn->query("UPDATE " . self::CLEANUPATTENDTABLE . " SET attendance = 2 WHERE dump_id = $dumpid AND user_id = $userid"),
        };

        return "1";
    }

    public static function getCleanupsToAttend(int $userid) : array{
        $conn = self::getConnection();
        $query = "
            SELECT uc.dump_id AS id, d.location_name, d.image, d.status, d.planned_cleanup AS creation_date
            FROM ".self::CLEANUPATTENDTABLE." uc
            INNER JOIN ".self::DUMPTABLE." d ON d.id = uc.dump_id
            WHERE uc.user_id = $userid 
            AND d.status IN(1,2) 
            AND uc.attendance IN(1,3)
            ORDER BY d.planned_cleanup DESC;
        ";
        $result = $conn->query($query);
        $conn->close();

        $rows = array();
        while($r = $result->fetch_assoc()){
            $rows[] = $r;
        }
        return $rows;
    }
}