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

public class CleanupsListActivity extends AppCompatActivity {

    private ListView cleanupsList;
    private DumpBinListAdapter cleanupsListAdapter;
    private RequestQueue queue;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_cleanups_list);

        cleanupsList = findViewById(R.id.cleanupslist_listview);

        Toolbar toolbar = findViewById(R.id.home_toolbar);
        toolbar.setTitle(R.string.cleanuplist_title);
        setSupportActionBar(toolbar);
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);

        queue = BMRequestQueue.getInstance(this).getRequestQueue();

        StringRequestSession binListRequest = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/getCleanupsToAttend.php", getApplicationContext(),
                response -> {
                    try {
                        Log.i("BM_PROFILE_RESPONSE", "Cleanupslist response: "+response);

                        JSONArray json = new JSONArray(response);
                        if (json.length() != 0){
                            cleanupsListAdapter = new DumpBinListAdapter(json, this, true, getString(R.string.cleanup_date_prefix));
                            cleanupsList.setAdapter(cleanupsListAdapter);
                        }else{
                            Toast.makeText(getApplicationContext(), R.string.no_cleanups, Toast.LENGTH_LONG).show();
                            finish();
                        }
                    } catch (Exception e) {
                        e.printStackTrace();
                    }
                }, error -> {
                    Toast.makeText(getApplicationContext(), getString(R.string.connect_error), Toast.LENGTH_LONG).show();
                    finish();
                }
        );

        queue.add(binListRequest);

        cleanupsList.setOnItemClickListener(new AdapterView.OnItemClickListener() {
            @Override
            public void onItemClick(AdapterView<?> adapterView, View view, int i, long l) {
                Intent intent = new Intent(CleanupsListActivity.this, DumpViewActivity.class);
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