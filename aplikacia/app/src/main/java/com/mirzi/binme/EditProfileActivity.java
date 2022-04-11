package com.mirzi.binme;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;
import androidx.core.content.FileProvider;

import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.net.Uri;
import android.os.Bundle;
import android.os.Environment;
import android.provider.MediaStore;
import android.provider.Settings;
import android.util.Log;
import android.view.View;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.Toast;

import com.android.volley.AuthFailureError;
import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.mirzi.binme.Helper.BM;
import com.mirzi.binme.Helper.BMRequestQueue;
import com.mirzi.binme.Helper.MultipartRequestSession;
import com.mirzi.binme.Helper.SessionHelper;
import com.mirzi.binme.Helper.StringRequestSession;
import com.squareup.picasso.Picasso;

import org.json.JSONException;
import org.json.JSONObject;

import java.io.File;
import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.HashMap;
import java.util.Map;

public class EditProfileActivity extends AppCompatActivity {

    ImageView imageView;
    EditText et;
    Bitmap bitmap = null;

    RequestQueue queue;
    String userid, previousDesc, currentPhotoPath;

    ActivityResultLauncher<String> requestPermissionLauncher;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_edit_profile);

        requestPermissionLauncher =
                registerForActivityResult(new ActivityResultContracts.RequestPermission(), isGranted -> {
                    if (isGranted) {
                        openImagePicker(null);
                    } else {
                        Toast.makeText(getApplicationContext(), R.string.need_file_access, Toast.LENGTH_LONG).show();
                    }
                });

        queue = BMRequestQueue.getInstance(this.getApplicationContext()).getRequestQueue();
        et = findViewById(R.id.editprofile_description);
        imageView = findViewById(R.id.editprofile_img_preview);

        userid = SessionHelper.getPreference(getApplicationContext(), "image");

        Picasso.get().load(BM.SERVER_URL+"/images/user/"+userid+".jpg").into(imageView);

        // get desc
        StringRequestSession request = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/getDesc.php", getApplicationContext(),
                response -> {
                    et.setText(response);
                    previousDesc = response;
                }, error -> {
            Toast.makeText(getApplicationContext(), getString(R.string.connect_error), Toast.LENGTH_LONG).show();
            finish();
        });

        queue.add(request);
    }

    public void openImagePicker(View view) {
        if (ContextCompat.checkSelfPermission(getApplicationContext(), Manifest.permission.READ_EXTERNAL_STORAGE) != PackageManager.PERMISSION_GRANTED) {
            requestPermissionLauncher.launch(Manifest.permission.READ_EXTERNAL_STORAGE);
        }else{
            Intent i = new Intent(Intent.ACTION_PICK, MediaStore.Images.Media.EXTERNAL_CONTENT_URI);
            startActivityForResult(i, 100);
        }
    }

    public void openCameraIntent(View view) {
        Intent takePictureIntent = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
        // Ensure that there's a camera activity to handle the intent
        if (takePictureIntent.resolveActivity(getPackageManager()) != null) {
            // Create the File where the photo should go
            File photoFile = null;
            try {
                photoFile = createImageFile();
            } catch (IOException ex) {
                Toast.makeText(getApplicationContext(), R.string.error, Toast.LENGTH_SHORT).show();
            }
            // Continue only if the File was successfully created
            if (photoFile != null) {
                Uri photoURI = FileProvider.getUriForFile(this,
                        "com.example.android.fileprovider",
                        photoFile);
                takePictureIntent.putExtra(MediaStore.EXTRA_OUTPUT, photoURI);
                startActivityForResult(takePictureIntent, 101);
            }
        }else{
            Toast.makeText(getApplicationContext(), R.string.error, Toast.LENGTH_SHORT).show();
        }
    }

    private File createImageFile() throws IOException {
        // Create an image file name
        String timeStamp = new SimpleDateFormat("yyyyMMdd_HHmmss").format(new Date());
        String imageFileName = "JPEG_" + timeStamp + "_";
        File storageDir = getExternalFilesDir(Environment.DIRECTORY_PICTURES);
        File image = File.createTempFile(
                imageFileName,  /* prefix */
                ".jpg",         /* suffix */
                storageDir      /* directory */
        );

        // Save a file: path for use with ACTION_VIEW intents
        currentPhotoPath = image.getAbsolutePath();
        return image;
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (requestCode == 100 && resultCode == RESULT_OK && data != null) {
            Uri imageUri = data.getData();
            try {
                bitmap = BM.scaleBitmap(MediaStore.Images.Media.getBitmap(this.getContentResolver(), imageUri), 1000, 1000);
                imageView.setImageBitmap(bitmap);
            } catch (IOException e) {
                e.printStackTrace();
            }
        }else if (requestCode == 101 && resultCode == RESULT_OK) {
            bitmap = BM.scaleBitmap((BitmapFactory.decodeFile(currentPhotoPath)), 1000, 1000);
            imageView.setImageBitmap(bitmap);
        }
    }

    public void confirmChanges(View view) {

        MultipartRequestSession picRequest = new MultipartRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/editProfilePic.php", getApplicationContext(),
                response -> {
                    String data = new String(response.data);
                    Log.i("BM_PIC_UPLOAD_RESPONSE", data);
                    Toast.makeText(getApplicationContext(), data, Toast.LENGTH_LONG).show();

                    bitmap = null;
                },
                error -> {
                    Toast.makeText(getApplicationContext(), R.string.connect_error, Toast.LENGTH_LONG).show();
                }){
            @Override
            protected Map<String, DataPart> getByteData() {
                Map<String, DataPart> params = new HashMap<>();
                long imagename = System.currentTimeMillis();
                params.put("picture", new DataPart(imagename + ".png", getFileDataFromDrawable(bitmap)));
                return params;
            }
        };

        StringRequestSession descRequest = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/editProfileDesc.php", getApplicationContext(),
                response -> {
                    Log.i("BM_DESC_CHANGE_RESPONSE", response);
                    Toast.makeText(getApplicationContext(), response, Toast.LENGTH_LONG).show();

                    previousDesc = et.getText().toString();
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
                    json.put("d", et.getText().toString());
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        // ak bol popis zmeneny
        if (!previousDesc.equals(et.getText().toString())){
            queue.add(descRequest);
        }

        // ak bol obrazok zmeneny
        if (bitmap != null){
            queue.add(picRequest);
        }
    }

    public void back(View view) {
        onBackPressed();
    }
}