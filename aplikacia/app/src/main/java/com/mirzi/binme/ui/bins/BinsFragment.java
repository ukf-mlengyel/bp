package com.mirzi.binme.ui.bins;

import static android.app.Activity.RESULT_OK;

import android.animation.Animator;
import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.util.Log;
import android.view.Gravity;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.SwitchCompat;
import androidx.fragment.app.Fragment;
import androidx.lifecycle.ViewModelProvider;

import com.android.volley.AuthFailureError;
import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.toolbox.StringRequest;
import com.mapbox.android.core.permissions.PermissionsListener;
import com.mapbox.android.core.permissions.PermissionsManager;
import com.mapbox.geojson.Point;
import com.mapbox.mapboxsdk.Mapbox;
import com.mapbox.mapboxsdk.annotations.Icon;
import com.mapbox.mapboxsdk.annotations.IconFactory;
import com.mapbox.mapboxsdk.annotations.MarkerOptions;
import com.mapbox.mapboxsdk.geometry.LatLng;
import com.mapbox.mapboxsdk.location.LocationComponent;
import com.mapbox.mapboxsdk.location.LocationComponentActivationOptions;
import com.mapbox.mapboxsdk.location.OnIndicatorPositionChangedListener;
import com.mapbox.mapboxsdk.location.modes.CameraMode;
import com.mapbox.mapboxsdk.location.modes.RenderMode;
import com.mapbox.mapboxsdk.maps.MapView;
import com.mapbox.mapboxsdk.maps.MapboxMap;
import com.mapbox.mapboxsdk.maps.OnMapReadyCallback;
import com.mapbox.mapboxsdk.maps.Style;
import com.mirzi.binme.AddBinActivity;
import com.mirzi.binme.BinViewActivity;
import com.mirzi.binme.Helper.BM;
import com.mirzi.binme.Helper.BMRequestQueue;
import com.mirzi.binme.Helper.SessionHelper;
import com.mirzi.binme.Helper.StringRequestSession;
import com.mirzi.binme.R;
import com.mirzi.binme.databinding.FragmentBinsBinding;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;
import java.util.List;
import java.util.Objects;

public class BinsFragment extends Fragment implements OnMapReadyCallback, PermissionsListener {

    private FragmentBinsBinding binding;

    private MapView mapView;
    private MapboxMap map;
    private PermissionsManager permissionsManager;
    private LocationComponent locationComponent;

    private RequestQueue queue;
    private double lastLon, lastLat;
    private int filter;

    private View filterSelector, addButton;

    private String[] bintypes = new String[8];
    private SwitchCompat[] filterSwitches = new SwitchCompat[8];

    private IconFactory iconFactory;
    Icon icon;

