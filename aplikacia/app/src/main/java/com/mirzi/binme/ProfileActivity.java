package com.mirzi.binme;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;

import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.AdapterView;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.Toast;

import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.mirzi.binme.Adapter.CommentAdapter;
import com.mirzi.binme.Adapter.DumpBinListAdapter;
import com.mirzi.binme.Helper.BM;
import com.mirzi.binme.Helper.BMRequestQueue;
import com.mirzi.binme.Helper.SessionHelper;
import com.mirzi.binme.Helper.StringRequestSession;
import com.squareup.picasso.Picasso;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;

public class ProfileActivity extends AppCompatActivity {

    private int user_id;
    RequestQueue queue;
    DumpBinListAdapter dumpListAdapter, binListAdapter;
    CommentAdapter commentAdapter;
    ListView dumpList, binList, commentsList;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_profile);

        Toolbar toolbar = findViewById(R.id.home_toolbar);
        toolbar.setTitle(R.string.profile_title);
        setSupportActionBar(toolbar);
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);

        Intent intent = getIntent();
        user_id = Integer.parseInt(intent.getStringExtra("id"));
        queue = BMRequestQueue.getInstance(this).getRequestQueue();

        dumpList = findViewById(R.id.profile_dumplist);
        binList = findViewById(R.id.profile_binlist);
        commentsList = findViewById(R.id.profile_comments);

        getProfile();
    }

    private void getProfile(){
        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/getProfile.php", getApplicationContext(),
                response -> {
                    try {
                        Log.i("BM_PROFILE_RESPONSE", "Profile response: "+response);

                        JSONObject json = new JSONObject(response);

                        // images
                        Picasso.get().load(BM.SERVER_URL+"/images/user/"+json.getString("image")+".jpg").into((ImageView)findViewById(R.id.profile_img));

                        // text
                        ((TextView)findViewById(R.id.profile_name)).setText(json.getString("username"));
                        ((TextView)findViewById(R.id.profile_points)).setText(json.getString("points"));
                        ((TextView)findViewById(R.id.profile_date)).setText(String.format(getString(R.string.member_since), json.getString("creation_date")));

                        String desc = json.getString("description");
                        ((TextView)findViewById(R.id.profile_description)).setText(desc == "null" ? "" : desc);

                    } catch (Exception e) {
                        e.printStackTrace();
                        Toast.makeText(getApplicationContext(), getString(R.string.user_doesnt_exist), Toast.LENGTH_LONG).show();
                        finish();
                    }
                }, error -> {
            Toast.makeText(getApplicationContext(), getString(R.string.connect_error), Toast.LENGTH_LONG).show();
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
                    json.put("i", user_id);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        StringRequestSession dumpListRequest = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/getDumpList.php", getApplicationContext(),
                response -> {
                    try {
                        Log.i("BM_PROFILE_RESPONSE", "Dumplist response: "+response);

                        JSONArray json = new JSONArray(response);

                        if (json.length() != 0){
                            dumpListAdapter = new DumpBinListAdapter(json, this, true, getString(R.string.dump_date_prefix));
                            dumpList.setAdapter(dumpListAdapter);
                        }else{
                            findViewById(R.id.profile_section_dumps).setVisibility(View.GONE);
                            findViewById(R.id.profile_section_dumps_divider).setVisibility(View.GONE);
                        }


                    } catch (Exception e) {
                        e.printStackTrace();
                    }
                }, error -> {
            Toast.makeText(getApplicationContext(), getString(R.string.connect_error), Toast.LENGTH_LONG).show();
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
                    json.put("i", user_id);
                    json.put("l", true);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        StringRequestSession binListRequest = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/getBinList.php", getApplicationContext(),
                response -> {
                    try {
                        Log.i("BM_PROFILE_RESPONSE", "Binlist response: "+response);

                        JSONArray json = new JSONArray(response);
                        if (json.length() != 0){
                            binListAdapter = new DumpBinListAdapter(json, this, false, getString(R.string.bin_date_prefix));
                            binList.setAdapter(binListAdapter);
                        }else{
                            findViewById(R.id.profile_section_bins).setVisibility(View.GONE);
                            findViewById(R.id.profile_section_bins_divider).setVisibility(View.GONE);
                        }
                    } catch (Exception e) {
                        e.printStackTrace();
                    }
                }, error -> {
            Toast.makeText(getApplicationContext(), getString(R.string.connect_error), Toast.LENGTH_LONG).show();
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
                    json.put("i", user_id);
                    json.put("l", true);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        StringRequestSession commentsRequest = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/getComments.php", getApplicationContext(),
                response -> {
                    try {
                        Log.i("BM_PROFILE_RESPONSE", "Comments response: "+response);

                        JSONArray json = new JSONArray(response);
                        commentAdapter = new CommentAdapter(json, this);
                        commentsList.setAdapter(commentAdapter);
                    } catch (Exception e) {
                        e.printStackTrace();
                    }
                }, error -> {
            Toast.makeText(getApplicationContext(), getString(R.string.connect_error), Toast.LENGTH_LONG).show();
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
                    json.put("id", user_id);
                    json.put("parent", 3);
                    json.put("offset", 0);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        queue.add(request);
        queue.add(dumpListRequest);
        queue.add(binListRequest);
        queue.add(commentsRequest);

        dumpList.setOnItemClickListener(new AdapterView.OnItemClickListener() {
            @Override
            public void onItemClick(AdapterView<?> adapterView, View view, int i, long l) {
                Intent intent = new Intent(ProfileActivity.this, DumpViewActivity.class);
                intent.putExtra("id", (int)view.getTag(R.id.id));
                startActivity(intent);
            }
        });

        binList.setOnItemClickListener(new AdapterView.OnItemClickListener() {
            @Override
            public void onItemClick(AdapterView<?> adapterView, View view, int i, long l) {
                Intent intent = new Intent(ProfileActivity.this, BinViewActivity.class);
                intent.putExtra("id", (int)view.getTag(R.id.id));
                startActivity(intent);
            }
        });

    }

    public void comment(View view) {
        EditText et = findViewById(R.id.profile_commentbox);
        commentAdapter.comment(et ,et.getText().toString(), user_id, 3);
    }

    public void showDumpList(View view) {
        Intent intent = new Intent(this, DumpListActivity.class);
        intent.putExtra("id", ""+user_id);
        startActivity(intent);
    }

    public void showBinList(View view) {
        Intent intent = new Intent(this, BinListActivity.class);
        intent.putExtra("id", ""+user_id);
        startActivity(intent);
    }

    @Override
    public boolean onSupportNavigateUp() {
        onBackPressed();
        return true;
    }
}