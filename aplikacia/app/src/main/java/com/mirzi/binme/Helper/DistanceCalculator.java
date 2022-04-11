package com.mirzi.binme.Helper;

import java.math.RoundingMode;
import java.text.DecimalFormat;
import java.util.Arrays;

public class DistanceCalculator {
    public static double calculateDistance(double lon1, double lat1, double lon2, double lat2){
        int radius = 6378137;
        double x = Math.PI / 180;

        lon1*=x;
        lon2*=x;
        lat1*=x;
        lat2*=x;

        return radius * (2 * Math.asin(Math.sqrt(Math.pow(Math.sin((lat1 - lat2) / 2), 2) + Math.cos(lat1) * Math.cos(lat2) * Math.pow(Math.sin((lon1 - lon2) / 2), 2))));
    }

    public static String getDistanceStr(double lon1, double lat1, double lon2, double lat2){
        if (lon1 != -1 && lat1 != -1){
            DecimalFormat df = new DecimalFormat("#.#");
            df.setRoundingMode(RoundingMode.CEILING);

            double distance = calculateDistance(lon1, lat1, lon2, lat2);

            return distance > 1000 ? df.format(distance/1000) + "km od vás" : df.format(distance) + "m od vás";
        }
        return "";
    }

    public static String getDistanceStr(double distance){
        DecimalFormat df = new DecimalFormat("#.#");
        df.setRoundingMode(RoundingMode.CEILING);

        return distance > 1000 ? df.format(distance/1000) + "km od vás" : df.format(distance) + "m od vás";
    }
}
