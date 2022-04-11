<html lang="sk">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=0.7">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="../../css/style.css">
    <title>bin me</title>
</head>
<body>
<div class="container-md">
    <?php
    require_once "../../class/actions/Like.php";
    require_once "../../class/actions/Bin.php";
    require_once "../../class/web/WebElements.php";
    require_once "../../class/Calculator.php";
    require_once "../../class/actions/Comment.php";

    $bin = isset($_GET["binid"]) ? Bin::getBin($_GET["binid"]) : array();

    // zistime typy
    function getTypesString($types) : string{
        global $fs;
        $types_values = array(
            "&#x26AA Komunál",
            "&#x1F7E1 Plast",
            "&#x1F535 Papier",
            "&#x1F7E2 Sklo",
            "&#x1F7E3 Kovy",
            "&#x1F7E4 Bio",
            "&#x1F534 Elektro",
            "&#x26AB Zberný dvor"
        );

        $offset = 0;
        $output = "";
        for ($i = 1; $i<=128; $i*=2){
            if($i & $types){
                $output.=$types_values[$offset]."<br>";
                array_push($fs, "checked");
            }else{
                array_push($fs, "");
            }
            $offset++;
        }

        return $output;
    }

    if(empty($bin)) echo WebElements::errorBox("Kôš neexistuje.");
    else{
        session_start();

        // vypocitame vzdialenost ak je v requeste lon a lat
        $distance_to_point = "";
        if (isset($_GET["lon"]) && isset($_GET["lat"])){
            $lon = $_GET["lon"];
            $lat = $_GET["lat"];
            // ak su suradnice validne
            if (! ($lat > 90 || $lat < -90 || $lon > 180 || $lon < -180)){
                $distance = round( Calculator::calculateDistance($lon, $lat, $bin["location_lon"], $bin["location_lat"]), 1);
                $distance_to_point = $distance > 1000 ? round($distance/1000, 1) . "km od vás" : $distance . "m od vás";
            }
        }

        // elementy
        $likeButton = "";
        $editButton = "";

        // hodnoty
        $likeCount = 0;
        $liked = false;
        $loggedin = isset($_SESSION["user_id"]);

        $binid = $_GET["binid"];
        $fs = array();

        $types = getTypesString($bin["types"]);

        $likeCount = Like::getLikeCount(1, $binid);
        if ($loggedin){
            $liked = Like::getLike(1, $binid) ? "1" : "0";

            $likedIcon = ["&#x1F90D", "&#x2764"];
            $likeButton = " <button onclick='like(this, 1, $binid, \"../../\")' class='btn btn-secondary btn-margin' data-s='{$liked}' data-c='{$likeCount}'>{$likedIcon[$liked]} $likeCount</button>
                            <script src='../../js/Like.js'></script>";

            if ($_SESSION["user_id"] == $bin["user_id"]){
                $editButton = "
                    <script src='../../js/ViewActions.js'></script>
                    <div class='d-flex justify-content-center'>
                        <button onclick='showEditBox(document.getElementById(\"edit-description-form\"))' class='btn btn-secondary btn-margin'>&#x270FUpraviť popis</button>
                        <button onclick='showAction(document.getElementById(\"filter-picker\"))' class='btn btn-secondary btn-margin'>&#x270FUpraviť typy</button>
                        <button onclick='deleteBin($binid, \"../../\")' class='btn btn-secondary btn-margin'>&#x1F5D1Odstrániť</button>
                    </div>
                    
                    <div style='display: none' id='edit-description-form'>
                        <br>
                        <h4>Upraviť popis</h4><br>
                        <div class='container mb-3 section-inset'>
                            <div class='row'>
                                <form action='../../web/bin/changeDesc.php' method='post'>
                                    <input type='hidden' name='binid' value='$binid'>
                                    <textarea id='edit-desc-textarea' class='form-control' onkeyup='updateTextCounter(this, 1000, \"editTextCounter\")' onclick='updateTextCounter(this, 1000, \"editTextCounter\")' id='description' maxlength='1000' name='description'></textarea>
                                    <h4 class='float-start' id='editTextCounter'></h4><br>
                                    <input type='submit' class='btn btn-secondary btn-margin-t float-end' value='Uložiť' name='submit'>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <br>
                    <div id='filter-picker' class='align-items-center fullwidth' style='display: none'>
                        <h4 style='text-align: center'>Upraviť typy</h4>
                        <div style='width:200px; margin: auto; padding: 5px; text-align: right;' class='form-check form-switch section-inset'>
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
                            <button onclick='changeTypes($binid, \"../../\")' class='btn btn-secondary form-control'>Uložiť</button>
                        </div>
                    </div>
                ";
            }
        }else{
            $likeButton = "<h3 class='btn-margin'>&#x2764 {$likeCount}</h3>";
        }

        $commentList = WebElements::commentList(Comment::getComments(1, $binid, 0), "../../", $loggedin);
        $commentBox = $loggedin ? " <div id='commentarea'>
                                            <textarea id='main-textarea' class='form-control' maxlength='300' onkeyup='updateTextCounter(this, 300, \"textCounter\")' onclick='updateTextCounter(this, 300, \"textCounter\")' ></textarea>
                                            <h4 id='textCounter' class='float-start'></h4>
                                            <script src='../../js/TextareaCounter.js'></script>
                                            <button id='post-button' class='btn btn-secondary btn-margin-v float-end' onclick='addComment(this, 1, $binid, \"../../\", {$_SESSION["user_id"]}, \"{$_SESSION["username"]}\", \"{$_SESSION["userpicture"]}\")'><span id='spinner' style='display: none' class='spinner-border spinner-border-sm'></span> Pridať komentár</button>
                                            <script src='../../js/Comment.js'></script>
                                        </div>
                                        <br><br>" : "<h6>Nie ste prihlásený, pre pridávanie komentárov je potrebné sa prihlásiť.</h6>";

        $commentList = "<br>
                            <h4>Komentáre</h4><br>
                            <div class='container-md section-inset'>
                                $commentBox $commentList
                            </div>";
        echo "
            <br>
            <div class='container'>
                <div class='container'>
                    <div class='row section-inset'>
                        <div class='col-md-4'><img class='profilepic img-fluid' src='../../images/bin/" .$bin["image"]. ".jpg'><br></div>
                        <div class='col'>
                            <h1>{$bin["location_name"]}</h1>
                            <h4>$distance_to_point</h4>
                            <h6>$types</h6>
                            <div class='row'>
                                <div class='col-auto'><a href='../../profile.php?userid={$bin["user_id"]}' target='_blank'><img class='profilepic' src='../../images/user_thumb/" .$bin["user_image"]. ".jpg'></a></div>
                                <div class='col-auto'><p>Pridal {$bin["username"]}<br>{$bin["creation_date"]}</p></div>
                            </div>
                            <p id='dump-description' class='text-break'>{$bin["description"]}</p>
                        </div>
                    </div>
                </div>
                <br>
                <div class='section-buttons'>
                <div style='margin-bottom: 10px' class='d-flex justify-content-center'>
                    $likeButton
                    <a class='btn btn-secondary btn-margin' target='_blank' href='https://maps.google.com/?q={$bin["location_lat"]},{$bin["location_lon"]}'>&#x1F5FAOtvoriť v Google Maps</a>
                </div>
                $editButton
                </div>

                $commentList
            </div>
        ";
    }
    ?>
</div>
</body>
</html>