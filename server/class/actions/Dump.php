<?php
require_once "Action.php";

// composer and intervention image imports
require_once serverRoot . "vendor/autoload.php";
use Intervention\Image\ImageManagerStatic as Image;

require_once serverRoot . "class/Antiflood.php";
require_once serverRoot . "class/InputValidator.php";
require_once serverRoot . "class/ImageValidator.php";
require_once serverRoot . "class/database/DumpDB.php";

class Dump{
    public static function addDump(float $lon, float $lat, $image, int $status, string $description) : string{
        if (Antiflood::exceedsLimits("add")) return "Zasielate požiadavky príliš často. Skúste to neskôr.";

        // verify data
        /*
        $lon = filter_var($lon, FILTER_VALIDATE_FLOAT);
        $lat = filter_var($lat,FILTER_VALIDATE_FLOAT);
        $status = filter_var($status, FILTER_SANITIZE_NUMBER_INT);
        */
        $description = InputValidator::sanitizeText($description, 1000);

        $errors = ImageValidator::validateImage($image);
        if(!empty($errors)) return $errors;

        // vytvorime unique id pre obrazok
        $uniqid = uniqid();
        $result = DumpDB::addDump($_SESSION["user_id"], $lon, $lat, $uniqid, $description, $status);
        // $result[0] - result, $result[1] - message
        if ($result[0]){
            // ulozime fotku a thumbnail
            Image::make($image["tmp_name"])->fit(1000)->save(serverRoot . "images/dump/$uniqid.jpg", 75, "jpg");
            Image::make($image["tmp_name"])->fit(32)->save(serverRoot . "images/dump_thumb/$uniqid.jpg", 75, "jpg");
            return $result[1];
        }

        return $result[1];
    }

    public static function editDescription(int $id, string $description) : bool{
        if (Antiflood::exceedsLimits("edit_generic")) return false;
        $description = InputValidator::sanitizeText($description, 1000);

        return DumpDB::editDescription($id, $_SESSION["user_id"], $description);
    }

    public static function removeDump(int $id) : bool{
        $dump = DumpDB::getDump($id);
        $result = DumpDB::removeDump($id, $_SESSION["user_id"]);

        $imgid = $dump["image"];
        if($result && !$imgid == "0"){
            unlink(serverRoot . "images/dump/$imgid.jpg");
            unlink(serverRoot . "images/dump_thumb/$imgid.jpg");
        }
        return $result;
    }

    public static function planCleanup(int $id, string $date) : string{
        // overime datum
        if (strtotime("+8 days") < strtotime($date)) return "Čistenie môže byť naplánované najneskôr o týždeň.";
        if (strtotime("+55 minutes") > strtotime($date)) return "Čistenie musí začať aspoň o hodinu.";
        return DumpDB::planCleanup($id, $_SESSION["user_id"], $date);
    }

    public static function setCleaned(int $id, bool $status) : bool{
        return DumpDB::setCleaned($id, $_SESSION["user_id"], $status);
    }

    public static function getDumpArr(float $lon, float $lat, float $range) : string{
        /*
        $lon = filter_var($lon, FILTER_VALIDATE_FLOAT);
        $lat = filter_var($lat,FILTER_VALIDATE_FLOAT);
        $range = filter_var($range,FILTER_VALIDATE_FLOAT);
        */
        if ($range > 50) return DumpDB::getDumpList($lon, $lat, 50);
        return DumpDB::getDumpList($lon, $lat, $range);
    }

    public static function getDumpArrShort(float $lon, float $lat) : string{
        return DumpDB::getDumpListShort($lon, $lat, 10);
    }

    /*
    public static function getDumpArrForUser(int $id) : array{
        return DumpDB::getDumpListForUser($id);
    }*/

    public static function getUserAttendance(int $dumpid, int $userid) : int{
        return DumpDB::getUserAttendance($dumpid, $userid);
    }

    public static function getAttendants(int $dumpid, bool $status) : array{
        return DumpDB::getAttendants($dumpid, $status);
    }

    public static function getAttendantsShort(int $dumpid, bool $status) : array{
        return DumpDB::getAttendantsShort($dumpid, $status);
    }

    public static function toggleAttendance(int $dumpid, int $userid) : bool{
        if (Antiflood::exceedsLimits("generic_request")) return false;
        return DumpDB::toggleAttendance($dumpid, $userid);
    }

    public static function attendCleanup(int $dumpid, int $userid, float $lon, float $lat) : string{
        if (Antiflood::exceedsLimits("generic_request")) return "Zasielate požiadavky moc často, skúste to neskôr.";
        return DumpDB::attendCleanup($dumpid, $userid, $lon, $lat);
    }

    public static function getDump(int $id) : array{
        if (Antiflood::exceedsLimits("generic_request")) return array();
        //$id = filter_var($id, FILTER_VALIDATE_INT);
        return DumpDB::getDump($id);
    }

    public static function getDumpShort(int $id, int $userid) : array{
        if (Antiflood::exceedsLimits("generic_request")) return array();
        //$id = filter_var($id, FILTER_VALIDATE_INT);
        return DumpDB::getDumpShort($id, $userid);
    }

    public static function getCleanupsToAttend() : array{
        if (Antiflood::exceedsLimits("generic_request")) return array();
        return DumpDB::getCleanupsToAttend($_SESSION["user_id"]);
    }
}