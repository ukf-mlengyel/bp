<?php
require_once "class/actions/Account.php";
require_once "class/actions/Dump.php";
require_once "class/Antiflood.php";

const MAPBOX_ACCESS_TOKEN = "pk.eyJ1IjoibWlyemkiLCJhIjoiY2t2djFmemd4MTcyOTJwbTlpd2tsb3dtMiJ9.KrkpLWMACeL0d_SRk1UjZQ";

$location = Account::getLocation();

// ak uživateľ nemá na profile uložené umiestnenie
if($location["preferred_location_name"] == NULL){
    echo '<div class="container-md">' . WebElements::infoBox("Pre pokračovanie si nastavte vaše umiestnenie.");
    echo "<div class='container section-inset'>" . WebElements::mapSelector("web/user/changeLocation.php") . "</div>";
}else if (!Antiflood::exceedsLimits('get_dumpmap')){
    $lon = $location["preferred_location_lon"];
    $lat = $location["preferred_location_lat"];

    $range = isset($_GET["range"]) && $_GET["range"] <= 50 ? $_GET["range"] : 10;

    // cestoviny
    $selectedRange = array("", "", "");

    switch ($range){
        case 10: $selectedRange["0"] = "selected"; break;
        case 25: $selectedRange["1"] = "selected"; break;
        case 50: $selectedRange["2"] = "selected"; break;
    }

    // get dump list from db
    $list = Dump::getDumpArr($lon, $lat, $range);

    echo "
        <div class='fullscreen'>
        
        <div id='dump-detail-background'>
            <div id='dump-detail-frame'>
                <iframe id='dump-detail-iframe'>
                
                </iframe>
                <div id='dump-detail-spinner' class='spinner-border'></div>
            </div>
            <div id='close-button' class='btn-close' onclick='hideDetails()'></div>
        </div>
        
        <div class='mapmenu_l text-white-shadow'>
            <p id='map_info'>".str_replace(',','<br>',$location['preferred_location_name'])."</p>
        </div>
        
        <div class='mapmenu_r text-white-shadow'>
            <form id='rangeselector' method='get' action='".htmlspecialchars($_SERVER['PHP_SELF'])."'>
                <select onchange='changeRange(this.options[this.selectedIndex].value)' class='form-control' style='margin-bottom: 5px' name='range' id='range'>
                    <option value='10' {$selectedRange["0"]}>Okolie: 5 km</option>
                    <option value='25' {$selectedRange["1"]}>Okolie: 10 km</option>
                    <option value='50' {$selectedRange["2"]}>Okolie: 30 km</option>     
                </select>
            </form>
            <a class='btn btn-secondary' style='width: 100%' href='web/user/changeLocation.php' role='button'>Zmeniť umiestnenie</a><div style='text-align: right; line-height: 0.5'>
            <br>
            <p id='status-0'>Nevyčistená &#x1F534</p>
            <p id='status-1'>Naplánované čistenie &#x1F7E2</p>
            <p id='status-2'>Čistenie prebieha &#x1F535</p>
            </div>
        </div>
        
        <div id='map' class='dumpmap'></div>
        <br>
        
        <script>
            const init_coords = [ $lon, $lat ];
            const dist = {$range} * 0.01
            const bounds = [
                [$lon - dist, $lat - dist/2],
                [$lon + dist, $lat + dist/2]
            ]
            
            // false positive thx phpstorm 
            const dumpList = $list;
            
            mapboxgl.accessToken = '". MAPBOX_ACCESS_TOKEN ."';
            const map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: init_coords,
                zoom: 14,
                maxBounds: bounds,
            });
            
            const marker_el = document.createElement('div');
            marker_el.className = 'marker';
            const marker = new mapboxgl.Marker(marker_el).setLngLat(init_coords).addTo(map);
            
            let statuses = [];
            for (let i = 0; i <= 2; i++) statuses.push(document.getElementById('status-'+i));
            function displayType(type){
                for (const status of statuses) status.style.opacity = '0.1';
                statuses[type].style.opacity = '1';
            }
            function resetType(){
                for (const status of statuses) status.style.opacity = '1';
            }
            
            // add markers to map
            for (const i in dumpList){
                const marker_el = document.createElement('div');
                marker_el.className = 'marker_dump dump-status-'+dumpList[i].status;
                marker_el.style.background = 'url(\'images/dump_thumb/'+dumpList[i].image+'.jpg\')';
                marker_el.style.backgroundSize = 'cover';
                marker_el.style.transition = 'width 0.2s, height 0.2s';
                marker_el.addEventListener('mouseenter', ()=>{displayType(dumpList[i].status)});
                marker_el.addEventListener('mouseleave', ()=>{resetType()})
                marker_el.addEventListener('click', () => {showDetails(dumpList[i].id, true, 'dump')});
                new mapboxgl.Marker(marker_el).setLngLat([dumpList[i].location_lon, dumpList[i].location_lat]).addTo(map);
            }
            
            // display amount
            document.getElementById('map_info').innerHTML += ('<br>Skládok: '+dumpList.length);
            
            function changeRange(range){
                document.getElementById('rangeselector').submit();
            }
        </script>
        
        <script src='js/DetailFrame.js'></script>

        </div>
        
        
        <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js' integrity='sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM' crossorigin='anonymous'></script>
    ";
}else echo '<div class="container-md">' . WebElements::errorBox("Zasielate požiadavky príliš často. Skúste to neskôr.") . "</div>";
