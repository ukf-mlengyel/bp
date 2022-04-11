package com.mirzi.binme.Helper;

import android.content.Context;
import android.content.SharedPreferences;

public class SessionHelper {

    public static String getSessionID(Context ctx){
        return getSharedPref(ctx).getString("session_id", "");
    }

    public static void setSessionID(Context ctx, String sessid){
        SharedPreferences.Editor editor = getSharedPref(ctx).edit();
        editor.putString("session_id", sessid);
        editor.apply();
    }

    public static String getPreference(Context ctx, String key){
        return getSharedPref(ctx).getString(key, "");
    }

    public static void setPreference(Context ctx, String key, String value){
        SharedPreferences.Editor editor = getSharedPref(ctx).edit();
        editor.putString(key, value);
        editor.apply();
    }

    public static void clearPreferences(Context ctx){
        SharedPreferences.Editor editor = getSharedPref(ctx).edit();
        editor.clear();
        editor.apply();
    }

    private static SharedPreferences getSharedPref(Context ctx){
        return ctx.getSharedPreferences("com.mirzi.binme.cfg", Context.MODE_PRIVATE);
    }
}
