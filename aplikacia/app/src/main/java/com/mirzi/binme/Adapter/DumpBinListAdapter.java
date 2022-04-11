package com.mirzi.binme.Adapter;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.DialogInterface;
import android.util.Log;
import android.view.View;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import com.android.volley.AuthFailureError;
import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.mirzi.binme.Helper.BM;
import com.mirzi.binme.Helper.BMRequestQueue;
import com.mirzi.binme.Helper.SessionHelper;
import com.mirzi.binme.Helper.StringRequestSession;
import com.mirzi.binme.R;
import com.squareup.picasso.Picasso;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;
import java.text.SimpleDateFormat;
import java.util.Date;

public class DumpBinListAdapter extends BaseAdapter {

    private Activity activity;
    private JSONArray data;
    private boolean isDump;
    private String datePrefix;

    private String[] dumpstates = new String[4];
    private String[] bintypes = new String[8];

    public DumpBinListAdapter(JSONArray data, Activity activity, boolean isDump, String datePrefix) {
        this.data = data;
        this.activity = activity;
        this.isDump = isDump;
        this.datePrefix = datePrefix;

        for (int i = 0; i<4; i++){
            String name = "dump_state_"+i;
            int id = activity.getApplicationContext()
                    .getResources()
                    .getIdentifier(name, "string", "com.mirzi.binme");

            dumpstates[i] = activity.getString(id);
        }

        for (int i = 0; i<8; i++){
            String name = "bin_type_icon_"+(int)Math.pow(2, i);
            int id = activity.getApplicationContext()
                    .getResources()
                    .getIdentifier(name, "string", "com.mirzi.binme");

            bintypes[i] = activity.getString(id);
        }
    }

    @Override
    public int getCount() {
        return data.length();
    }

    @Override
    public JSONObject getItem(int i) {
        try {
            return data.getJSONObject(i);
        } catch (JSONException e) {
            e.printStackTrace();
        }
        return null;
    }

    @Override
    public long getItemId(int i) {
        try {
            return data.getJSONObject(i).getInt("id");
        } catch (JSONException e) {
            e.printStackTrace();
        }
        return -1;
    }

    @Override
    public View getView(int i, View view, ViewGroup viewGroup) {
        if(view == null)
            view = activity.getLayoutInflater().inflate(R.layout.layout_post, null);

        ImageView image = view.findViewById(R.id.post_picture);
        TextView tvTitle = view.findViewById(R.id.post_title);
        TextView tvSubtitle = view.findViewById(R.id.post_subtitle);

        try {
            JSONObject json = data.getJSONObject(i);
            view.setTag(R.id.id, json.getInt("id"));
            tvTitle.setText(json.getString("location_name"));

            if (isDump){
                Picasso.get().load(BM.SERVER_URL+"/images/dump_thumb/"+json.getString("image")+".jpg").into(image);
                tvSubtitle.setText(datePrefix+json.getString("creation_date")+"\n"+dumpstates[json.getInt("status")]);
            }else{
                Picasso.get().load(BM.SERVER_URL+"/images/bin_thumb/"+json.getString("image")+".jpg").into(image);

                int types = json.getInt("types");
                String result = "";
                for (int o = 0; o<8; o++){
                    int check = (int)Math.pow(2, o);
                    if ((check & types) == check) result+=bintypes[o];
                }

                tvSubtitle.setText(datePrefix+json.getString("creation_date")+", "+result);
            }



        }catch (Exception e){
            e.printStackTrace();
        }

        return view;
    }
}
