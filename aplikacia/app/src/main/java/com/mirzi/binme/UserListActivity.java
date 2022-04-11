package com.mirzi.binme;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;

import android.app.ProgressDialog;
import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.util.Log;
import android.view.View;
import android.widget.AdapterView;
import android.widget.Button;
import android.widget.GridView;
import android.widget.TextView;
import android.widget.Toast;

import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.toolbox.StringRequest;
import com.mirzi.binme.Adapter.DumpBinListAdapter;
import com.mirzi.binme.Adapter.UserListAdapter;
import com.mirzi.binme.Helper.BM;
import com.mirzi.binme.Helper.BMRequestQueue;
import com.mirzi.binme.Helper.SessionHelper;
import com.mirzi.binme.Helper.StringRequestSession;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;

public class UserListActivity extends AppCompatActivity {

    // TODO: načítať ďaľší zoznam na konci

    private GridView userList;
    private UserListAdapter adapter;
    private RequestQueue queue;

    private TextView pageText;
    private Button backButton, forwardButton;

    private int limit = 30;
    private int page = 0;
    private int usercount = 0;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_user_list);

        userList = findViewById(R.id.userList_gridView);
        pageText = findViewById(R.id.userlist_page);
        backButton = findViewById(R.id.userlist_backbutton);
        forwardButton = findViewById(R.id.userlist_forwardbutton);

        queue = BMRequestQueue.getInstance(this).getRequestQueue();

        Toolbar toolbar = findViewById(R.id.home_toolbar);
        toolbar.setTitle(R.string.userlist_title);
        setSupportActionBar(toolbar);
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);

        getUserListForCurrentPage();

        StringRequest userCountRequest = new StringRequest(Request.Method.POST, BM.SERVER_URL+"/api/getUserCount.php",
                response -> {
                    usercount = Integer.parseInt(response);
                    toolbar.setTitle(getString(R.string.userlist_title) + " ("+usercount+")");
                    refreshNavBar();
                }, error -> {
                    Log.e("BM_USERLIST_ERROR", "Could not get user count from server.");
        });

        queue.add(userCountRequest);

        userList.setOnItemClickListener(new AdapterView.OnItemClickListener() {
            @Override
            public void onItemClick(AdapterView<?> adapterView, View view, int i, long l) {
                Intent intent = new Intent(UserListActivity.this, ProfileActivity.class);
                intent.putExtra("id", (String)view.getTag(R.id.id));
                startActivity(intent);
            }
        });
    }

    private void getUserListForCurrentPage(){
        ProgressDialog progress = new ProgressDialog(this);
        progress.setMessage(getString(R.string.loading));
        progress.setCancelable(false);
        progress.show();

        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/getUserList.php", getApplicationContext(),
                response -> {
                    try {
                        Log.i("BM_USERLIST_RESPONSE", "Userlist response: "+response);

                        JSONArray json = new JSONArray(response);
                        if (json.length() != 0){
                            adapter = new UserListAdapter(json, this);
                            userList.setAdapter(adapter);
                            userList.scrollTo(0,0);
                            refreshNavBar();
                        }else{
                            Toast.makeText(getApplicationContext(), R.string.connect_error, Toast.LENGTH_LONG).show();
                        }

                        progress.dismiss();
                    } catch (Exception e) {
                        e.printStackTrace();
                    }
                }, error -> {
            Toast.makeText(getApplicationContext(), getString(R.string.connect_error), Toast.LENGTH_LONG).show();
            progress.dismiss();
            finish();
        }
        ){
            @Override
            public String getBodyContentType() {
                return "application/json; charset=utf-8";
            }

            @Override
            public byte[] getBody(){
                try {
                    JSONObject json = new JSONObject();
                    json.put("l", limit);
                    json.put("o", page);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        queue.add(request);
    }

    private void refreshNavBar(){
        if (page == 0){
            backButton.setVisibility(View.INVISIBLE);
            backButton.setClickable(false);
        }else{
            backButton.setVisibility(View.VISIBLE);
            backButton.setClickable(true);
        }

        if (limit * (page+1) >= usercount){
            forwardButton.setVisibility(View.INVISIBLE);
            forwardButton.setClickable(false);
        }else{
            forwardButton.setVisibility(View.VISIBLE);
            forwardButton.setClickable(true);
        }

        pageText.setText("" + (page+1));
    }

    public void nextPage(View view){
        page++;
        getUserListForCurrentPage();
    }

    public void prevPage(View view){
        page--;
        getUserListForCurrentPage();
    }

    @Override
    public boolean onSupportNavigateUp() {
        onBackPressed();
        return true;
    }

}