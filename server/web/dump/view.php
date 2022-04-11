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
    require_once "../../class/actions/Dump.php";
    require_once "../../class/web/WebElements.php";
    require_once "../../class/Calculator.php";
    require_once "../../class/actions/Comment.php";

    $dump = isset($_GET["dumpid"]) ? Dump::getDump($_GET["dumpid"]) : array();

    if(empty($dump)) echo WebElements::errorBox("Skládka neexistuje.");
    else{
        session_start();

        // vypocitame vzdialenost ak je v requeste lon a lat
        $distance_to_point = "";
        if (isset($_GET["lon"]) && isset($_GET["lat"])){
            $lon = $_GET["lon"];
            $lat = $_GET["lat"];
            // ak su suradnice validne
            if (! ($lat > 90 || $lat < -90 || $lon > 180 || $lon < -180)){
                $distance = round( Calculator::calculateDistance($lon, $lat, $dump["location_lon"], $dump["location_lat"]), 1);
                $distance_to_point = $distance > 1000 ? round($distance/1000, 1) . "km od vás" : $distance . "m od vás";
            }
        }

        $status = array("&#x1F534 Nevyčistená", "&#x1F7E2 Naplánované čistenie", "&#x1F535 Čistenie prebieha", "&#x26AA Vyčistená");

        // elementy
        $likeButton = "";
        $editButton = "";

        // hodnoty
        $likeCount = 0;
        $liked = false;
        $loggedin = isset($_SESSION["user_id"]);

        $dumpid = $_GET["dumpid"];
        if ($loggedin){
            $likeCount = Like::getLikeCount(0, $dumpid);
            $liked = Like::getLike(0, $dumpid) ? "1" : "0";
            //$likedIcon = $liked ? "&#x2764" : "&#x1F90D";

            $likedIcon = ["&#x1F90D", "&#x2764"];
            $likeButton = " <button onclick='like(this, 0, $dumpid, \"../../\")' class='btn btn-secondary btn-margin' data-s='{$liked}' data-c='{$likeCount}'>{$likedIcon[$liked]} $likeCount</button>
                            <script src='../../js/Like.js'></script>";

            if ($_SESSION["user_id"] == $dump["user_id"]){
                $date = date("Y-m-d");
                $maxdate = date("Y-m-d", strtotime("+1 week"));
                $time = date("H:i", strtotime("+1 hour"));

                $status_btn_text = "";
                $status_btn = "true";
                if($dump["status"] == 3){
                    $status_btn_text = "ne";
                    $status_btn = "false";
                }

                $cleanup_btn_state = $dump["status"] == 1 ? "disabled" : "";

                $editButton = "
                    <script src='../../js/ViewActions.js'></script>
                    <div class='d-flex justify-content-center'>
                        <button onclick='showEditBox(document.getElementById(\"edit-description-form\"))' class='btn btn-secondary btn-margin'>&#x270FUpraviť popis</button>
                        <button onclick='showAction(document.getElementById(\"cleanup-event-form\"))' class='btn btn-secondary btn-margin' $cleanup_btn_state>&#x1F4C5Naplánovať čistenie</button>
                        <button onclick='markAsCleaned($dumpid, $status_btn, \"../../\")' class='btn btn-secondary btn-margin'>&#x2714Označiť ako {$status_btn_text}vyčistené</button>
                        <button onclick='deleteDump($dumpid, \"../../\")' class='btn btn-secondary btn-margin'>&#x1F5D1Odstrániť</button>
                    </div>
                    
                    <div style='display: none' id='edit-description-form'>
                        <br>
                        <h4>Upraviť popis</h4><br>
                        <div class='container mb-3 section-inset'>
                            <div class='row'>
                                <form action='../../web/dump/changeDesc.php' method='post'>
                                    <input type='hidden' name='dumpid' value='$dumpid'>
                                    <textarea id='edit-desc-textarea' class='form-control' onkeyup='updateTextCounter(this, 1000, \"editTextCounter\")' onclick='updateTextCounter(this, 1000, \"editTextCounter\")' id='description' maxlength='1000' name='description'></textarea>
                                    <h4 class='float-start' id='editTextCounter'></h4><br>
                                    <input type='submit' class='btn btn-secondary btn-margin-t float-end' value='Uložiť' name='submit'>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div style='display: none' id='cleanup-event-form'>
                        <br>
                        <h4>Naplánovať čistenie</h4><br>
                        <div class='container mb-3 section-inset'>
                            <form action='../../web/dump/planCleanup.php' method='post'>
                                <input type='hidden' name='dumpid' value='$dumpid'>
                                <input type='date' name='date' class='form-control' value='$date' min='$date' max='$maxdate' required>
                                <input type='time' name='time' class='form-control' value='$time' required>
                                <input type='submit' class='btn btn-secondary btn-margin-t-1 float-end' value='Potvrdiť' name='submit'><br>
                            </form>
                        </div>
                    </div>
                    
                        
                ";
            }
        }else{
            $likeCount = Like::getLikeCount(0, $dumpid);
            $likeButton = "<h3 class='btn-margin'>&#x2764 {$likeCount}</h3>";
        }

        $commentList = WebElements::commentList(Comment::getComments(0, $dumpid, 0), "../../", $loggedin);
        $commentBox = $loggedin ? " <div id='commentarea'>
                                            <textarea id='main-textarea' class='form-control' maxlength='300' onkeyup='updateTextCounter(this, 300, \"textCounter\")' onclick='updateTextCounter(this, 300, \"textCounter\")' ></textarea>
                                            <h4 id='textCounter' class='float-start'></h4>
                                            <script src='../../js/TextareaCounter.js'></script>
                                            <button id='post-button' class='btn btn-secondary btn-margin-v float-end' onclick='addComment(this, 0, $dumpid, \"../../\", {$_SESSION["user_id"]}, \"{$_SESSION["username"]}\", \"{$_SESSION["userpicture"]}\")'><span id='spinner' style='display: none' class='spinner-border spinner-border-sm'></span> Pridať komentár</button>
                                            <script src='../../js/Comment.js'></script>
                                        </div>
                                        <br><br>" : "<h6>Nie ste prihlásený, pre pridávanie komentárov je potrebné sa prihlásiť.</h6>";

        $commentList = "<br>
                            <h4>Komentáre</h4><br>
                            <div class='container-md section-inset'>
                                $commentBox $commentList
                            </div>";

        $planned_cleaup = $dump["status"] != 0 ? $dump["planned_cleanup"] : "";

        $attendanceSection = "";
        if($dump["status"] == 1 || $dump["status"] == 2 && $loggedin){

            // zistíme attendance používateľa
            $attendance = Dump::getUserAttendance($dumpid, $_SESSION["user_id"]);

            $attendBtnTxt = ($attendance == 1 || $attendance == 3) ? "Nechcem sa zúčastniť" : "Chcem sa zúčastniť";
            $attendBtn = "<form action='toggleAttendance.php' method='post'>
                                <input type='hidden' name='dumpid' value='$dumpid'>
                                <input type='submit' class='btn btn-success btn-margin-t float-end' value='$attendBtnTxt' name='submit'>
                          </form>";

            if ($attendance == 1) $attendanceSection .= WebElements::infoBox("Ste prihlásený na najbližšiu čistiacu akciu. Potvrďte vašu prítomnosť v aplikácii a dostanete body.");
            else if ($attendance == 2) $attendanceSection .= WebElements::successBox("Zúčastnili ste sa pri čistení tejto skládky, ďakujeme za vašu pomoc!");
            else if ($attendance == 3) $attendanceSection .= WebElements::infoBox("Ste prihlásený na najbližšiu čistiacu akciu. Nakoľko ste sa už v minulosti zúčastnili, neobdržíte viacej bodov.");

            // vytvorime zoznamy ludi
            $userlist1 = "";
            $userlist2 = "";

            $attendants_arr = Dump::getAttendants($dumpid, false);
            $len1 = sizeof($attendants_arr);
            foreach ($attendants_arr as $attendant){
                $userlist1.= "<a target='_blank' href='../../profile.php?userid={$attendant['user_id']}'>{$attendant['username']}</a>, ";
            }

            $attendants_arr = Dump::getAttendants($dumpid, true);
            $len2 = sizeof($attendants_arr);
            foreach ($attendants_arr as $attendant){
                $userlist2.= "<a target='_blank' href='../../profile.php?userid={$attendant['user_id']}'>{$attendant['username']}</a>, ";
            }

            $userlist1 = substr($userlist1, 0 , -2);
            $userlist2 = substr($userlist2, 0 , -2);

            // čistiaca akcia
            $attendanceSection .="
                <h4>Čistiaca akcia (od $planned_cleaup) $attendBtn</h4><br>
                <div style='padding-left: 10px' class='section-inset'>
                    Zúčastniť sa chce $len1 ľudí:<br>
                    $userlist1
                    <br>
                    Zúčastnilo sa $len2 ľudí:<br>
                    $userlist2
                </div>
            ";
        }

        echo "
            <br>
            <div class='container'>
                <div class='container'>
                    <div class='row section-inset'>
                        <div class='col-md-4'><img class='profilepic img-fluid' src='../../images/dump/" .$dump["image"]. ".jpg'><br></div>
                        <div class='col'>
                            <h1>{$dump["location_name"]}</h1><h4>$distance_to_point</h4>
                            <h6>{$status[$dump["status"]]}</h6>
                            <div class='row'>
                                <div class='col-auto'><a href='../../profile.php?userid={$dump["user_id"]}' target='_blank'><img class='profilepic' src='../../images/user_thumb/" .$dump["user_image"]. ".jpg'></a></div>
                                <div class='col-auto'><p>Pridal {$dump["username"]}<br>{$dump["creation_date"]}</p></div>
                            </div>
                            <p id='dump-description' class='text-break'>{$dump["description"]}</p>
                        </div>
                    </div>
                </div>
                <br>
                <div class='section-buttons'>
                <div style='margin-bottom: 10px' class='d-flex justify-content-center'>
                    $likeButton
                    <a class='btn btn-secondary btn-margin' target='_blank' href='https://maps.google.com/?q={$dump["location_lat"]},{$dump["location_lon"]}'>&#x1F5FAOtvoriť v Google Maps</a>
                </div>
                $editButton
                </div>
                
                <br>
                $attendanceSection

                $commentList
            </div>
        ";
    }
    ?>
</div>
</body>
</html>