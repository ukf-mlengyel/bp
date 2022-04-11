<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=0.7">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <!-- mapbox -->
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.6.0/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.6.0/mapbox-gl.css' rel='stylesheet' />

    <link href="css/style.css" rel="stylesheet">
    <title>bin me</title>
</head>
<body>
    <?php require "web/header.php"; ?>
    <br>

        <?php
            require_once "class/web/WebElements.php";
            if(!isset($_SESSION["user_id"])){
                echo '<div class="container-md">' . WebElements::infoBox("Registrácie sú momentálne obmedzené, pre prístup do aplikácie musí byť účet overený administrátorom.");
                echo '
                        <div class="container">
                            <div class="row">
                                <div class="col-lg">
                                    <h3>Čo je bin me?</h3>
                                    <p>Bin me je mobilná a webová aplikácia ktorá uľahčuje nahlasovanie čiernych skládok.</p>
                                    <p>Po prihlásení a overení účtu máte možnosť:</p>
                                    <ul>
                                        <li>Hľadať a pridávať nelegálne skládky vo vašom okolí</li>
                                        <li>Plánovať skupinové čistenie</li>
                                        <li>Vyhľadávať odpadkové koše, kontajnery, zberné dvory</li>
                                        <li>Získavať body za plnenie aktivít</li>
                                    </ul>
                                    
                                    <h5>Ako aplikácia funguje?</h5>
                                    <ol>
                                        <li>Po prihlásení do aplikácie sa zobrazí mapa vášho okolia</li>
                                        <li>Na základe umiestnenia (GPS) dokážete do mapy pridať zistenú skládku</li>
                                        <li>Otvorí sa diskusia k danej skládke</li>
                                        <li>Naplánuje sa čistiaca akcia, ktorej sa zúčastní skupina ľudí</li>
                                    </ol>
                                    <h5>Za každú akciu v aplikácii dostáva používateľ body:</h5>
                                    <ul>
                                        <li>Pridanie skládky alebo koša</li>
                                        <li>Účasť na čistiacej akcii</li>
                                        <li>Získanie likov na príspevky a komentáre</li>
                                    </ul>
                                </div>
                                <div class="col-auto">
                                    <img src="images/web/mockup.png">
                                </div>
                            </div>
                        </div>
                    </div>
                ';
                include "web/footer.php";
            }else{
                include "web/dumpmap.php";
            }
        ?>
    </div>
</body>
</html>