package com.mirzi.binme.Adapter;

import android.app.Activity;
import android.view.View;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.ImageView;
import android.widget.TextView;

import com.mirzi.binme.Helper.BM;
import com.mirzi.binme.R;
import com.squareup.picasso.Picasso;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

public class UserListAdapter extends BaseAdapter {
    private Activity activity;
    private JSONArray data;

    public UserListAdapter(JSONArray data, Activity activity){
        this.data = data;
        this.activity = activity;
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
            view = activity.getLayoutInflater().inflate(R.layout.layout_user, viewGroup, false);

        try {
            JSONObject json = data.getJSONObject(i);

            Picasso.get().load(BM.SERVER_URL+"/images/user/"+json.getString("image")+".jpg").into(((ImageView)view.findViewById(R.id.userlayout_img)));
            ((TextView)view.findViewById(R.id.userlayout_name)).setText(json.getString("username"));
            ((TextView)view.findViewById(R.id.userlayout_points)).setText(json.getString("points"));
            view.setTag(R.id.id, json.getString("id"));

        } catch (JSONException e) {
            e.printStackTrace();
        }

        return view;
    }
}
