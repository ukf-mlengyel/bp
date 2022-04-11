package com.mirzi.binme;

import androidx.appcompat.app.AppCompatActivity;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;

public class IndexActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_index);
    }

    public void gotoLogin(View view) {
        startActivity(new Intent(this, LoginActivity.class));
    }

    public void gotoRegister(View view) {
        startActivity(new Intent(this, RegisterActivity.class));
    }
}