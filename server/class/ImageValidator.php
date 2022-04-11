<?php
class ImageValidator{
    public static function validateImage($image) : string{
        // 32MB
        $maxSize = 32000000;
        $allowedExtensions = ["png", "jpg", "jpeg"];

        $extension = strtolower(pathinfo($image["name"],PATHINFO_EXTENSION));

        // TODO: check php.ini for POST file size limits

        if(!getimagesize($image["tmp_name"])) return "Súbor nie je obrázok.";
        if($image["size"] > $maxSize) return "Obrázok je moc veľký.";
        if(!self::checkExtension($extension, $allowedExtensions)) return "Nepodporovaný formát.";

        return "";
    }

    private static function checkExtension($imageExtension, $extensions) : bool{
        foreach($extensions as $ext){
            if($imageExtension == $ext) return true;
        }
        return false;
    }
}