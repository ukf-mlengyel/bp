package com.mirzi.binme;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;
import androidx.core.content.FileProvider;

import android.Manifest;
import android.app.ProgressDialog;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.os.Handler;
import android.os.Looper;
import android.provider.MediaStore;
import android.provider.Settings;
import android.util.Log;
import android.view.View;
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
import com.mirzi.binme.Helper.MultipartRequestSession;

import java.io.File;
import java.io.IOException;
import java.text.SimpleDateFormat;
import java.util.Arrays;
import java.util.Date;
import java.util.HashMap;
import java.util.Map;

public class AddDumpActivity extends AppCompatActivity {

    ImageView imageView;
    EditText editText;
    Button addButton;

    Bitmap bitmap = null;

    String currentPhotoPath;

    RequestQueue queue;

    double lon,lat;

    ActivityResultLauncher<String> requestPermissionLauncher;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_add_dump);

        requestPermissionLauncher =
                registerForActivityResult(new ActivityResultContracts.RequestPermission(), isGranted -> {
                    if (isGranted) {
                        openImagePicker(null);
                    } else {
                        Toast.makeText(getApplicationContext(), R.string.need_file_access, Toast.LENGTH_LONG).show();
                    }
                });

        Intent intent = getIntent();
        lon = intent.getDoubleExtra("lon", 0);
        lat = intent.getDoubleExtra("lat", 0);

        //((TextView)findViewById(R.id.textView9)).setText(lon+", "+lat);

        imageView = findViewById(R.id.add_dump_img_preview);
        editText = findViewById(R.id.add_dump_description);
        addButton = findViewById(R.id.add_dump_button);

        queue = BMRequestQueue.getInstance(this.getApplicationContext()).getRequestQueue();
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

    public void addDump(View view) {
        if(bitmap == null) {
            Toast.makeText(getApplicationContext(), R.string.image_not_selected, Toast.LENGTH_LONG).show();
            return;
        }

        addButton.setClickable(false);
        new Handler(Looper.getMainLooper()).postDelayed(() -> addButton.setClickable(true), 10000);

        ProgressDialog progress = new ProgressDialog(this);
        progress.setMessage(getString(R.string.progress_add_dump));
        progress.setCancelable(false);
        progress.show();

        MultipartRequestSession request = new MultipartRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/addDump.php", getApplicationContext(),
                response -> {
                    String data = new String(response.data);

                    Log.i("BM_DUMP_UPLOAD_RESPONSE", data);
                    if (data.substring(0,1).equals("1")){
                        Toast.makeText(getApplicationContext(), R.string.dump_added, Toast.LENGTH_LONG).show();

                        Intent returnIntent = new Intent();
                        returnIntent.putExtra("lon", lon);
                        returnIntent.putExtra("lat", lat);
                        returnIntent.putExtra("id", data.substring(2));
                        setResult(RESULT_OK, returnIntent);

                        finish();
                        progress.dismiss();
                    }
                    else Toast.makeText(getApplicationContext(), data, Toast.LENGTH_LONG).show();

                    progress.dismiss();
                },
                error -> {
                    Toast.makeText(getApplicationContext(), R.string.add_error, Toast.LENGTH_LONG).show();
                }){
            @Override
            protected Map<String, String> getParams(){
                Map<String, String> params = new HashMap<>();
                params.put("lon", String.valueOf(lon));
                params.put("lat", String.valueOf(lat));
                params.put("description", editText.getText().toString());
                return params;
            }

            @Override
            protected Map<String, DataPart> getByteData() {
                Map<String, DataPart> params = new HashMap<>();
                long imagename = System.currentTimeMillis();
                params.put("picture", new DataPart(imagename + ".png", getFileDataFromDrawable(bitmap)));
                return params;
            }
        };

        queue.add(request);
    }

    public void back(View view) {
        onBackPressed();
    }
}