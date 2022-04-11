<?php
require_once "class/actions/Account.php";
require_once "class/actions/Bin.php";
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

    $filter = isset($_GET["filter"]) ? (int)$_GET["filter"] : 0;


    // get bin list from db
    $list = Bin::getBinArr($lon, $lat, $range, $filter);

    $fs = array();
    for($i = 1; $i <= 128; $i*=2) {
        if ($filter & $i) array_push($fs, "checked");
        else array_push($fs, "");
    }

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
            <a class='btn btn-secondary' style='width: 100%; margin-bottom:5px' href='web/user/changeLocation.php' role='button'>Zmeniť umiestnenie</a><br>
            <a class='btn btn-secondary' style='width: 100%' onclick='toggleFilter()' role='button'>Upraviť filter</a>
            <div style='text-align: right; line-height: 0.5'>
                <br>
                <div id='filter-picker' style='display: none'>
                    <div class='form-check form-switch filter-form'>
                        <input type='checkbox' class='form-check-input' id='t1' name='t1' {$fs[0]}>
                        <label class='form-check-label' for='t1'>Komunál &#x26AA</label><br>
                        <input type='checkbox' class='form-check-input' id='t2' name='t2' {$fs[1]}>
                        <label class='form-check-label' for='t2'>Plast &#x1F7E1</label><br>
                        <input type='checkbox' class='form-check-input' id='t4' name='t4' {$fs[2]}>
                        <label class='form-check-label' for='t4'>Papier &#x1F535</label><br>
                        <input type='checkbox' class='form-check-input' id='t8' name='t8' {$fs[3]}>
                        <label class='form-check-label' for='t8'>Sklo &#x1F7E2</label><br>
                        <input type='checkbox' class='form-check-input' id='t16' name='t16' {$fs[4]}>
                        <label class='form-check-label' for='t16'>Kovy &#x1F7E3</label><br>
                        <input type='checkbox' class='form-check-input' id='t32' name='t32' {$fs[5]}>
                        <label class='form-check-label' for='t32'>Bio &#x1F7E4</label><br>
                        <input type='checkbox' class='form-check-input' id='t64' name='t64' {$fs[6]}>
                        <label class='form-check-label' for='t64'>Elektro &#x1F534</label><br>
                        <input type='checkbox' class='form-check-input' id='t128' name='t128' {$fs[7]}>
                        <label class='form-check-label' for='t128'>Zberný dvor &#x26AB</label><br><br>
                        <button onclick='filterBins()' class='btn btn-secondary form-control'>Filtrovať</button>
                    </div>
                </div>
                <br>
                <div id='types-div' style='pointer-events: none; display: none'>
                    <p id='type-1'>Komunál &#x26AA</p>
                    <p id='type-2'>Plast &#x1F7E1</p>
                    <p id='type-4'>Papier &#x1F535</p>
                    <p id='type-8'>Sklo &#x1F7E2</p>
                    <p id='type-16'>Kovy &#x1F7E3</p>
                    <p id='type-32'>Bio &#x1F7E4</p>
                    <p id='type-64'>Elektro &#x1F534</p>
                    <p id='type-128'>Zberný dvor &#x26AB</p>
                </div>
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
            for (let i = 1; i <= 128; i*=2) statuses.push(document.getElementById('type-'+i));
            const typesdiv = document.getElementById('types-div');
            
            function displayType(type){
                let statusIndex = 0;
                for (let i = 1; i <= 128; i*=2){
                    if (i & type) statuses[statusIndex].style.opacity = '1';
                    else statuses[statusIndex].style.opacity = '0.1';
                    statusIndex++;
                }
                typesdiv.style.display = 'inline';
            }
            function resetType(){
                typesdiv.style.display = 'none';
            }
            
            // add markers to map
            for (const i in dumpList){
                const marker_el = document.createElement('div');
                marker_el.className = 'marker_bin';
                marker_el.style.background = 'url(\'images/bin_thumb/'+dumpList[i].image+'.jpg\')';
                marker_el.style.backgroundSize = 'cover';
                marker_el.style.transition = 'width 0.2s, height 0.2s';
                marker_el.addEventListener('click', () => {showDetails(dumpList[i].id, true, 'bin')});
                marker_el.addEventListener('mouseenter', ()=>{displayType(dumpList[i].types)});
                marker_el.addEventListener('mouseleave', ()=>{resetType()})
                
                new mapboxgl.Marker(marker_el).setLngLat([dumpList[i].location_lon, dumpList[i].location_lat]).addTo(map);
            }
            
            // display amount
            document.getElementById('map_info').innerHTML += ('<br>Košov: '+dumpList.length);
        </script>
        
        <script src='js/DetailFrame.js'></script>

        </div>
        
        
        <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js' integrity='sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM' crossorigin='anonymous'></script>
    ";
}else echo '<div class="container-md">' . WebElements::errorBox("Zasielate požiadavky príliš často. Skúste to neskôr.") . "</div>";
