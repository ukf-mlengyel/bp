package com.mirzi.binme;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;

import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.AdapterView;
import android.widget.ListView;
import android.widget.Toast;

import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.mirzi.binme.Adapter.DumpBinListAdapter;
import com.mirzi.binme.Helper.BM;
import com.mirzi.binme.Helper.BMRequestQueue;
import com.mirzi.binme.Helper.StringRequestSession;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;

public class BinListActivity extends AppCompatActivity {

    private ListView binList;
    private DumpBinListAdapter binListAdapter;
    private int user_id;
    private RequestQueue queue;


    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_bin_list);

        binList = findViewById(R.id.binlist_listview);

        Toolbar toolbar = findViewById(R.id.home_toolbar);
        toolbar.setTitle(R.string.binlist_title);
        setSupportActionBar(toolbar);
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);

        Intent intent = getIntent();
        user_id = Integer.parseInt(intent.getStringExtra("id"));
        queue = BMRequestQueue.getInstance(this).getRequestQueue();

        StringRequestSession binListRequest = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/getBinList.php", getApplicationContext(),
                response -> {
                    try {
                        Log.i("BM_PROFILE_RESPONSE", "Binlist response: "+response);

                        JSONArray json = new JSONArray(response);
                        if (json.length() != 0){
                            binListAdapter = new DumpBinListAdapter(json, this, false, getString(R.string.bin_date_prefix));
                            binList.setAdapter(binListAdapter);
                        }else{
                            Toast.makeText(getApplicationContext(), R.string.no_bins, Toast.LENGTH_LONG).show();
                            finish();
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
                    json.put("l", false);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        queue.add(binListRequest);

        binList.setOnItemClickListener(new AdapterView.OnItemClickListener() {
            @Override
            public void onItemClick(AdapterView<?> adapterView, View view, int i, long l) {
                Intent intent = new Intent(BinListActivity.this, BinViewActivity.class);
                intent.putExtra("id", (int)view.getTag(R.id.id));
                startActivity(intent);
            }
        });
    }

    @Override
    public boolean onSupportNavigateUp() {
        onBackPressed();
        return true;
    }
}