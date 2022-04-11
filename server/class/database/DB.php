<?php
class DB{
    const SERVER = "localhost";
    const USERNAME = "root";
    const PASSWORD = "";
    const DBNAME = "bin_me";

    const USERTABLE = "user";
    const DUMPTABLE = "dump";
    const BINTABLE = "bin";

    const COMMENTTABLE = "comment";
    const NOTIFICATIONTABLE = "notification";

    const CLEANUPATTENDTABLE = "user_attends_cleanup";

    const DUMPLIKETABLE = "dump_liked_by_user";
    const BINLIKETABLE = "bin_liked_by_user";
    const COMMENTLIKETABLE = "comment_liked_by_user";

    const LIKETABLES = [
        self::DUMPLIKETABLE,
        self::BINLIKETABLE,
        self::COMMENTLIKETABLE
    ];

    const LIKETYPES = [
        "dump",
        "bin",
        "comment"
    ];

    const serverRoot = __DIR__ . "/../../";

    const MAPBOX_ACCESS_TOKEN = "pk.eyJ1IjoibWlyemkiLCJhIjoiY2t2djFmemd4MTcyOTJwbTlpd2tsb3dtMiJ9.KrkpLWMACeL0d_SRk1UjZQ";

    protected static function getLocationFromCoords(float $lon, float $lat) : string{
        // ziskame nazov zo suradnic pomocou mapbox geolocation api
        $json = json_decode(file_get_contents("https://api.mapbox.com/geocoding/v5/mapbox.places/$lon,$lat.json?access_token=" . self::MAPBOX_ACCESS_TOKEN), true);

        // vratime country code a nazov lokacie
        // moze nastat situacia kedy je json prazdny (napr ked klikneme na ocean), vtedy volbu ignorujeme
        return !empty($json["features"]) ? $json["features"]["0"]["place_name"] : "";
    }

    protected static function getConnection() : mysqli{
        // mysql connect
        return new mysqli(
            self::SERVER,
            self::USERNAME,
            self::PASSWORD,
            self::DBNAME
        );
    }
}