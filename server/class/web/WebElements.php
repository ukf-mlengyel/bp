<?php
class WebElements{
    const MAPBOX_ACCESS_TOKEN = "pk.eyJ1IjoibWlyemkiLCJhIjoiY2t2djFmemd4MTcyOTJwbTlpd2tsb3dtMiJ9.KrkpLWMACeL0d_SRk1UjZQ";

    public static function errorBox(string $message) : string{
        return "
        <svg xmlns='http://www.w3.org/2000/svg' style='display: none;'>
            <symbol id='exclamation-triangle-fill' fill='currentColor' viewBox='0 0 16 16'>
                <path d='M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z'/>
            </symbol>
        </svg>
        <div class='alert alert-danger d-flex align-items-center' role='alert'>
            <svg class='bi flex-shrink-0 me-2' width='24' height='24' role='img' aria-label='Danger:'><use xlink:href='#exclamation-triangle-fill'/></svg>
            <div>$message</div>
        </div>
        ";
    }

    public static function infoBox(string $message) : string{
        return "
        <svg xmlns='http://www.w3.org/2000/svg' style='display: none;'>
            <symbol id='info-fill' fill='currentColor' viewBox='0 0 16 16'>
                <path d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z'/>
            </symbol>
        </svg>
        <div class='alert alert-primary d-flex align-items-center' role='alert'>
            <svg class='bi flex-shrink-0 me-2' width='24' height='24' role='img' aria-label='Info:'><use xlink:href='#info-fill'/></svg>
            <div>$message</div>
        </div>
        ";
    }

    public static function successBox(string $message) : string{
        return "
        <svg xmlns='http://www.w3.org/2000/svg' style='display: none;'>
            <symbol id='check-circle-fill' fill='currentColor' viewBox='0 0 16 16'>
                <path d='M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z'/>
            </symbol>
        </svg>
        <div class='alert alert-success d-flex align-items-center' role='alert'>
            <svg class='bi flex-shrink-0 me-2' width='24' height='24' role='img' aria-label='Info:'><use xlink:href='#check-circle-fill'/></svg>
            <div>$message</div>
        </div>
        ";
    }

    public static function mapSelector(string $submit_location) : string{
        $picker = !strlen($submit_location) == 0 ?
            "<form method='post' action='$submit_location'>
                <input type='hidden' id='lon' name='lon' value='' readonly>
                <input type='hidden' id='lat' name='lat' value='' readonly>
                <input class='form-control btn btn-secondary' type='submit' name='submit' value='Uložiť'>
            </form>" : "";

        return "
            <div id='map' class='mapselector'></div>
            <br>
            
            $picker
            
            <script>
                // ukf coords :)
                const init_coords = [18.091302, 48.308232];
                const bounds = [
                    [16.7246, 47.6679],
                    [22.6602, 49.7]
                ];
                
                let form_lon = document.getElementById('lon');
                let form_lat = document.getElementById('lat');
                
                form_lon.value = init_coords[0];
                form_lat.value = init_coords[1];
                
                mapboxgl.accessToken = '". self::MAPBOX_ACCESS_TOKEN ."';
                const map = new mapboxgl.Map({
                    container: 'map',
                    style: 'mapbox://styles/mapbox/streets-v11',
                    center: init_coords,
                    maxBounds: bounds,
                    zoom: 14
                });
                
                const marker_el = document.createElement('div');
                marker_el.className = 'marker';
                const marker = new mapboxgl.Marker(marker_el).setLngLat(init_coords).addTo(map);
                
                map.on('click', (e) => {
                    marker.setLngLat(e.lngLat);
                    form_lon.value = e.lngLat.lng;
                    form_lat.value = e.lngLat.lat;
                });
                
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition((position) => {
                        map.jumpTo({center: [position.coords.longitude, position.coords.latitude]});
                        marker.setLngLat([position.coords.longitude, position.coords.latitude]);
                        form_lon.value = position.coords.longitude;
                        form_lat.value = position.coords.latitude;
                    });
                }
            </script>
        ";
    }

    public static function profileList(array $dumps, string $server_root, string $type) : string{
        // hrozny neporiadok je toto ale funguje to :)
        $dumpList = "";
        $listsize = sizeof($dumps)-1;
        foreach ($dumps as $key=>$dump){
            // build string
            $loc_name = substr($dump["location_name"], 0, -10);
            $str = $type == "dump" ?
                "<div onclick='showDetails({$dump["id"]}, false, \"dump\")' class='col-md dump-list-item dump-list-status-". $dump["status"] ."'>" :
                "<div onclick='showDetails({$dump["id"]}, false, \"bin\")' class='col-md dump-list-item'>";

            $str.="<div class='row'>
                            <div class='col-auto'><img class='dump-list-item-image' src='{$server_root}images/{$type}_thumb/". $dump["image"] .".jpg'></div>
                            <div class='col'>$loc_name<br>{$dump["creation_date"]}</div>
                        </div>
                    </div>
                ";

            if ($key % 2 == 0){
                $str = "<div class='row'>" . $str;
                if($key == $listsize) $str .= "</div>";
            }else{
                $str .= "</div>";
            }
            $dumpList .= $str;
        }

        return $dumpList;
    }

    public static function commentList(array $comments, string $server_root, bool $logged_in) : string{
        // dalsi neporiadok
        $commentList = "<div id='comment-list'>";
        $likedIcon = ["&#x1F90D", "&#x2764"];
        $user_id = $_SESSION["user_id"] ?? "";

        foreach ($comments as $comment){
            $commentList .= "
                <div>
                <hr>
                <div class='row'>
                    <div class='col-auto'>
                        <a href='{$server_root}profile.php?userid={$comment["user_id"]}'>
                            <img class='profilepic' src='{$server_root}images/user_thumb/{$comment["image"]}.jpg'>
                        </a>
                    </div>
                    <div class='col'>
                        <h6>{$comment['username']} <span class='date-small'><i>{$comment['creation_date']}</i></span>";

            //like tlacitko podla prihlasenia
            if($logged_in){
                $commentList.= "<button onclick='like(this, 2, {$comment["id"]}, \"{$server_root}\")' class='float-end btn btn-secondary btn-small btn-small-margin' data-s='{$comment["isliked"]}' data-c='{$comment["likecount"]}'>{$likedIcon[$comment["isliked"]]} {$comment["likecount"]}</button>";
                if($comment["user_id"] == $user_id)
                    $commentList .= "<button onclick='removeComment(this.parentElement.parentElement.parentElement.parentElement, this, {$comment["id"]}, \"{$server_root}\")' class='float-end btn btn-secondary btn-small' >&#x1F5D1 Odstrániť</button>";
            }else{
                $commentList.="<span class='btn-small float-end'>&#x2764 {$comment["likecount"]}</span>";
            }


            $commentList.="</h6> 
                        <p class='no-p-margin'>{$comment['content']}</p>
                    </div>
                </div></div>
            ";


            //$commentList .= "userid {$comment['user_id']} crdate {$comment['creation_date']} content {$comment['content']} likecount {$comment['likecount']} isliked {$comment['isliked']}";
        }

        $commentList .= $logged_in ? "<script src='{$server_root}js/Like.js'></script>" : "";
        $commentList .= "</div>";

        return $commentList;
    }
}