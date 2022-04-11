package com.mirzi.binme;

import androidx.appcompat.app.AppCompatActivity;

import android.content.Intent;
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
import com.mirzi.binme.Helper.SessionHelper;
import com.mirzi.binme.Helper.StringRequestSession;

import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;

public class LoginActivity extends AppCompatActivity {
    RequestQueue queue;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        queue = BMRequestQueue.getInstance(this.getApplicationContext()).getRequestQueue();
    }

    public void login(View view){
        // get nick/pass
        String nick = ((TextView)findViewById(R.id.loginNick)).getText().toString();
        String pass = ((TextView)findViewById(R.id.loginPass)).getText().toString();

        String url = BM.SERVER_URL + "/api/login.php";

        StringRequestSession request = new StringRequestSession(Request.Method.POST, url, getApplicationContext(),
                response -> {
                    if (response.substring(0,1).equals("1")){
                        SessionHelper.setPreference(getApplicationContext(), "nick", nick);
                        SessionHelper.setPreference(getApplicationContext(), "pass", pass);

                        String[] temp = response.substring(2).split(";");
                        SessionHelper.setPreference(getApplicationContext(), "user_id", temp[0]);
                        SessionHelper.setPreference(getApplicationContext(), "image", temp[1]);

                        goToHome();
                    }
                    else{
                        Toast.makeText(getApplicationContext(), response, Toast.LENGTH_LONG).show();
                        Log.i("BM_SERVER_RESPONSE", response);
                    }
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

    private void goToHome(){
        Intent intent = new Intent(getApplicationContext(), HomeActivity.class);
        //intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP);
        startActivity(intent);
        finishAffinity();
    }

    public void back(View view) {
        onBackPressed();
    }
}