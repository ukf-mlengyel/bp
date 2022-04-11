<?php
require_once "Action.php";

// composer and intervention image imports
require_once serverRoot . "vendor/autoload.php";
use Intervention\Image\ImageManagerStatic as Image;

require_once serverRoot . "class/Antiflood.php";
require_once serverRoot . "class/InputValidator.php";
require_once serverRoot . "class/ImageValidator.php";
require_once serverRoot . "class/database/BinDB.php";

class Bin{
    public static function getBinArr(float $lon, float $lat, float $range, int $filter) : string{
        if ($range > 10) return DumpDB::getDumpList($lon, $lat, 10);
        return BinDB::getBinList($lon, $lat, $range, $filter);
    }

    public static function getBinArrShort(float $lon, float $lat, int $filter) : string{
        return BinDB::getBinListShort($lon, $lat, 10, $filter);
    }

    public static function getBin(int $id) : array{
        if (Antiflood::exceedsLimits("generic_request")) return array();
        return BinDB::getBin($id);
    }

    public static function getBinShort(int $id) : array{
        if (Antiflood::exceedsLimits("generic_request")) return array();
        return BinDB::getBinShort($id);
    }

    public static function editDescription(int $id, string $description) : bool{
        if (Antiflood::exceedsLimits("edit_generic")) return false;
        $description = InputValidator::sanitizeText($description, 1000);

        return BinDB::editDescription($id, $_SESSION["user_id"], $description);
    }

    public static function changeTypes(int $id, int $types) : bool{
        if (Antiflood::exceedsLimits("edit_generic")) return false;

        return BinDB::changeTypes($id, $types, $_SESSION["user_id"]);
    }

    public static function removeBin(int $id) : bool{
        $bin = BinDB::getBin($id);
        $result = BinDB::removeBin($id, $_SESSION["user_id"]);

        $imgid = $bin["image"];
        if($result && !$imgid == "0"){
            unlink(serverRoot . "images/bin/$imgid.jpg");
            unlink(serverRoot . "images/bin_thumb/$imgid.jpg");
        }
        return $result;
    }

    public static function addBin(float $lon, float $lat, $image, int $types, string $description) : string{
        if (Antiflood::exceedsLimits("add")) return "Zasielate požiadavky príliš často. Skúste to neskôr.";
        if ($types > 255 || $types <= 0) return "Nebol zvolený typ koša.";

        $description = InputValidator::sanitizeText($description, 1000);

        $errors = ImageValidator::validateImage($image);
        if(!empty($errors)) return $errors;

        // vytvorime unique id pre obrazok
        $uniqid = uniqid();
        $result = BinDB::addBin($_SESSION["user_id"], $lon, $lat, $uniqid, $description, $types);
        // $result[0] - result, $result[1] - message
        if ($result[0]){
            // ulozime fotku a thumbnail
            Image::make($image["tmp_name"])->fit(1000)->save(serverRoot . "images/bin/$uniqid.jpg", 75, "jpg");
            Image::make($image["tmp_name"])->fit(32)->save(serverRoot . "images/bin_thumb/$uniqid.jpg", 75, "jpg");
        }

        return $result[1];
    }
}
