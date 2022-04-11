<html lang="sk">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=0.7">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="../../css/style.css">
    <title>bin me</title>
</head>
<body>

<?php
require "../../class/actions/Account.php";

echo "<h1 class='text-center'>";
session_start();
if(isset($_POST["submit"])){
    if(Account::setLocation($_POST["lon"], $_POST["lat"])){
        header('Location: ../../index.php');
    }else{
        echo "Vyberte miesto na slovensku.";
        header('Refresh: 2; url=../../index.php');
    }

} else {
    echo Account::resetLocation();
    header('Location: ../../index.php');
}
echo "</h1>";

?>
</body>
</html>