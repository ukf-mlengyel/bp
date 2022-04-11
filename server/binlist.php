<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=0.7">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="css/style.css" rel="stylesheet">
    <title>Profil</title>
</head>
<body>
<?php require "web/header.php"; ?>
<div class="container-md">
    <br>
    <?php
    include_once "class/Antiflood.php";
    include_once "class/web/WebElements.php";
    include_once "class/database/UserDB.php";
    include_once "class/database/BinDB.php";

    const loginErrString = "Profil neexistuje.";

    if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST["userid"]))
        echo getBinList(intval($_REQUEST["userid"]));
    else if (isset($_SESSION["user_id"]))
        header('Location: binlist.php?userid='.$_SESSION["user_id"]);
    else
        echo WebElements::errorBox(loginErrString);

    function getBinList(int $userid) : string{
        if (Antiflood::exceedsLimits("get_user")) return WebElements::errorBox("Zasielate požiadavky príliš často. Skúste to neskôr.");

        $details = UserDB::getUserDetails($userid);

        // ak profil neexistuje alebo nie je overený
        if(!isset($details) || !($details["flags"] & 1))
            return WebElements::errorBox(loginErrString);

        $bins = BinDB::getBinListForUser($userid, false);

        $dumpList = WebElements::profileList($bins, "", "bin");

        if(!empty($dumpList)){
            $dumpList = "<div class='container-md section-inset'>" . $dumpList . "</div>";
        }

        return "
                <div id='dump-detail-background'>
                    <div id='dump-detail-frame'>
                        <iframe id='dump-detail-iframe'>
                        
                        </iframe>
                        <div id='dump-detail-spinner' class='spinner-border'></div>
                    </div>
                    <div id='close-button' class='btn-close' onclick='hideDetails()'></div>
                </div>
                
                <script src='js/DetailFrame.js'></script>
                
                <div class='container'>
                    <div class='row'>
                        <div class='col col-auto'><img class='profilepic' src='images/user_thumb/{$details["image"]}.jpg'></div>
                        <div class='col'><h1>Koše používateľa {$details["username"]}</h1></div>
                    </div>
                </div>
                
                <br>

                $dumpList
            ";
    }
    ?>
    <?php include "web/footer.php"; ?>
</div>
</body>
</html>