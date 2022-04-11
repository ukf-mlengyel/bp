package com.mirzi.binme.Helper;

import android.graphics.Bitmap;
import android.graphics.Matrix;
import android.graphics.RectF;

public class BM {
    public static final String SERVER_URL = "http://192.168.1.3/bin-me-server";

    public static Bitmap scaleBitmap(Bitmap targetBmp, int reqHeightInPixels, int reqWidthInPixels) {
        Matrix matrix = new Matrix();
        matrix .setRectToRect(new RectF(0, 0, targetBmp.getWidth(), targetBmp.getHeight()), new RectF(0, 0, reqWidthInPixels, reqHeightInPixels), Matrix.ScaleToFit.CENTER);
        return Bitmap.createBitmap(targetBmp, 0, 0, targetBmp.getWidth(), targetBmp.getHeight(), matrix, true);
    }
}
