<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=0.7">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="css/style.css" rel="stylesheet">
    <title>Prihlásiť sa</title>
</head>
<body>
    <?php require "web/header.php"; ?>
    <div class="container-md">
        <br>
        <?php
        require_once "class/web/WebElements.php";
        if(!isset($_SESSION["user_id"])) echo WebElements::infoBox("Registrácie sú momentálne obmedzené, pre prístup do aplikácie musí byť účet overený administrátorom.");
        ?>
        <h1>Prihlásiť sa</h1>
        <div class="container section-inset">
            <div class="row">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>">
                    <label for="username">Meno</label>
                    <input class="form-control" type="text" name="username" required>
                    <label for="password">Heslo</label>
                    <input class="form-control" type="password" name="password" required>
                    <br>
                    <input class="btn btn-secondary float-end" type="submit" value="Prihlásiť sa">
                </form>
            </div>
        </div>

        <br>

        <?php
            if(isset($_SESSION["user_id"])) header('Location: index.php');

            require_once "class/actions/Account.php";
            include_once "class/web/WebElements.php";

            if($_SERVER["REQUEST_METHOD"] == "POST"){
                // získame uživateľom zadané údaje
                $username = $_REQUEST["username"];
                $password = $_REQUEST["password"];

                echo WebElements::errorBox(Account::login($username, $password, true));
        }
        ?>

        <br>
        <?php include "web/footer.php"; ?>
    </div>

</body>
</html>