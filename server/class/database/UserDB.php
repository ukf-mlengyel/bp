<?php

require_once "DB.php";

class UserDB extends DB{
    // TODO: change local login to server login

    public static function addUser($username, $password) : string{
        $conn = self::getConnection();
        if($conn->connect_error) return "Nastala chyba pri pripájaní na databázu: " . $conn->connect_error;

        // dishwasher
        $username = mysqli_real_escape_string($conn, $username);
        $password = mysqli_real_escape_string($conn, $password);

        // vypíšeme chybu ak účet existuje
        if(self::exists($conn, self::USERTABLE, "username", $username)){
            $conn->close();
            return "Účet s týmto menom už existuje.";
        }

        $stmt = $conn->prepare("INSERT INTO " .self::USERTABLE. "(username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $password);

        if($stmt->execute()){
            $stmt->close();
            $conn->close();
            return "Žiadosť o vytvorenie účtu bola odoslaná, počkajte kým bude overená administrátorom.";
        }else{
            $error = $conn->error;
            $stmt->close();
            $conn->close();
            return "Chyba: " . $error;
        }
    }

    public static function updateUserValue($type, $userID, $newValue) : bool{
        // type moze byt image alebo description
        if($type != "description" && $type != "image") return false;

        $conn = self::getConnection();
        if($conn->connect_error) {echo "Nastala chyba pri pripájaní na databázu: " . $conn->connect_error; return false;}

        // nikdy nevis
        $userID = mysqli_real_escape_string($conn, $userID);
        $newValue = mysqli_real_escape_string($conn, $newValue);

        $stmt = $conn->prepare("UPDATE user SET $type=? WHERE id=?");
        $stmt->bind_param("ss", $newValue, $userID);

        $result = $stmt->execute();
        $stmt->close();
        $conn->close();

        return $result;
    }

    public static function getUserList(int $limit, int $offset) : mysqli_result | null{
        $offset *= $limit;

        $conn = self::getConnection();
        if($conn->connect_error) {echo "Nastala chyba pri pripájaní na databázu: " . $conn->connect_error; return null;}

        // WHERE flags & 1 = TRUE vybere iba overené účty
        $stmt = $conn->prepare("SELECT id, username, image, points FROM " . self::USERTABLE . " WHERE flags & 1 ORDER BY points DESC LIMIT ? OFFSET ?;");
        $stmt->bind_param("ii", $limit, $offset);

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $conn->close();

        return $result;
    }

    public static function getUserListArr(int $limit, int $offset) : array{
        $offset *= $limit;

        $conn = self::getConnection();
        if($conn->connect_error) return array();

        // WHERE flags & 1 = TRUE vybere iba overené účty
        $stmt = $conn->prepare("SELECT id, username, image, points FROM " . self::USERTABLE . " WHERE flags & 1 ORDER BY points DESC LIMIT ? OFFSET ?;");
        $stmt->bind_param("ii", $limit, $offset);

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $conn->close();

        $rows = array();
        while($r = $result->fetch_assoc()){
            $rows[] = $r;
        }

        return $rows;
    }

    public static function getUserCount() : int{
        $conn = self::getConnection();
        $result = $conn->query("SELECT COUNT(*) AS count FROM user WHERE flags & 1;")->fetch_array()[0];
        $conn->close();
        return $result;
    }

    public static function getUserLocation(int $userid) : array{
        $conn = self::getConnection();

        // hand sanitizer
        $userid = filter_var($userid, FILTER_SANITIZE_NUMBER_INT);
        $userid = mysqli_escape_string($conn, $userid);

        $result = $conn->query("SELECT preferred_location_lon, preferred_location_lat, preferred_location_name FROM user WHERE id = $userid;")->fetch_assoc();

        $conn->close();

        return $result;
    }

    public static function setUserLocation(int $userid, float $lon, float $lat) : bool{
        $location = self::getLocationFromCoords($lon, $lat);
        if(!str_contains($location, "Slovakia")) return false;

        $conn = self::getConnection();
        if($conn->connect_error){ echo "Nastala chyba pri pripájaní na databázu: " . $conn->connect_error; return false;}

        //prehanam to troska ale nevadi
        $userid = mysqli_real_escape_string($conn, $userid);
        $lon = mysqli_real_escape_string($conn, $lon);
        $lat = mysqli_real_escape_string($conn, $lat);
        $location = mysqli_real_escape_string($conn, $location);

        $stmt = $conn->prepare("UPDATE user SET preferred_location_lon=?, preferred_location_lat=?, preferred_location_name=? WHERE id=?");
        $stmt->bind_param("ddsi", $lon, $lat, $location, $userid);

        $result = $stmt->execute();
        $stmt->close();

        $conn->close();

        return $result == 1;
    }

    public static function resetUserLocation(int $userid) : bool{
        $conn = self::getConnection();
        if($conn->connect_error) return false;

        $userid = mysqli_real_escape_string($conn, $userid);
        $stmt = $conn->prepare("UPDATE user SET preferred_location_lon=NULL, preferred_location_lat=NULL, preferred_location_name=NULL WHERE id=?");
        $stmt->bind_param("i", $userid);

        $stmt->execute();
        $stmt->close();
        $conn->close();

        return true;
    }

    public static function getUserDetails(int $userid) : array | null | false{
        $conn = self::getConnection();
        if($conn->connect_error) {echo "Nastala chyba pri pripájaní na databázu: " . $conn->connect_error; return null;}

        $userid = mysqli_real_escape_string($conn, $userid);
        $stmt = $conn->prepare("SELECT username, image, description, DATE_FORMAT(creation_date, '%d.%m.%Y') AS creation_date, points, flags FROM ". self::USERTABLE ." WHERE id=?");
        $stmt->bind_param("i", $userid);

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $conn->close();

        return $result->fetch_assoc();
    }

    public static function getUserDetailsShort(int $userid) : array | null | false{
        $conn = self::getConnection();
        if($conn->connect_error) {return null;}

        $result = $conn->query("SELECT username AS u, image AS i, DATE_FORMAT(creation_date, '%d.%m.%Y') AS d, points AS p,
        (SELECT COUNT(*) FROM ".self::DUMPTABLE." WHERE user_id = $userid) AS dc, 
        (SELECT COUNT(*) FROM ".self::BINTABLE." WHERE user_id = $userid) AS bc 
        FROM ". self::USERTABLE ." WHERE id=$userid");

        $conn->close();
        return $result->fetch_assoc();
    }

    public static function getUserDescription(int $userid) : string | null{
        $conn = self::getConnection();
        if($conn->connect_error) return "Nastala chyba pri pripájaní na databázu: " . $conn->connect_error;

        $userid = mysqli_real_escape_string($conn, $userid);
        $stmt = $conn->prepare("SELECT description FROM ". self::USERTABLE ." WHERE id=?");
        $stmt->bind_param("i", $userid);

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $conn->close();

        return $result->fetch_assoc()["description"];
    }

    public static function getLoginDetails($username) : array | null | false{
        $conn = self::getConnection();
        if($conn->connect_error) {echo "Nastala chyba pri pripájaní na databázu: " . $conn->connect_error; return null;}

        // dishwasher
        $username = mysqli_real_escape_string($conn, $username);
        $stmt = $conn->prepare("SELECT id, password, image, points, flags FROM ". self::USERTABLE ." WHERE username=?");
        $stmt->bind_param("s", $username);

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $conn->close();

        return $result->fetch_assoc();
    }

    private static function exists($conn, $table, $column, $username) : bool{
        // prepared statement
        $stmt = $conn->prepare("SELECT id FROM $table WHERE $column=?");
        $stmt->bind_param("s", $username);

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result->num_rows > 0;
    }
}