<?php
class InputValidator{
    // static funkcie na overovanie údajov ktoré zadal uživateľ

    // meno
    public static function validateName($input) : string{
        $errString = "";

        // dlzka mena
        if(strlen($input) < 4) $errString .= "Meno musí obsahovať aspoň 4 znaky. <br>";
        else if(strlen($input) > 24) $errString .= "Meno je príliš dlhé (24 znakov limit). <br>";

        //povolene znaky
        if(!self::allowedChars($input)) $errString .= "Meno obsahuje nepovolený znak. <br>";

        return $errString;
    }

    // heslo
    public static function validatePassword($input) : string{
        $errString = "";

        // dlzka
        if(strlen($input) < 8) $errString .= "Heslo musí obsahovať aspoň 8 znakov. <br>";
        else if(strlen($input) > 32) $errString .= "Maximálna dĺžka hesla je 32 znakov. <br>";

        // povolene znaky
        // if(!self::allowedChars($input)) $errString .= "Heslo obsahuje nepovolený znak. <br>";

        return $errString;
    }

    // popis
    public static function sanitizeText(string $input, int $len) : string{
        $input = filter_var($input,FILTER_SANITIZE_STRING);
        return substr($input, 0, $len);
    }

    private static function allowedChars($input) : bool{
        return preg_match("/^[a-zA-Z0-9-' ]*$/",$input);
    }
}