package com.example.app;

import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;

import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
    }

    @Override
    public void onStart() {
        super.onStart();
        // Get the intent that started the activity
        Intent intent = getIntent();
        if (intent != null && intent.getAction() != null && intent.getAction().equals(Intent.ACTION_VIEW)) {
            Uri uri = intent.getData();
            if (uri != null) {
                // Load the deep link URL in the webview
                this.loadUrl(uri.toString());
            }
        }
    }

    private void loadUrl(String url) {
        getBridge().getWebView().loadUrl(url);
    }
}
