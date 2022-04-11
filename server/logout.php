<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=0.7">
    <title>Boli ste odhlásený</title>
</head>
<body>
    <?php
        session_start();
        session_unset();
        session_destroy();
        header('Location: index.php');
    ?>
    <h1>Boli ste odhlásený.</h1>
</body>
</html>