<?php
class Antiflood{
    // limity requestov
    const LIMITS = array(
        "register" => 3600,
        "login" => 5,
        "add" => 30,
        "like" => 1,
        "comment" => 10,
        "get_dumpmap" => 5,
        "get_userlist" => 5,
        "get_user" => 2,
        "edit_profilepic" => 120,
        "edit_generic" => 10,
        "edit_location" => 30,
        "generic_request" => 1
    );

    const ANTIFLOOD_ENABLE = true;

    public static function exceedsLimits(string $requestType) : bool{
        if(!self::ANTIFLOOD_ENABLE) return false;

        if (!isset($_SESSION[$requestType])){                                       // ak hodnota neexistuje v session
            $_SESSION[$requestType] = time() + self::LIMITS[$requestType];
            return false;
        }else if ($_SESSION[$requestType]>time()){                                  // ak existuje a prekracuje limity
            // mal som tu doteraz kod ktory jednoducho oneskoril request ale
            // neslo to tak dobre jak som cakal
            /*
            if($requestType = "like") return true;                                  // specialny pripad - pre liky nechceme obmedzovat requesty
            if(self::LIMITS[$requestType] <= 10){                                   // ak je limit kratky tak len oneskorime request
                sleep($_SESSION[$requestType] - time());
                $_SESSION[$requestType] = time() + self::LIMITS[$requestType];
                return false;
            }*/
            return true;
        }
        else{
            $_SESSION[$requestType] = time() + self::LIMITS[$requestType];          // ak existuje ale neprekracuje limity
            return false;
        }
    }

    public static function registerAllowed() : bool{
        return true;
    }
}