    public View onCreateView(@NonNull LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        Mapbox.getInstance(getContext(), getString(R.string.mapbox_access_token));
        setHasOptionsMenu(true);

        BinsViewModel binsViewModel = new ViewModelProvider(this).get(BinsViewModel.class);
        binding = FragmentBinsBinding.inflate(inflater, container, false);
        View root = binding.getRoot();

        mapView = root.findViewById(R.id.map_bins);
        mapView.onCreate(savedInstanceState);
        mapView.getMapAsync(this);

        filterSelector = root.findViewById(R.id.binmap_filter_selector);
        addButton = root.findViewById(R.id.binmap_add_button);

        addButton.setOnClickListener(view -> {
            if (locationComponent.getLastKnownLocation() != null){
                Intent intent = new Intent(this.getActivity(), AddBinActivity.class);
                intent.putExtra("lon", locationComponent.getLastKnownLocation().getLongitude());
                intent.putExtra("lat", locationComponent.getLastKnownLocation().getLatitude());
                startActivityForResult(intent, 10);
            }else{
                Toast.makeText(getContext(), R.string.location_find_error, Toast.LENGTH_LONG).show();
            }
        });

        root.findViewById(R.id.binmap_apply_filter_button).setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View view) {
                changeFilter(view);
            }
        });

        try {
            filter = Integer.parseInt(SessionHelper.getPreference(getContext(), "bin_filter"));
        }catch (Exception e){
            filter = 255;
            SessionHelper.setPreference(getContext(), "bin_filter", "255");
        }

        // strings
        for (int i = 0; i<8; i++){
            String name = "bin_type_icon_"+(int)Math.pow(2, i);
            int id = getContext()
                    .getResources()
                    .getIdentifier(name, "string", getContext().getPackageName());

            bintypes[i] = getString(id);
        }

        // switches
        for (int i = 0; i<8; i++){
            int check = (int)Math.pow(2, i);

            String name = "filter_switch"+check;
            int id = getContext()
                    .getResources()
                    .getIdentifier(name, "id", getContext().getPackageName());

            filterSwitches[i] = root.findViewById(id);
            if ((check & filter) == check) filterSwitches[i].setChecked(true);
        }

        iconFactory = IconFactory.getInstance(getContext());
        icon = iconFactory.fromResource(R.mipmap.pin_red);

        return root;
    }

    @Override
    public void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (resultCode == RESULT_OK){
            switch (requestCode){
                case 10:
                    map.addMarker(new MarkerOptions()
                            .position(new LatLng(
                                    data.getDoubleExtra("lat", locationComponent.getLastKnownLocation().getLatitude()),
                                    data.getDoubleExtra("lon", locationComponent.getLastKnownLocation().getLongitude())))
                            .title(data.getStringExtra("id"))
                            .icon(icon));
                    break;
                case 11:
                    refreshMarkers(locationComponent.getLastKnownLocation().getLongitude(), locationComponent.getLastKnownLocation().getLatitude());
            }
        }
    }

    @Override
    public void onMapReady(@NonNull MapboxMap mapboxMap) {
        BinsFragment.this.map = mapboxMap;
        map.setStyle(Style.MAPBOX_STREETS, new Style.OnStyleLoaded(){
            @Override
            public void onStyleLoaded(@NonNull Style style){
                enableLocationComponent(style);
            }
        });

        int bottomLogoMargin = (int) (50 * getResources().getDisplayMetrics().density);
        map.getUiSettings().setAttributionMargins(map.getUiSettings().getAttributionMarginLeft(),0,0,bottomLogoMargin);
        map.getUiSettings().setLogoMargins(map.getUiSettings().getLogoMarginLeft(),0,0,bottomLogoMargin);
        map.getUiSettings().setCompassGravity(Gravity.START);
        map.getUiSettings().setCompassMargins(0, bottomLogoMargin, 0, 0);

        map.setOnMarkerClickListener(marker -> {
            Intent intent = new Intent(getActivity(), BinViewActivity.class);
            intent.putExtra("id", Integer.parseInt(marker.getTitle()));
            try{
                intent.putExtra("lon", locationComponent.getLastKnownLocation().getLongitude());
                intent.putExtra("lat", locationComponent.getLastKnownLocation().getLatitude());
            }catch (Exception e){ /* i'm lazy */ }

            startActivityForResult(intent, 11);
            return true;
        });
    }

    @SuppressWarnings( {"MissingPermission"})
    private void enableLocationComponent(@NonNull Style loadedMapStyle) {
        // Check if permissions are enabled and if not request
        if (PermissionsManager.areLocationPermissionsGranted(getContext())) {

            // Get an instance of the component
            locationComponent = map.getLocationComponent();

            // Activate with options
            locationComponent.activateLocationComponent(
                    LocationComponentActivationOptions.builder(getContext(), loadedMapStyle).build());

            // Enable to make component visible
            locationComponent.setLocationComponentEnabled(true);

            // Set the component's camera mode
            locationComponent.setCameraMode(CameraMode.TRACKING);

            // Set the component's render mode
            locationComponent.setRenderMode(RenderMode.COMPASS);

            try {
                Log.i("BM_MAP_INFO", "Refreshing bin markers from cache");
                setMarkers(new JSONArray(SessionHelper.getPreference(getContext(), "bin_markers_cache")));
            }catch (Exception e){
                Log.e("BM_MAP_ERR", "Bin marker json file is invalid "+e.getMessage());
            }


            locationComponent.addOnIndicatorPositionChangedListener(new OnIndicatorPositionChangedListener() {
                @Override
                public void onIndicatorPositionChanged(@NonNull Point point) {
                    if (Math.abs(lastLat - point.latitude()) >= 0.03 || Math.abs(lastLon - point.longitude()) >= 0.05){
                        refreshMarkers(point.longitude(), point.latitude());
                    }
                }
            });
        } else {
            permissionsManager = new PermissionsManager(this);
            permissionsManager.requestLocationPermissions(this.getActivity());
        }
    }

    private void refreshMarkers(double lon, double lat){
        Log.i("BM_MAP_INFO", String.format("Refreshing bin markers for lon %f lat %f", lon, lat));
        lastLon = lon;
        lastLat = lat;

        // get markers from server
        queue = BMRequestQueue.getInstance(getContext()).getRequestQueue();

        String url = BM.SERVER_URL+"/api/getBins.php";
        StringRequest request = new StringRequestSession(Request.Method.POST, url, getContext(),
                response -> {
                    Log.i("BM_MAP_RESPONSE", "bin response: "+response);
                    SessionHelper.setPreference(getContext(), "bin_markers_cache", response);
                    try {
                        JSONArray json = new JSONArray(response);
                        setMarkers(json);
                        Toast.makeText(getContext(), R.string.refresh_done, Toast.LENGTH_SHORT).show();
                    } catch (Exception e) {
                        e.printStackTrace();
                    }
                }, error -> { Toast.makeText(getContext(), R.string.connect_error, Toast.LENGTH_LONG).show(); }
        ){
            @Override
            public String getBodyContentType() {
                return "application/json; charset=utf-8";
            }

            @Override
            public byte[] getBody() throws AuthFailureError {
                try {
                    JSONObject json = new JSONObject();
                    json.put("lon", lon);
                    json.put("lat", lat);
                    json.put("filter", filter);
                    return json.toString().getBytes(StandardCharsets.UTF_8);
                }catch (JSONException e){
                    Log.e("BM_JSON_ERROR", e.getMessage());
                    return null;
                }
            }
        };

        queue.add(request);
        // add markers to map
    }

    private void setMarkers(JSONArray jsonArray) throws JSONException {
        // clear markers
        map.clear();

        String typesStr = "";
        for (int o = 0; o<8; o++){
            int check = (int)Math.pow(2, o);
            if ((check & filter) == check) typesStr+=bintypes[o];
        }

        ((TextView)getActivity().findViewById(R.id.binmap_typesText)).setText(typesStr);
        Objects.requireNonNull(((AppCompatActivity) getActivity()).getSupportActionBar()).setTitle(getString(R.string.title_bins) + " (" + jsonArray.length() + ")");
        for (int i = 0; i < jsonArray.length(); i++){
            JSONObject obj = jsonArray.getJSONObject(i);

            // add new marker
            map.addMarker(new MarkerOptions()
                    .position(new LatLng(obj.getDouble("lat"), obj.getDouble("lon")))
                    .title(obj.getString("i"))
                    .icon(icon));
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        permissionsManager.onRequestPermissionsResult(requestCode, permissions, grantResults);
    }

    @Override
    public void onExplanationNeeded(List<String> permissionsToExplain) {
        Toast.makeText(getContext(), R.string.location_request, Toast.LENGTH_LONG).show();
    }

    @Override
    public void onPermissionResult(boolean b) {
        if (b){
            map.getStyle(new Style.OnStyleLoaded() {
                @Override
                public void onStyleLoaded(@NonNull Style style) {
                    enableLocationComponent(style);
                }
            });
        }else{
            Toast.makeText(getContext(), R.string.location_request_denied, Toast.LENGTH_LONG).show();
        }
    }

    @Override
    public void onStart() {
        super.onStart();
        mapView.onStart();
    }

    @Override
    public void onResume() {
        super.onResume();
        mapView.onResume();
    }

    @Override
    public void onPause() {
        super.onPause();
        mapView.onPause();
    }

    @Override
    public void onStop() {
        super.onStop();
        mapView.onStop();
    }

    @Override
    public void onSaveInstanceState(@NonNull Bundle outState) {
        super.onSaveInstanceState(outState);
        mapView.onSaveInstanceState(outState);
    }

    @Override
    public void onLowMemory() {
        super.onLowMemory();
        mapView.onLowMemory();
    }

    @Override
    public void onDestroyView() {
        super.onDestroyView();
        mapView.onDestroy();
        binding = null;
    }

    @Override
    public void onCreateOptionsMenu(@NonNull Menu menu, @NonNull MenuInflater inflater) {
        inflater.inflate(R.menu.menu_bins, menu);
    }

    @Override
    public boolean onOptionsItemSelected(@NonNull MenuItem item) {
        switch (item.getItemId()){
            case R.id.binmap_refresh_button:
                View button = getActivity().findViewById(R.id.binmap_refresh_button);
                if (locationComponent.getLastKnownLocation() != null){
                    refreshMarkers(locationComponent.getLastKnownLocation().getLongitude(), locationComponent.getLastKnownLocation().getLatitude());
                }else{
                    try {
                        locationComponent.isLocationComponentActivated();
                    }catch (Exception e){
                        enableLocationComponent(map.getStyle());
                    }
                }
                button.setVisibility(View.GONE);
                new Handler(Looper.getMainLooper()).postDelayed(() -> button.setVisibility(View.VISIBLE), 10000);
                return true;
            case R.id.binmap_filter_button:
                toggleFilterSelectorDisplay();
                return true;
            default:
                return super.onOptionsItemSelected(item);
        }
    }

    public void changeFilter(View view){
        toggleFilterSelectorDisplay();

        int newfilter = 0;
        for (int o = 0; o<8; o++){
            if (filterSwitches[o].isChecked()){
                newfilter += (int)Math.pow(2, o);
            }
        }

        filter = newfilter;
        if (filter == 0) filter = 255;

        if (locationComponent.getLastKnownLocation() != null) {
            SessionHelper.setPreference(getContext(), "bin_filter", ""+filter);
            refreshMarkers(locationComponent.getLastKnownLocation().getLongitude(), locationComponent.getLastKnownLocation().getLatitude());
        }
    }

    private void toggleFilterSelectorDisplay(){
        int visibility = filterSelector.getVisibility();
        if (visibility == View.VISIBLE){
            filterSelector.setClickable(false);
            filterSelector.animate()
                    .alpha(0f)
                    .setDuration(300)
                    .setListener(new Animator.AnimatorListener() {
                        @Override
                        public void onAnimationEnd(Animator animator) {
                            filterSelector.setVisibility(View.GONE);
                            addButton.setVisibility(View.VISIBLE);
                        }

                        @Override
                        public void onAnimationStart(Animator animator) {}
                        @Override
                        public void onAnimationCancel(Animator animator) {}
                        @Override
                        public void onAnimationRepeat(Animator animator) {}
                    });

        }else{
            filterSelector.setClickable(false);
            filterSelector.setVisibility(View.VISIBLE);
            filterSelector.setAlpha(0f);
            addButton.setVisibility(View.INVISIBLE);
            filterSelector.animate()
                    .alpha(1f)
                    .setDuration(300)
                    .setListener(new Animator.AnimatorListener() {
                        @Override
                        public void onAnimationEnd(Animator animator) {
                            filterSelector.setClickable(true);
                        }

                        @Override
                        public void onAnimationStart(Animator animator) {}
                        @Override
                        public void onAnimationCancel(Animator animator) {}
                        @Override
                        public void onAnimationRepeat(Animator animator) {}
                    });

            addButton.setVisibility(View.GONE);
        }
    }
}