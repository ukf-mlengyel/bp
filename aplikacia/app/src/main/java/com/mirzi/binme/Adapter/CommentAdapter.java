package com.mirzi.binme.Adapter;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.DialogInterface;
import android.content.Intent;
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
import com.mirzi.binme.ProfileActivity;
import com.mirzi.binme.R;
import com.squareup.picasso.Picasso;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;
import java.text.SimpleDateFormat;
import java.util.Date;

public class CommentAdapter extends BaseAdapter {

    int TYPE = 2;

    int user_id;

    private Activity activity;
    private JSONArray data;

    private String[] likeBtnText = new String[2];

    private RequestQueue queue;

    public CommentAdapter(JSONArray data, Activity activity) {
        this.data = data;
        this.activity = activity;

        user_id = Integer.parseInt(SessionHelper.getPreference(activity.getApplicationContext(), "user_id"));

        queue = BMRequestQueue.getInstance(activity.getApplicationContext()).getRequestQueue();

        likeBtnText[0] = activity.getString(R.string.likebutton_disabled);
        likeBtnText[1] = activity.getString(R.string.likebutton);
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
            view = activity.getLayoutInflater().inflate(R.layout.layout_comment, null);

        ImageView userImage = view.findViewById(R.id.comment_user_img);
        TextView tvUserName = view.findViewById(R.id.comment_username);
        TextView tvDate = view.findViewById(R.id.comment_date);
        TextView tvComment = view.findViewById(R.id.comment_body);
        Button likeButton = view.findViewById(R.id.comment_like_btn);
        Button deleteButton = view.findViewById(R.id.comment_delete_btn);

        try {
            JSONObject json = data.getJSONObject(i);
            int comment_id = json.getInt("id");

            tvUserName.setText(json.getString("username"));
            tvDate.setText(json.getString("creation_date"));
            tvComment.setText(json.getString("content"));

            likeButton.setText(String.format(likeBtnText[json.getInt("isliked")], json.getInt("likecount")));
            likeButton.setTag(R.id.commentid, comment_id);
            likeButton.setTag(R.id.isliked, json.getInt("isliked"));
            likeButton.setTag(R.id.likecount, json.getInt("likecount"));
            likeButton.setTag(R.id.adapterposition, i);
            likeButton.setOnClickListener(this::like);

            deleteButton.setVisibility(View.GONE);

            int comm_user_id = json.getInt("user_id");
            if (comm_user_id == user_id){
                deleteButton.setVisibility(View.VISIBLE);
                deleteButton.setTag(R.id.adapterposition, i);
                deleteButton.setTag(R.id.commentid, comment_id);
                deleteButton.setOnClickListener(this::delete);
            }

            Picasso.get().load(BM.SERVER_URL+"/images/user_thumb/"+json.getString("image")+".jpg").into(userImage);
            userImage.setOnClickListener(new View.OnClickListener() {
                @Override
                public void onClick(View view) {
                    goToProfile(comm_user_id);
                }
            });

        }catch (Exception e){
            e.printStackTrace();
        }

        return view;
    }

    private void delete(View view){
        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/web/removecomment.php", activity.getApplicationContext(),
                response -> {
                    if (response.equals("1")){
                        data.remove((Integer) view.getTag(R.id.adapterposition));
                        notifyDataSetChanged();
                    }else{
                        Toast.makeText(activity.getApplicationContext(), R.string.too_many_requests, Toast.LENGTH_LONG).show();
                    }
                }, error -> {
            Toast.makeText(activity.getApplicationContext(), activity.getString(R.string.connect_error), Toast.LENGTH_LONG).show();
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
                    json.put("i", view.getTag(R.id.commentid));
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };


        new AlertDialog.Builder(activity)
                .setMessage(R.string.delete_comment_confirm)
                .setPositiveButton(R.string.yes, new DialogInterface.OnClickListener() {
                    public void onClick(DialogInterface dialog, int button) {
                        queue.add(request);
                    }})
                .setNegativeButton(R.string.no, null).show();
    }

    private void like(View view){
        int id = (int) view.getTag(R.id.commentid);
        int likestate = (int) view.getTag(R.id.isliked);

        String url = BM.SERVER_URL + "/web/like.php";

        StringRequestSession request = new StringRequestSession(Request.Method.POST, url, activity.getApplicationContext(),
                response -> {
                    if (response.equals("1")){
                        toggleLikeButton((TextView)view, likestate);
                    }else{
                        Toast.makeText(activity.getApplicationContext(), R.string.too_many_requests, Toast.LENGTH_LONG).show();
                    }
                }, error -> {
            Toast.makeText(activity.getApplicationContext(), activity.getString(R.string.connect_error), Toast.LENGTH_LONG).show();
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
                    json.put("t", TYPE);
                    json.put("i", id);
                    json.put("s", likestate == 1 ? 0 : 1);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        queue.add(request);
    }

    public void comment(EditText view, String content, int id, int parent_type){
        if (content.isEmpty()) return;

        RequestQueue queue = BMRequestQueue.getInstance(activity.getApplicationContext()).getRequestQueue();

        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/web/comment.php", activity.getApplicationContext(),
                response -> {
                    if (response.substring(0,1).equals("1")){
                        String comment_id = response.substring(2);
                        String username = SessionHelper.getPreference(activity.getApplicationContext(), "nick");
                        String image = SessionHelper.getPreference(activity.getApplicationContext(), "image");

                        String date = new SimpleDateFormat("dd.MM.yyyy, HH:mm").format(new Date());

                        JSONArray previous = data;
                        try {
                            data = new JSONArray("[{'id':'"+comment_id+"','user_id':'"+user_id+"','username':'"+username+"','image':'"+image+"','creation_date':'"+date+"','content':'"+content+"','likecount':'0','isliked':'0'}]");
                            for (int i = 0; i<previous.length(); i++){
                                data.put(previous.get(i));
                            }

                            notifyDataSetChanged();
                        } catch (JSONException e) {
                            e.printStackTrace();
                        }

                        view.setText("");
                    }
                    else{
                        Toast.makeText(activity.getApplicationContext(), R.string.too_many_requests, Toast.LENGTH_LONG).show();
                    }
                }, error -> {
            Toast.makeText(activity.getApplicationContext(), activity.getString(R.string.connect_error), Toast.LENGTH_LONG).show();
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
                    json.put("t", parent_type);
                    json.put("i", id);
                    json.put("c", content);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        queue.add(request);
    }

    private void toggleLikeButton(TextView view, int likestate){
        int likecount = (int) view.getTag(R.id.likecount);

        if (likestate == 0) {likestate = 1; likecount++;}
        else {likestate = 0; likecount--;}

        view.setTag(R.id.isliked, likestate);
        view.setTag(R.id.likecount, likecount);

        // odkážte mirkovi v budúcnosti nech pouziva namiesto JSONObjectov classy ;)
        int position = (int)view.getTag(R.id.adapterposition);
        try {
            JSONObject obj = (JSONObject) data.get(position);
            obj.put("isliked", likestate);
            obj.put("likecount", likecount);

            data.put(position, obj);
        } catch (JSONException e) {
            e.printStackTrace();
        }


        Log.i("BM_LIKE_INFO", "New state: "+likestate+", count: "+likecount);

        view.setText(String.format(likeBtnText[likestate], likecount));
    }

    private void goToProfile(int id){
        Intent intent = new Intent(activity, ProfileActivity.class);
        intent.putExtra("id", ""+id);
        activity.startActivity(intent);
    }
}
