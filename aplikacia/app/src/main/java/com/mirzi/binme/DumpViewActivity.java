package com.mirzi.binme;

import androidx.appcompat.app.AppCompatActivity;

import android.app.AlertDialog;
import android.app.DatePickerDialog;
import android.app.TimePickerDialog;
import android.content.DialogInterface;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.DatePicker;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.TimePicker;
import android.widget.Toast;

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
import java.util.Calendar;
import java.util.Date;

public class DumpViewActivity extends AppCompatActivity {

    private final int TYPE = 0;

    private double lon, lat,   distance;
    private double targetLon, targetLat = -1;
    private String targetLocationName, cleanupDate, description;
    private int id, user_id, dumpstatus,    likestate, likecount, attendance;

    private Button likeButton, toggleAttendButton, attendCleanupButton;

    private RequestQueue queue;

    private ListView commentList;

    private CommentAdapter adapter;

    private JSONArray toAttend, attendants;
    private TextView toAttendList, dumpStatusText, attendantsList, descriptionView;

    private final String[] dumpState = new String[4];
    private final String[] likeBtnText = new String[2];

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_dump_view);

        Intent intent = getIntent();

        id = intent.getIntExtra("id", -1);
        lon = intent.getDoubleExtra("lon", -1);
        lat = intent.getDoubleExtra("lat", -1);

        dumpState[0] = getString(R.string.dump_state_0);
        dumpState[1] = getString(R.string.dump_state_1);
        dumpState[2] = getString(R.string.dump_state_2);
        dumpState[3] = getString(R.string.dump_state_3);

        likeBtnText[0] = getString(R.string.likebutton_disabled);
        likeBtnText[1] = getString(R.string.likebutton);

        commentList = findViewById(R.id.dumpview_comments);
        likeButton = findViewById(R.id.dumpview_like_button);

        toAttendList = findViewById(R.id.toAttendList);
        attendantsList = findViewById(R.id.attendantsList);
        toggleAttendButton = findViewById(R.id.dumpview_toggleattend_button);
        attendCleanupButton = findViewById(R.id.dumpview_attend_button);

        queue = BMRequestQueue.getInstance(getApplicationContext()).getRequestQueue();

        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/getDump.php", getApplicationContext(),
                response -> {
                    try {
                        Log.i("BM_DUMP_RESPONSE", "Dump response: "+response);

                        JSONObject json = new JSONObject(response);

                        // images
                        Picasso.get().load(BM.SERVER_URL+"/images/dump/"+json.getString("image")+".jpg").into((ImageView)findViewById(R.id.dumpview_img));
                        Picasso.get().load(BM.SERVER_URL+"/images/user_thumb/"+json.getString("user_image")+".jpg").into((ImageView)findViewById(R.id.dumpview_user_img));

                        user_id = json.getInt("user_id");

                        // main text
                        targetLocationName = json.getString("location_name");
                        ((TextView)findViewById(R.id.dumpview_title)).setText(targetLocationName);

                        dumpstatus = json.getInt("status");
                        dumpStatusText = ((TextView)findViewById(R.id.dumpview_status));
                        dumpStatusText.setText(dumpState[dumpstatus]);

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

                            ((TextView)findViewById(R.id.dumpview_title_sub)).setText( DistanceCalculator.getDistanceStr(distance) );
                        }else{
                            findViewById(R.id.dumpview_title_sub).setVisibility(View.GONE);
                        }


                        // user
                        ((TextView)findViewById(R.id.dumpview_username)).setText(json.getString("username"));
                        ((TextView)findViewById(R.id.dumpview_add_date)).setText(json.getString("creation_date"));

                        // desc
                        description = json.getString("description");
                        descriptionView = findViewById(R.id.dumpview_description);
                        if (description.equals("")){
                            descriptionView.setVisibility(View.GONE);
                            findViewById(R.id.dumpview_description_separator).setVisibility(View.GONE);
                        }else{
                            descriptionView.setText(description);
                        }

                        // like btn
                        likestate = json.getInt("isliked");
                        likecount = json.getInt("likecount");
                        likeButton.setText(String.format(likeBtnText[likestate], likecount));

                        // cleanup event
                        if (dumpstatus == 1 || dumpstatus == 2){
                            cleanupDate = json.getString("planned_cleanup");
                            showCleanupEventSection();
                        }

                        // admin buttons
                        if (Integer.parseInt(SessionHelper.getPreference(getApplicationContext(), "user_id")) == json.getInt("user_id")){
                            findViewById(R.id.dumpview_admin_buttons).setVisibility(View.VISIBLE);
                            if (dumpstatus != 0) findViewById(R.id.plan_cleanup_btn).setVisibility(View.GONE);
                            if (dumpstatus == 3) ((Button)findViewById(R.id.set_cleaned_btn)).setText(R.string.set_not_cleaned);
                        }

                    } catch (Exception e) {
                        e.printStackTrace();
                        Toast.makeText(getApplicationContext(), getString(R.string.dump_doesnt_exist), Toast.LENGTH_LONG).show();
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
                        Log.i("BM_DUMP_RESPONSE", "Dump comments response: "+response);
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
                    json.put("parent", 0);
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

    public void openDumpInMaps(View view) {
        if (targetLon != -1 && targetLat != -1){
            Uri uri = Uri.parse( String.format("geo:%f,%f?q=%s",targetLon, targetLat, targetLocationName) );
            Intent intent = new Intent(Intent.ACTION_VIEW, uri);
            intent.setPackage("com.google.android.apps.maps");
            startActivity(intent);
        }
    }

    // --- i'm so sorry ----
    private void showCleanupEventSection(){
        // USER ATTENDANCE ----------------------------------------------------------------------------------------------------------------
        StringRequestSession attendanceRequest = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/getCleanupAttendance.php", getApplicationContext(),
            response -> {
                Log.i("BM_CLEANUP_EVENT_REQUEST", "Attendance response: "+response);
                attendance = Integer.parseInt(response);

                toggleAttendButton.setText(attendance == 1 || attendance == 3 ? R.string.dont_want_to_attend_btn : R.string.want_to_attend_btn);

                findViewById(R.id.cleanup_event_section).setVisibility(View.VISIBLE);
                findViewById(R.id.cleanup_event_separator).setVisibility(View.VISIBLE);
                ((TextView)findViewById(R.id.dumpview_cleanup_date)).setText(cleanupDate);

                System.out.println(distance);
                if (distance < 50 && attendance < 2 && dumpstatus == 2){
                    attendCleanupButton.setVisibility(View.VISIBLE);
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
                    json.put("dumpid", id);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        // PEOPLE TO ATTEND ----------------------------------------------------------------------------------------------------------------
        StringRequestSession toAttendRequest = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/getCleanupAttendants.php", getApplicationContext(),
                response -> {
                    Log.i("BM_CLEANUP_EVENT_REQUEST", "To attend response: "+response);
                    try {
                        toAttend = new JSONArray(response);
                        String str = "";
                        if (toAttend.length() != 0){
                            for (int i = 0; i<toAttend.length(); i++){
                                JSONObject obj = (JSONObject) toAttend.get(i);
                                str += obj.getString("u")+", ";
                            }
                            str = str.substring(0, str.length()-2);
                        }

                        toAttendList.setText(String.format(getString(R.string.to_attend_title), toAttend.length(), str));
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
                    json.put("dumpid", id);
                    json.put("status", false);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        // PEOPLE WHO ATTENDED ----------------------------------------------------------------------------------------------------------------
        StringRequestSession attendantsRequest = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/getCleanupAttendants.php", getApplicationContext(),
                response -> {
                    Log.i("BM_CLEANUP_EVENT_REQUEST", "Attendants resopnse: "+response);
                    try {
                        attendants = new JSONArray(response);
                        String str = "";
                        if (attendants.length() != 0) {
                            for (int i = 0; i < attendants.length(); i++) {
                                JSONObject obj = (JSONObject) attendants.get(i);
                                str += obj.getString("u") + ", ";
                            }
                            str = str.substring(0, str.length() - 2);
                        }

                        attendantsList.setText(String.format(getString(R.string.attendees_title), attendants.length(), str));
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
                    json.put("dumpid", id);
                    json.put("status", true);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        queue.add(attendanceRequest);
        queue.add(toAttendRequest);
        queue.add(attendantsRequest);
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
        EditText et = findViewById(R.id.dumpview_commentbox);
        adapter.comment(et ,et.getText().toString(), id, 0);
    }

    public void toggleAttendance(View view) {
        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/toggleAttendance.php", getApplicationContext(),
                response -> {
                    Log.i("BM_REQUEST_INFO", "Toggle attendance response: "+response);
                    if (response.equals("1")){
                        // add to list
                        showCleanupEventSection();
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

        queue.add(request);
    }

    public void attendCleanup(View view) {
        if(distance < 50){
            StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/attendCleanup.php", getApplicationContext(),
                    response -> {
                        Log.i("BM_REQUEST_INFO", "Attend cleanup response: "+response);
                        if (response.equals("1")){
                            Toast.makeText(getApplicationContext(), R.string.attend_message, Toast.LENGTH_LONG).show();
                            attendCleanupButton.setVisibility(View.GONE);
                            showCleanupEventSection();
                        }else{
                            Toast.makeText(getApplicationContext(), response, Toast.LENGTH_LONG).show();
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
                        json.put("lon", lon);
                        json.put("lat", lat);
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

    public void deleteDump(View view) {
        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/web/dump/remove.php", getApplicationContext(),
                response -> {
                    Log.i("BM_REQUEST_INFO", "Delete dump response: "+response);
                    if (response.equals("1")){
                        Toast.makeText(getApplicationContext(), R.string.dump_removed, Toast.LENGTH_LONG).show();

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

    public void setCleaned(View view) {
        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/web/dump/setcleaned.php", getApplicationContext(),
                response -> {
                    Log.i("BM_REQUEST_INFO", "Setcleaned dump response: "+response);
                    if (response.equals("1")){
                        Toast.makeText(getApplicationContext(), R.string.status_changed, Toast.LENGTH_LONG).show();

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
                    json.put("s", dumpstatus == 3 ? 0 : 3);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        String dialogMsg = getString(dumpstatus == 3 ? R.string.set_not_cleaned : R.string.set_cleaned) + "?";

        new AlertDialog.Builder(this)
                .setMessage(dialogMsg)
                .setPositiveButton(R.string.yes, new DialogInterface.OnClickListener() {
                    public void onClick(DialogInterface dialog, int button) {
                        queue.add(request);
                    }})
                .setNegativeButton(R.string.no, null).show();
    }

    public void planCleanup(View view) {
        Calendar c = Calendar.getInstance();
        String[] datetime = new String[3];

        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/planCleanup.php", getApplicationContext(),
                response -> {
                    Log.i("BM_REQUEST_INFO", "Plan cleanup response: "+response);
                    if (response.equals("1")){
                        Toast.makeText(getApplicationContext(), R.string.plan_cleanup_success, Toast.LENGTH_LONG).show();
                        dumpStatusText.setText(dumpState[1]);
                        findViewById(R.id.cleanup_event_section).setVisibility(View.VISIBLE);
                        findViewById(R.id.cleanup_event_separator).setVisibility(View.VISIBLE);
                        toAttendList.setText(String.format(getString(R.string.to_attend_title), 0, ""));
                        attendantsList.setText(String.format(getString(R.string.attendees_title), 0, ""));
                        ((TextView)findViewById(R.id.dumpview_cleanup_date)).setText(datetime[2]+", "+datetime[1]);
                        view.setVisibility(View.GONE);
                    }else{
                        Toast.makeText(getApplicationContext(), response, Toast.LENGTH_LONG).show();
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
                    json.put("d", datetime[0]+" "+datetime[1]);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        AlertDialog.Builder dialog = new AlertDialog.Builder(this)
                .setPositiveButton(R.string.yes, new DialogInterface.OnClickListener() {
                    public void onClick(DialogInterface dialog, int button) {
                        queue.add(request);
                    }})
                .setNegativeButton(R.string.no, null);


        TimePickerDialog timePicker = new TimePickerDialog(this, new TimePickerDialog.OnTimeSetListener() {
            @Override
            public void onTimeSet(TimePicker timePicker, int i, int i1) {
                String h = i < 10 ? "0"+i : ""+i;
                String m = i1 < 10 ? "0"+i1 : ""+i1;

                datetime[1] = h+":"+m;

                dialog.setMessage(String.format(getString(R.string.plan_cleanup_msg), datetime[2], datetime[1]));
                dialog.show();
            }
        }, c.get(Calendar.HOUR_OF_DAY) + 1, c.get(Calendar.MINUTE), true);


        DatePickerDialog datePicker = new DatePickerDialog(this, new DatePickerDialog.OnDateSetListener() {
            @Override
            public void onDateSet(DatePicker datePicker, int i, int i1, int i2) {
                String m = i1++ < 10 ? "0"+i1 : ""+i1;
                String d = i2 < 10 ? "0"+i2 : ""+i2;

                datetime[0] = i+"-"+m+"-"+d;
                datetime[2] = d+"."+m+"."+i;
                timePicker.show();
            }
        }, c.get(Calendar.YEAR), c.get(Calendar.MONTH), c.get(Calendar.DAY_OF_MONTH));
        datePicker.getDatePicker().setMinDate(c.getTimeInMillis());
        datePicker.getDatePicker().setMaxDate(c.getTimeInMillis() + 604800000);

        datePicker.show();
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

                        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/editDumpDesc.php", getApplicationContext(),
                                response -> {
                                    Log.i("BM_REQUEST_INFO", "Editdesc response: "+response);
                                    if (response.equals("1")){
                                        Toast.makeText(getApplicationContext(), R.string.description_edited, Toast.LENGTH_LONG).show();
                                        descriptionView.setText(newDesc);
                                        description = newDesc;

                                        descriptionView.setVisibility(View.VISIBLE);
                                        findViewById(R.id.dumpview_description_separator).setVisibility(View.VISIBLE);
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

    public void back(View view) {
        onBackPressed();
    }
}