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
        include_once "class/database/DumpDB.php";
        include_once "class/database/BinDB.php";
        include_once "class/actions/Comment.php";

        const loginErrString = "Profil neexistuje.";

        if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST["userid"]))
            echo getProfile(intval($_REQUEST["userid"]));
        else if (isset($_SESSION["user_id"]))
            header('Location: profile.php?userid='.$_SESSION["user_id"]);
        else
            echo WebElements::errorBox(loginErrString);

        function getProfile(int $userid) : string{
            if (Antiflood::exceedsLimits("get_user")) return WebElements::errorBox("Zasielate požiadavky príliš často. Skúste to neskôr.");

            $details = UserDB::getUserDetails($userid);

            // ak profil neexistuje alebo nie je overený
            if(!isset($details) || !($details["flags"] & 1))
                return WebElements::errorBox(loginErrString);

            $description = $details["description"] == "" ? "<i>Bez popisu</i>" : $details["description"];

            $dumpList = WebElements::profileList(DumpDB::getDumpListForUser($userid, true), "", "dump");
            if(!empty($dumpList))
                $dumpList = "<br><h4 class='d-inline'>Naposledy pridané skládky</h4><a class='btn btn-secondary float-end' href='dumpList.php?userid={$userid}'>Zobraziť všetky</a><br><br><div class='container-md section-inset'>" . $dumpList . "</div>";

            $binList = WebElements::profileList(BinDB::getBinListForUser($userid, true), "", "bin");
            if(!empty($binList))
                $binList = "<br><h4 class='d-inline'>Naposledy pridané koše</h4><a class='btn btn-secondary float-end' href='binlist.php?userid={$userid}'>Zobraziť všetky</a><br><br><div class='container-md section-inset'>" . $binList . "</div>";

            // ak je uzivatel prihlaseny tak zobrazujeme aj like stav
            $loggedin = isset($_SESSION["user_id"]);

            $commentList = WebElements::commentList(Comment::getComments(3, $userid, 0), "", $loggedin);
            $commentBox = $loggedin ? " <div id='commentarea'>
                                            <textarea id='main-textarea' class='form-control' maxlength='300' onkeyup='updateTextCounter(this, 300, \"textCounter\")' onclick='updateTextCounter(this, 300, \"textCounter\")' ></textarea>
                                            <h4 id='textCounter' class='float-start'></h4>
                                            <script src='js/TextareaCounter.js'></script>
                                            <button id='post-button' class='btn btn-secondary btn-margin-v float-end' onclick='addComment(this, 3, $userid, \"\", {$_SESSION["user_id"]}, \"{$_SESSION["username"]}\", \"{$_SESSION["userpicture"]}\")'><span id='spinner' style='display: none' class='spinner-border spinner-border-sm'></span> Pridať komentár</button>
                                            <script src='js/Comment.js'></script>
                                        </div>
                                        <br><br>" : "<h6>Nie ste prihlásený, pre pridávanie komentárov je potrebné sa prihlásiť.</h6>";

            $commentList = "<br>
                            <h4 class='d-inline'>Komentáre</h4><br><br>
                            <div class='container-md section-inset'>
                                $commentBox $commentList
                            </div>";

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

                <div class='container-md section-inset'>
                    <div class='row'>
                        <div class='col profilepic-container'>
                            <img class='profilepic profilepic-big' src='images/user/{$details["image"]}.jpg'> <br>
                        </div>
                        <div class='col'>
                            <h2>{$details["username"]} <span class='badge bg-secondary'>{$details["points"]}</span></h2>
                            
                            <p class='text-break'>$description</p>
                            
                            <p>Členom od {$details["creation_date"]}</p>
                        </div>
                    </div>
                </div>
                
                $dumpList
                $binList
                
                $commentList
            ";
        }
    ?>
    <?php include "web/footer.php"; ?>
</div>
</body>
</html>