package com.mirzi.binme.Helper;

import android.content.Context;

import androidx.annotation.Nullable;

import com.android.volley.AuthFailureError;
import com.android.volley.Response;
import com.android.volley.toolbox.StringRequest;

import java.util.Collections;
import java.util.HashMap;
import java.util.Map;

public class StringRequestSession extends StringRequest {
    Context ctx;
    public StringRequestSession(int method, String url, Context ctx, Response.Listener<String> listener, @Nullable Response.ErrorListener errorListener) {
        super(method, url, listener, errorListener);
        this.ctx = ctx;
    }

    public Map<String, String> getHeaders() throws AuthFailureError {
        Map<String, String> headers = super.getHeaders();

        // add session id to header if it exists
        String sessid = SessionHelper.getSessionID(ctx);

        if(!sessid.equals("")){
            if(headers == null || headers.equals(Collections.emptyMap())) {
                headers = new HashMap<String, String>();
            }
            headers.put("Cookie", sessid);
        }

        return headers;
    }

}
