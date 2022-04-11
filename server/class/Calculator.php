<?php
class Calculator{
    public static function calculateDistance($lon1, $lat1, $lon2, $lat2) : float{
        $radius = 6378137;
        $x = M_PI / 180;

        $lat1 *= $x; $lon1 *= $x; $lat2 *= $x; $lon2 *= $x;

        return $radius * (2 * asin(sqrt(pow(sin(($lat1 - $lat2) / 2), 2) + cos($lat1) * cos($lat2) * pow(sin(($lon1 - $lon2) / 2), 2))));
    }
}
