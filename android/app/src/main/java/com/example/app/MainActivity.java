package com.example.app;

import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.util.Log;

import com.epicshaggy.biometric.NativeBiometric;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    private static final String TAG = "MainActivity";

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        // correct the capgo plugin registration
        // Register the NativeBiometric plugin
        registerPlugin(NativeBiometric.class);
    }
    @Override
    public void onStart() {
        super.onStart();
        // Get the intent that started the activity
        Intent intent = getIntent();
        if (intent != null && intent.getAction() != null && intent.getAction().equals(Intent.ACTION_VIEW)) {
            Uri uri = intent.getData();
            if (uri != null) {
                String url = uri.toString();
                Log.d(TAG, "Deep link URL: " + url);
                // Load the deep link URL in the webview
                this.loadUrl(url);
            } else {
                Log.e(TAG, "Deep link URI is null");
            }
        } else {
            Log.d(TAG, "No deep link intent found");
        }
    }

    private void loadUrl(String url) {
        getBridge().getWebView().loadUrl(url);
    }
}