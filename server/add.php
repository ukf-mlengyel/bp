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

    <div class="container-md">
        <h1>SUPER SECRET ADMIN PAGE!!!!!!</h1>
        <h4>add dump</h4>
        <form action="web/dump/add.php" method="post" enctype="multipart/form-data">
            lon, lat
            <input class="form-control" type="text" id="lon" name="lon">
            <input class="form-control" type="text" id="lat" name="lat">
            image
            <input class="form-control" type="file" accept="image/jpeg, image/png" name="picture" id="picture">
            status(0,1,2,3)
            <input class="form-control" type="text" name="status">
            description
            <textarea class="form-control" id="description" maxlength="1000" name="description"></textarea>
            <input type="submit" class="btn btn-secondary float-end" name="submit">
        </form>


        <br><br><br>
        <h4>add bin</h4>
        <form action="web/bin/add.php" method="post" enctype="multipart/form-data">
            lon, lat
            <input class="form-control" type="text" id="lon" name="lon">
            <input class="form-control" type="text" id="lat" name="lat">
            image
            <input class="form-control" type="file" accept="image/jpeg, image/png" name="picture" id="picture">
            types(0-255)
            <input class="form-control" type="text" name="types">
            description
            <textarea class="form-control" id="description" maxlength="1000" name="description"></textarea>
            <input type="submit" class="btn btn-secondary float-end" name="submit">
        </form>
        <?php include "class/web/WebElements.php"; echo WebElements::mapSelector("");?>
        <?php include "web/footer.php"; ?>
    </div>
</body>
</html>