<?php

require_once "Action.php";

// composer and intervention image imports
require_once serverRoot . "vendor/autoload.php";
use Intervention\Image\ImageManagerStatic as Image;

require_once serverRoot . "class/Antiflood.php";
require_once serverRoot . "class/InputValidator.php";
require_once serverRoot . "class/ImageValidator.php";
require_once serverRoot . "class/database/UserDB.php";

class Account{
    // funkcia na vytvorenie účtu
    public static function createAccount($username, $password) : string{
        if (!Antiflood::registerAllowed()) return "Registrácie sú momentálne uzatvorené.";

        $errors = InputValidator::validateName($username);
        $errors .= InputValidator::validatePassword($password);

        // ak sa nenašli chyby vytvoríme účet
        if(empty($errors)){
            // overime ci to je spam
            if (Antiflood::exceedsLimits("register")) return "Požiadavka už bola zaslaná.";

            // hashujeme heslo
            $password = password_hash($password, PASSWORD_DEFAULT);
            return UserDB::addUser($username, $password);
        }else{
            return $errors;
        }
    }

    public static function login(string $username, string $password, bool $redirect) : string{
        $errors = InputValidator::validateName($username);
        $errors .= InputValidator::validatePassword($password);

        $loginErrString = "Zadané údaje sú neplatné.";

        if(empty($errors)){
            if (Antiflood::exceedsLimits("login")) return "Zasielate požiadavky príliš často. Skúste to neskôr.";

            // ziskame udaje
            $loginresult = UserDB::getLoginDetails($username);

            // ak sa nenaslo v databaze
            if(!isset($loginresult)) return $loginErrString;

            // overime heslo
            if( password_verify($password, $loginresult["password"]) ){
                // zistíme či je účet aktivovaný (flag 1)
                if(!($loginresult["flags"] & 1))return "Účet ešte nebol overený, skúste sa prihlásiť neskôr.";
                // spustime session
                $_SESSION["user_id"] = $loginresult["id"];
                $_SESSION["username"] = $username;
                $_SESSION["userpicture"] = $loginresult["image"];
                if($redirect)header('Location: index.php');
                return "1;".$loginresult["id"].";".$loginresult["image"];
            } else return $loginErrString;

        }else return $errors;
    }

    public static function changeProfilePicture($image) : string{
        $errors = ImageValidator::validateImage($image);
        if(!empty($errors)) return $errors;

        // SPRACUJEME A ULOZIME OBRAZOK

        // adjust for 200x200
        /*
        $img = Image::make($image["tmp_name"])
            ->resize(
                200,
                200,
                function($constraint){
                    $constraint->aspectRatio();
                    $constraint->upsize();
                }
        );
        */

        if (Antiflood::exceedsLimits("edit_profilepic")) return "Zasielate požiadavky príliš často. Skúste to neskôr.";

        // ulozime 200x200 profilovku a 50x50 thumbnail
        $img = Image::make($image["tmp_name"])->fit(200);
        $img_thumb = Image::make($image["tmp_name"])->fit(50);

        // vytvorime unique id pre obrazok
        $uniqid = uniqid();
        $img->save(serverRoot . "images/user/$uniqid.jpg", 75, "jpg");
        $img_thumb->save(serverRoot . "images/user_thumb/$uniqid.jpg", 75, "jpg");

        // ULOZIME NOVE ID DO DATABAZY
        if(!UserDB::updateUserValue("image", $_SESSION["user_id"], $uniqid))
            return "Chyba pri komunikácii s databázou, skúste to neskôr.";

        // ODSTRANIME STARY OBRAZOK
        $oldid = $_SESSION["userpicture"];
        if(!$oldid == 0){
            unlink(serverRoot . "images/user/$oldid.jpg");
            unlink(serverRoot . "images/user_thumb/$oldid.jpg");
        }

        // ZMENIME NAZOV OBRAZKU PRE SESSION
        $_SESSION["userpicture"] = $uniqid;

        return "Profilová fotka bola zmenená.";
    }

    public static function removeProfilePicture() : string{
        // ULOZIME NOVE ID DO DATABAZY
        if(!UserDB::updateUserValue("image", $_SESSION["user_id"], 0))
            return "Chyba pri komunikácii s databázou, skúste to neskôr.";

        $imgid = $_SESSION["userpicture"];
        if(!$imgid == "0"){
            unlink(serverRoot . "images/user/$imgid.jpg");
            unlink(serverRoot . "images/user_thumb/$imgid.jpg");
        }

        $_SESSION["userpicture"] = 0;
        return "Profilová fotka bola odstránená.";
    }

    public static function changeDescription($userid, $text) : string{
        if (Antiflood::exceedsLimits("edit_generic")) return "Zasielate požiadavky príliš často. Skúste to neskôr.";
        return UserDB::updateUserValue(
            "description",
            $userid,
            InputValidator::sanitizeText($text, 1000)
        ) == 1 ? "Popis bol upravený." : "Nastala chyba pri úprave popisu.";
    }

    public static function getLocation() : array{
        return UserDB::getUserLocation($_SESSION["user_id"]);
    }

    public static function setLocation(float $lon, float $lat) : bool{
        $lon = filter_var($lon, FILTER_VALIDATE_FLOAT);
        $lat = filter_var($lat, FILTER_VALIDATE_FLOAT);
        return UserDB::setUserLocation($_SESSION["user_id"], $lon, $lat);
    }

    public static function resetLocation() : bool{
        if (Antiflood::exceedsLimits("edit_location")) return "Zasielate požiadavky príliš často. Skúste to neskôr.";
        return UserDB::resetUserLocation($_SESSION["user_id"]);
    }
}
