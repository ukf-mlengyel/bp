package com.mirzi.binme.ui.mainmenu;

import android.app.AlertDialog;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.SharedPreferences;
import android.net.Uri;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.ImageView;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.fragment.app.Fragment;
import androidx.lifecycle.ViewModelProvider;

import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.mirzi.binme.BinListActivity;
import com.mirzi.binme.CleanupsListActivity;
import com.mirzi.binme.DumpListActivity;
import com.mirzi.binme.EditProfileActivity;
import com.mirzi.binme.Helper.BM;
import com.mirzi.binme.Helper.BMRequestQueue;
import com.mirzi.binme.Helper.SessionHelper;
import com.mirzi.binme.Helper.StringRequestSession;
import com.mirzi.binme.IndexActivity;
import com.mirzi.binme.MainActivity;
import com.mirzi.binme.ProfileActivity;
import com.mirzi.binme.R;

import com.mirzi.binme.UserListActivity;
import com.mirzi.binme.databinding.FragmentMainmenuBinding;
import com.squareup.picasso.Picasso;

import org.json.JSONException;
import org.json.JSONObject;

public class MainMenuFragment extends Fragment {

    private FragmentMainmenuBinding binding;
    RequestQueue queue;
    String url = BM.SERVER_URL + "/api/getMyProfile.php";
    StringRequestSession request;

    public View onCreateView(@NonNull LayoutInflater inflater,
                             ViewGroup container, Bundle savedInstanceState) {
        MainMenuViewModel mainMenuViewModel =
                new ViewModelProvider(this).get(MainMenuViewModel.class);

        binding = FragmentMainmenuBinding.inflate(inflater, container, false);
        View root = binding.getRoot();

        setHasOptionsMenu(true);

        // ----- LISTVIEW -----

        ListView listView = root.findViewById(R.id.main_menu_listview);

        String[] options = {
                "Profil",
                "Moje skládky",
                "Moje koše",
                "Moje čistiace akcie",
                "Používatelia",
                "Web",
                "Odhlásiť sa",
        };

        ArrayAdapter<String> adapter = new ArrayAdapter<>(getContext(), android.R.layout.simple_list_item_1, android.R.id.text1, options);

        listView.setOnItemClickListener((adapterView, view, i, l) -> {
            Intent intent = null;
            switch (i) {
                case 0:
                    intent = new Intent(this.getActivity(), ProfileActivity.class);
                    intent.putExtra("id", SessionHelper.getPreference(getContext(), "user_id"));
                    break;
                case 1:
                    intent = new Intent(this.getActivity(), DumpListActivity.class);
                    intent.putExtra("id", SessionHelper.getPreference(getContext(), "user_id"));
                    break;
                case 2:
                    intent = new Intent(this.getActivity(), BinListActivity.class);
                    intent.putExtra("id", SessionHelper.getPreference(getContext(), "user_id"));
                    break;
                case 3:
                    intent = new Intent(this.getActivity(), CleanupsListActivity.class);
                    break;
                case 4:
                    intent = new Intent(this.getActivity(), UserListActivity.class);
                    break;
                case 5:
                    intent = new Intent(Intent.ACTION_VIEW);
                    intent.setData(Uri.parse(BM.SERVER_URL));
                    break;
                case 6:
                    StringRequestSession logoutrequest = new StringRequestSession(Request.Method.POST, BM.SERVER_URL+"/api/logout.php", getContext(),
                            response -> {
                                logout();
                            }, error -> { Toast.makeText(getContext(), R.string.connect_error, Toast.LENGTH_LONG).show(); }
                    );

                    new AlertDialog.Builder(getContext())
                            .setMessage(R.string.logout_dialog)
                            .setPositiveButton(R.string.yes, new DialogInterface.OnClickListener() {
                                public void onClick(DialogInterface dialog, int button) {
                                    queue.add(logoutrequest);
                                }})
                            .setNegativeButton(R.string.no, null).show();
                    break;
                default:
                    Toast.makeText(getContext(), options[i], Toast.LENGTH_SHORT).show();
                    break;
            }

            if (intent != null) startActivity(intent);
        });

        listView.setAdapter(adapter);

        // ----- USER DETAILS -----
        queue = BMRequestQueue.getInstance(getContext()).getRequestQueue();
        // get user details
        request = new StringRequestSession(Request.Method.POST, url, getContext(),
                response -> {
                    SessionHelper.setPreference(getContext(), "userinfo", response);
                    Toast.makeText(getContext(), R.string.menu_info_refreshed, Toast.LENGTH_LONG).show();
                    showUserDetails(response, root);
                }, error -> { Toast.makeText(getContext(), R.string.connect_error, Toast.LENGTH_LONG).show(); }
        );

        String userdetails = SessionHelper.getPreference(getContext(), "userinfo");

        if(userdetails.equals("")){
            Log.i("BM_INFO", "No userdetails found, downloading from server.");
            queue.add(request);
        }else{
            showUserDetails(userdetails, root);
        }

        // ----- USER DETAILS BUTTONS -----
        //root.findViewById(R.id.main_menu_refresh_btn).setOnClickListener(view -> refreshUserDetails(view));

        return root;
    }

    public void refreshUserDetails(MenuItem view){
        queue.add(request);
        view.setVisible(false);
        new Handler(Looper.getMainLooper()).postDelayed(() -> view.setVisible(true), 10000);
    }

    public void showUserDetails(String jsonstr, View root){
        try {
            JSONObject json = new JSONObject(jsonstr);
            ((TextView)root.findViewById(R.id.main_menu_name)).setText(json.getString("u"));
            ((TextView)root.findViewById(R.id.main_menu_subtext)).setText(
                    String.format(getString(R.string.menu_summary),
                            json.getString("d"),
                            json.getString("dc"),
                            json.getString("bc"))
            );
            ((TextView)root.findViewById(R.id.main_menu_points)).setText(json.getString("p"));

            Picasso.get().load(BM.SERVER_URL+"/images/user/"+json.getString("i")+".jpg").into((ImageView) root.findViewById(R.id.main_menu_img));
        } catch (JSONException e) {
            e.printStackTrace();
        }
    }

    @Override
    public void onDestroyView() {
        super.onDestroyView();
        binding = null;
    }

    @Override
    public void onCreateOptionsMenu(@NonNull Menu menu, @NonNull MenuInflater inflater) {
        inflater.inflate(R.menu.menu_mainmenu, menu);
    }

    @Override
    public boolean onOptionsItemSelected(@NonNull MenuItem item) {
        switch (item.getItemId()){
            case R.id.mainmenu_refresh_button:
                refreshUserDetails(item);
                return true;
            case R.id.mainmenu_edit_button:
                Intent intent = new Intent(getActivity(), EditProfileActivity.class);
                startActivity(intent);
            default:
                return super.onOptionsItemSelected(item);
        }
    }

    private void logout(){
        SessionHelper.clearPreferences(getContext());
        Intent intent = new Intent(getActivity(), MainActivity.class);
        Toast.makeText(getContext(), R.string.logout_success, Toast.LENGTH_LONG).show();
        startActivity(intent);
        getActivity().finishAffinity();
    }
}