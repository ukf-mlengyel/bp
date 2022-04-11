<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=0.7">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="css/style.css" rel="stylesheet">
    <title>Zoznam Používateľov</title>
</head>
<body>
<?php require_once "web/header.php"; ?>
<div class="container-md">
    <br>

<?php
    require_once "class/database/UserDB.php";
    require_once "class/web/WebElements.php";
    require_once "class/Antiflood.php";

    if (Antiflood::exceedsLimits("get_userlist")) {echo WebElements::errorBox("Zasielate požiadavky príliš často. Skúste to neskôr."); goto end;};

    // koľko výsledkov sa bude zobrazovať naraz
    $limit = 30;
    // začiatočný bod
    $offset = 0;
    // získame číslo stránky ak existuje
    if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_REQUEST["page"]))
        $offset = filter_var($_REQUEST["page"], FILTER_SANITIZE_NUMBER_INT);

    // filter
    $offset = $offset < 0 ? 0 : $offset;

    $result = UserDB::getUserList($limit, $offset);

    $result_count = 0;

    if($result->num_rows > 0){
        $usercount = UserDB::getUserCount();
        echo "<div class='container'><div class='row'>";
            while($row = $result->fetch_assoc()){
                echo "<div class='col-4 col-sm-4 col-md-4 col-lg-3 col-xxl-2 user-card text-truncate'>
                        <a class='text-decoration-none' href='profile.php?userid={$row["id"]}'>
                            <img class='profilepic img-fluid' src='images/user/{$row["image"]}.jpg'>
                            <div class='card-text-overlay'><b>{$row["username"]}</b><br>{$row["points"]}</div>
                        </a>
                      </div>
                      ";
                $result_count++;
            }
        echo "</div></div>";

        // Vypocitame pocet stranok
        $pagecount = ceil($usercount / $limit);

        // zakazeme tlacidla ak sme na zaciatku alebo na konci
        $back_enable = $offset > 0 ? "" : "disabled";
        $fw_enable = $offset+1 < $pagecount ? "" : "disabled";

        // pagination
        echo "
            <ul class='pagination justify-content-center'>
                <li class='page-item $back_enable'>
                    <a class='page-link' href='userlist.php?page=". $offset-1 ."' aria-label='Previous'>
                        <span aria-hidden='true'>&laquo;</span>
                    </a>
                </li>";
                for ($i = 0; $i<$pagecount; $i++){
                    $activestr = $i == $offset ? "active" : "";
                    echo "<li class='page-item $activestr'><a class='page-link' href='userlist.php?page=$i'>". $i+1 ."</a></li>";
                }
                echo "<li class='page-item $fw_enable'>
                    <a class='page-link' href='userlist.php?page=". $offset+1 ."' aria-label='Next'>
                        <span aria-hidden='true'>&raquo;</span>
                    </a>
                </li>
            </ul>
            <p class='text-center'><i>Počet registrovaných používateľov: $usercount</i></p>
        ";

            /*
        // zobraz tlacidlo naspat ak nie sme na prvej strane
        if($offset > 0)
            echo "<a href='userlist.php?page=". $offset - 1 ."'>&lt;&lt;</a>";

        echo "<b>". $offset+1 ."</b>";

        // zobraz tlacidlo dopredu ak je dosiahnutý limit výsledkov
        if($limit*($offset+1) <= $usercount)
            echo "<a href='userlist.php?page=". $offset + 1 ."'>&gt;&gt;</a>";


            */
    }else echo "Žiadne výsledky.";

    end:

    include "web/footer.php";
?>
</div>
</body>
</html>