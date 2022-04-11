package com.mirzi.binme;

import android.app.AlertDialog;
import android.content.DialogInterface;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.SwitchCompat;

import com.android.volley.AuthFailureError;
import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.mirzi.binme.Adapter.CommentAdapter;
import com.mirzi.binme.Helper.BM;
import com.mirzi.binme.Helper.BMRequestQueue;
import com.mirzi.binme.Helper.DistanceCalculator;
import com.mirzi.binme.Helper.SessionHelper;
import com.mirzi.binme.Helper.StringRequestSession;
import com.squareup.picasso.Picasso;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;

public class BinViewActivity extends AppCompatActivity {

    private final int TYPE = 1;

    private double lon, lat,   distance;
    private double targetLon, targetLat = -1;
    private String targetLocationName, description;
    private int id, user_id, types,    likestate, likecount;

    private Button likeButton;

    private RequestQueue queue;

    private ListView commentList;

    private CommentAdapter adapter;

    private TextView binTypesText, descriptionView;

    private final String[] bintypes = new String[8];
    private final String[] likeBtnText = new String[2];

    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_bin_view);

        Intent intent = getIntent();

        id = intent.getIntExtra("id", -1);
        lon = intent.getDoubleExtra("lon", -1);
        lat = intent.getDoubleExtra("lat", -1);

        likeBtnText[0] = getString(R.string.likebutton_disabled);
        likeBtnText[1] = getString(R.string.likebutton);

        for (int i = 0; i<8; i++){
            String name = "bin_type_"+(int)Math.pow(2, i);
            int id = getApplicationContext()
                    .getResources()
                    .getIdentifier(name, "string", getPackageName());

            bintypes[i] = getString(id);
        }

        commentList = findViewById(R.id.binview_comments);
        likeButton = findViewById(R.id.binview_like_button);

        queue = BMRequestQueue.getInstance(getApplicationContext()).getRequestQueue();

        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/getBin.php", getApplicationContext(),
                response -> {
                    try {
                        Log.i("BM_DUMP_RESPONSE", "Bin response: "+response);

                        JSONObject json = new JSONObject(response);

                        // images
                        Picasso.get().load(BM.SERVER_URL+"/images/bin/"+json.getString("image")+".jpg").into((ImageView)findViewById(R.id.binview_img));
                        Picasso.get().load(BM.SERVER_URL+"/images/user_thumb/"+json.getString("user_image")+".jpg").into((ImageView)findViewById(R.id.binview_user_img));

                        user_id = json.getInt("user_id");

                        // main text
                        targetLocationName = json.getString("location_name");
                        ((TextView)findViewById(R.id.binview_title)).setText(targetLocationName);

                        types = json.getInt("types");
                        binTypesText = ((TextView)findViewById(R.id.binview_status));
                        refreshBinTypes();

                        targetLon = json.getDouble("location_lat");
                        targetLat = json.getDouble("location_lon");

                        // distance
                        if (lon != -1 && lat != -1){
                            distance = DistanceCalculator.calculateDistance(
                                    lon,
                                    lat,
                                    targetLat,
                                    targetLon
                            );

                            ((TextView)findViewById(R.id.binview_title_sub)).setText( DistanceCalculator.getDistanceStr(distance) );
                        }else{
                            findViewById(R.id.binview_title_sub).setVisibility(View.GONE);
                        }


                        // user
                        ((TextView)findViewById(R.id.binview_username)).setText(json.getString("username"));
                        ((TextView)findViewById(R.id.binview_add_date)).setText(json.getString("creation_date"));

                        // desc
                        description = json.getString("description");
                        descriptionView = findViewById(R.id.binview_description);
                        if (description.equals("")){
                            descriptionView.setVisibility(View.GONE);
                            findViewById(R.id.binview_description_separator).setVisibility(View.GONE);
                        }else{
                            descriptionView.setText(description);
                        }

                        // like btn
                        likestate = json.getInt("isliked");
                        likecount = json.getInt("likecount");
                        likeButton.setText(String.format(likeBtnText[likestate], likecount));

                        // admin buttons
                        if (Integer.parseInt(SessionHelper.getPreference(getApplicationContext(), "user_id")) == json.getInt("user_id")){
                            findViewById(R.id.binview_admin_buttons).setVisibility(View.VISIBLE);
                        }

                    } catch (Exception e) {
                        e.printStackTrace();
                        Toast.makeText(getApplicationContext(), getString(R.string.bin_doesnt_exist), Toast.LENGTH_LONG).show();
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
                    json.put("id", id);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        StringRequestSession commentRequest = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/getComments.php", getApplicationContext(),
                response -> {
                    try {
                        Log.i("BM_DUMP_RESPONSE", "Bin comments response: "+response);
                        JSONArray json = new JSONArray(response);
                        adapter = new CommentAdapter(json, this);

                        commentList.setAdapter(adapter);
                    } catch (JSONException e) {
                        e.printStackTrace();
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
                    json.put("id", id);
                    json.put("parent", 1);
                    json.put("offset", 0);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        queue.add(request);
        queue.add(commentRequest);
    }

    private void refreshBinTypes(){
        String typesText = "";
        for (int o = 0; o<8; o++){
            int check = (int)Math.pow(2, o);
            if ((check & types) == check) typesText+=bintypes[o]+" ";
        }
        binTypesText.setText(typesText);
    }

    public void like(View view) {
        String url = BM.SERVER_URL + "/web/like.php";

        StringRequestSession request = new StringRequestSession(Request.Method.POST, url, getApplicationContext(),
                response -> {
                    if (response.equals("1")){
                        toggleLikeButton();
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

    private void toggleLikeButton(){
        if (likestate == 0) {likestate = 1; likecount++;}
        else {likestate = 0; likecount--;}

        likeButton.setText(String.format(likeBtnText[likestate], likecount));
    }

    public void postComment(View view) {
        EditText et = findViewById(R.id.binview_commentbox);
        adapter.comment(et ,et.getText().toString(), id, 1);
    }

    public void editDescription(View view) {
        final View editDescView = getLayoutInflater().inflate(R.layout.layout_edit_description, null);
        EditText editText = editDescView.findViewById(R.id.dialog_edit_text);
        if (!description.equals("null"))editText.setText(description);

        new AlertDialog.Builder(this)
                .setTitle(R.string.edit_description)
                .setPositiveButton(R.string.confirm, new DialogInterface.OnClickListener() {
                    @Override
                    public void onClick(DialogInterface dialogInterface, int i) {
                        String newDesc = editText.getText().toString();

                        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/editBinDesc.php", getApplicationContext(),
                                response -> {
                                    Log.i("BM_REQUEST_INFO", "Editdesc response: "+response);
                                    if (response.equals("1")){
                                        Toast.makeText(getApplicationContext(), R.string.description_edited, Toast.LENGTH_LONG).show();
                                        descriptionView.setText(newDesc);
                                        description = newDesc;

                                        descriptionView.setVisibility(View.VISIBLE);
                                        findViewById(R.id.binview_description_separator).setVisibility(View.VISIBLE);
                                    }else{
                                        Toast.makeText(getApplicationContext(), R.string.too_many_requests, Toast.LENGTH_LONG).show();
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
                                    json.put("i", id);
                                    json.put("d", newDesc);
                                    return json.toString().getBytes(StandardCharsets.UTF_8);
                                }catch (JSONException e){
                                    Log.e("BM_JSON_ERROR", e.getMessage());
                                    return null;
                                }
                            }
                        };

                        queue.add(request);
                    }
                }).setNegativeButton(R.string.cancel, null)
                .setView(editDescView)
                .show();
    }

    public void showProfile(View view) {
        Intent intent = new Intent(this, ProfileActivity.class);
        intent.putExtra("id", ""+user_id);
        startActivity(intent);
    }

    public void openBinInMaps(View view) {
        if (targetLon != -1 && targetLat != -1){
            Uri uri = Uri.parse( String.format("geo:%f,%f?q=%s",targetLon, targetLat, targetLocationName) );
            Intent intent = new Intent(Intent.ACTION_VIEW, uri);
            intent.setPackage("com.google.android.apps.maps");
            startActivity(intent);
        }
    }

    public void deleteBin(View view) {
        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/web/bin/remove.php", getApplicationContext(),
                response -> {
                    Log.i("BM_REQUEST_INFO", "Delete bin response: "+response);
                    if (response.equals("1")){
                        Toast.makeText(getApplicationContext(), R.string.bin_removed, Toast.LENGTH_LONG).show();

                        Intent returnIntent = new Intent();
                        setResult(RESULT_OK, returnIntent);
                        finish();
                    }else{
                        Toast.makeText(getApplicationContext(), R.string.too_many_requests, Toast.LENGTH_LONG).show();
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
                    json.put("i", id);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        new AlertDialog.Builder(this)
                .setMessage(R.string.delete_dump_confirm)
                .setPositiveButton(R.string.yes, new DialogInterface.OnClickListener() {
                    public void onClick(DialogInterface dialog, int button) {
                        queue.add(request);
                    }})
                .setNegativeButton(R.string.no, null).show();
    }

    public void back(View view) {
        onBackPressed();
    }

    public void editTypes(View view) {
        final View editTypesView = getLayoutInflater().inflate(R.layout.layout_edit_types, null);

        SwitchCompat[] typeSwitches = new SwitchCompat[8];

        // switches
        for (int i = 0; i<8; i++){
            int check = (int)Math.pow(2, i);

            String name = "edit_types_switch"+check;
            int id = getResources()
                    .getIdentifier(name, "id", getPackageName());

            typeSwitches[i] = editTypesView.findViewById(id);
            if ((check & types) == check) ((SwitchCompat)typeSwitches[i]).setChecked(true);
        }

        new AlertDialog.Builder(this)
                .setTitle(R.string.edit_types)
                .setPositiveButton(R.string.confirm, new DialogInterface.OnClickListener() {
                    @Override
                    public void onClick(DialogInterface dialogInterface, int i) {
                        // get new types
                        int newtypes[] = {0};
                        for (int o = 0; o<8; o++){
                            if (typeSwitches[o].isChecked()){
                                newtypes[0] += (int)Math.pow(2, o);
                            }
                        }

                        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/web/bin/changeTypes.php", getApplicationContext(),
                                response -> {
                                    Log.i("BM_REQUEST_INFO", "Edittypes response: "+response);
                                    if (response.equals("1")){
                                        Toast.makeText(getApplicationContext(), R.string.types_edited, Toast.LENGTH_LONG).show();
                                        types = newtypes[0];
                                        refreshBinTypes();
                                    }else{
                                        Toast.makeText(getApplicationContext(), R.string.too_many_requests, Toast.LENGTH_LONG).show();
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
                                    json.put("i", id);
                                    json.put("t", newtypes[0]);
                                    return json.toString().getBytes(StandardCharsets.UTF_8);
                                }catch (JSONException e){
                                    Log.e("BM_JSON_ERROR", e.getMessage());
                                    return null;
                                }
                            }

                        };

                        queue.add(request);
                    }
                }).setNegativeButton(R.string.cancel, null)
                .setView(editTypesView)
                .show();
    }
}