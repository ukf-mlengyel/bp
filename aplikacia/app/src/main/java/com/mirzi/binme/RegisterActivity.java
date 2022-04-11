package com.mirzi.binme;

import androidx.appcompat.app.AppCompatActivity;

import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.TextView;
import android.widget.Toast;

import com.android.volley.AuthFailureError;
import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.mirzi.binme.Helper.BM;
import com.mirzi.binme.Helper.BMRequestQueue;
import com.mirzi.binme.Helper.StringRequestSession;

import org.json.JSONException;
import org.json.JSONObject;
import java.nio.charset.StandardCharsets;

public class RegisterActivity extends AppCompatActivity {
    RequestQueue queue;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_register);

        queue = BMRequestQueue.getInstance(this.getApplicationContext()).getRequestQueue();
    }

    public void register(View view){
        // get nick/pass
        String nick = ((TextView)findViewById(R.id.registerNick)).getText().toString();
        String pass = ((TextView)findViewById(R.id.registerPass)).getText().toString();

        String url = BM.SERVER_URL + "/api/createAccount.php";

        StringRequestSession request = new StringRequestSession(Request.Method.POST, url, getApplicationContext(),
                response -> {
                    Toast.makeText(getApplicationContext(), response, Toast.LENGTH_LONG).show();
                    Log.i("BM_SERVER_RESPONSE", response);
                }, error -> {
                    Toast.makeText(getApplicationContext(), getString(R.string.connect_error), Toast.LENGTH_LONG).show();
                }
        ){
            @Override
            public String getBodyContentType() {
                return "application/json; charset=utf-8";
            }

            @Override
            public byte[] getBody() throws AuthFailureError {
                try {
                    JSONObject json = new JSONObject();
                    json.put("username", nick);
                    json.put("password", pass);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        queue.add(request);

    }


    public void back(View view) {
        onBackPressed();
    }
}