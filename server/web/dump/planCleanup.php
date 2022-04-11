<html lang="sk">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=0.7">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="../../css/style.css">
    <title>bin me</title>
</head>
<body>

<?php
require "../../class/actions/Dump.php";

echo "<h1 class='text-center'>";
if(isset($_POST["submit"])){
    session_start();
    $result = Dump::planCleanup( $_POST["dumpid"], $_POST["date"]." ".$_POST["time"]);
    echo $result == "1" ? "Čistenie bolo naplánované." : $result;
}
echo "</h1>";
header('Refresh: 1; url=view.php?dumpid='.$_POST["dumpid"]);
?>
</body>
</html>