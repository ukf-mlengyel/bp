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
    header('Location: index.php');
}else{
    include "web/binmap.php";
}
?>
</div>
</body>
</html>