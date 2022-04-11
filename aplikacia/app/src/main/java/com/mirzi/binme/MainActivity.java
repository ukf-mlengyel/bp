package com.mirzi.binme;

import androidx.appcompat.app.AppCompatActivity;

import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import com.android.volley.AuthFailureError;
import com.android.volley.NetworkResponse;
import com.android.volley.ParseError;
import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.Response;
import com.android.volley.toolbox.HttpHeaderParser;
import com.android.volley.toolbox.StringRequest;
import com.mirzi.binme.Helper.BM;
import com.mirzi.binme.Helper.BMRequestQueue;
import com.mirzi.binme.Helper.SessionHelper;
import com.mirzi.binme.Helper.StringRequestSession;

import org.json.JSONException;
import org.json.JSONObject;

import java.io.UnsupportedEncodingException;
import java.nio.charset.StandardCharsets;

public class MainActivity extends AppCompatActivity {

    // hlavna aktivita ktora kontroluje ci je spojenie na server, ak ano tak prihlasi pouzivatela
    RequestQueue queue;
    StringRequest connectRequest;
    String url = BM.SERVER_URL + "/motd.php";

    ImageView connIcon;
    TextView connTitle, connMessage;
    View connSpinner, retryButton;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        queue = BMRequestQueue.getInstance(this).getRequestQueue();

        connIcon = findViewById(R.id.loader_err_icon);
        connTitle = findViewById(R.id.loader_title);
        connMessage = findViewById(R.id.loader_message);
        connSpinner = findViewById(R.id.loader_spinner);
        retryButton = findViewById(R.id.loader_retry_button);

        connectRequest = new StringRequestSession(Request.Method.GET, url, getApplicationContext(),
                response -> {
                    // uspech
                    connSpinner.setVisibility(View.INVISIBLE);
                    connMessage.setText(response);

                    tryLogin();
                },
                error -> {
                    // chyba
                    retryButton.setVisibility(View.VISIBLE);
                    connIcon.setVisibility(View.VISIBLE);
                    connTitle.setText(R.string.error);
                    connMessage.setText(R.string.preload_error);
                    connSpinner.setVisibility(View.INVISIBLE);
                }
        ){
            @Override
            protected Response <String> parseNetworkResponse(NetworkResponse response) {
                try {
                    String jsonString = new String(response.data, HttpHeaderParser.parseCharset(response.headers));

                    // save session id if it doesn't exist
                    if(SessionHelper.getSessionID(getApplicationContext()).equals("")) {
                        String header_response = String.valueOf(response.headers.values());
                        int index1 = header_response.indexOf("PHPSESSID=");
                        int index2 = header_response.indexOf("; path");

                        if (index2 != -1) {
                            String session_id = header_response.substring(index1, index2);
                            SessionHelper.setSessionID(getApplicationContext(), session_id);
                        }
                    }

                    return Response.success(jsonString, HttpHeaderParser.parseCacheHeaders(response));
                } catch (UnsupportedEncodingException e) {
                    return Response.error(new ParseError(e));
                }
            }
        };

        queue.add(connectRequest);
    }

    public void retryConnect(View view){
        retryButton.setVisibility(View.GONE);
        connIcon.setVisibility(View.INVISIBLE);
        connTitle.setText(R.string.app_name);
        connMessage.setText(R.string.preload_connect);
        connSpinner.setVisibility(View.VISIBLE);
        queue.add(connectRequest);
    }

    private void tryLogin(){
        String nick = SessionHelper.getPreference(getApplicationContext(), "nick");
        String pass = SessionHelper.getPreference(getApplicationContext(), "pass");
        if (nick.equals("") || pass.equals("")){
            goToIndex();
        }else{
            String url = BM.SERVER_URL + "/api/login.php";

            StringRequestSession request = new StringRequestSession(Request.Method.POST, url, getApplicationContext(),
                    response -> {
                        Log.i("BM_SERVER_RESPONSE", response);
                        if (response.substring(0,1).equals("1")){
                            String[] temp = response.substring(2).split(";");
                            SessionHelper.setPreference(getApplicationContext(), "user_id", temp[0]);
                            SessionHelper.setPreference(getApplicationContext(), "image", temp[1]);
                            goToHome();
                        }
                        else{
                            Toast.makeText(getApplicationContext(), response, Toast.LENGTH_LONG).show();
                            goToIndex();
                        }
                    }, error -> {
                Toast.makeText(getApplicationContext(), getString(R.string.preload_error), Toast.LENGTH_LONG).show();
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
    }

    public void goToIndex() {
        startActivity(new Intent(this, IndexActivity.class));
        this.finish();
    }

    private void goToHome(){
        Intent intent = new Intent(getApplicationContext(), HomeActivity.class);
        startActivity(intent);
        finishAffinity();
    }
}