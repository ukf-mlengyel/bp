<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=0.7">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="css/style.css" rel="stylesheet">
    <title>Upraviť profil</title>
</head>
<body onbeforeunload="return checkDataChanged()">
<?php
require "web/header.php";
include_once "class/web/WebElements.php";
include_once "class/database/UserDB.php";

if(!isset($_SESSION["user_id"]))
    header('Location: index.php');
?>

<div class="container-md">
    <br>
    <h3>Zmeniť profilovú fotku</h3>
    <div class="container section-inset">
        <div class="row">
            <div class="col" style="flex:0 0 96px">
                <img id="img-preview" src="images/user/<?php echo $_SESSION["userpicture"] ?>.jpg">
            </div>

            <div class="col">
                <form action="web/user/changePfp.php" method="post" enctype="multipart/form-data">
                    <input class="form-control" type="file" accept="image/jpeg, image/png" name="picture" id="picture"><br>
                    <input type="submit" onclick="dataChanged=false" class="btn btn-secondary float-end"  value="Uložiť" name="submit">
                </form>

                <i>Formáty: PNG, JPEG, JPG, Max 8MB</i>

                <script>
                    let dataChanged = false;

                    const filePicker = document.getElementById("picture");
                    const imgPreview = document.getElementById("img-preview");

                    filePicker.addEventListener("change", function () {
                        const files = filePicker.files[0];
                        if (files) {
                            const fileReader = new FileReader();
                            fileReader.readAsDataURL(files);
                            fileReader.addEventListener("load", function () {
                                imgPreview.src = this.result;
                                dataChanged = true;
                            });
                        }
                    });
                </script>
            </div>
        </div>
    </div>

    <br>

    <h3>Upraviť Popis</h3>
    <div class="container mb-3 section-inset">
        <div class="row">
            <form action="web/user/changeDesc.php" method="post">
                <textarea id="main-textarea" class="form-control" onkeyup="updateTextCounter(this, 1000, 'textCounter')" onclick="updateTextCounter(this, 1000, 'textCounter')" id="description" maxlength="1000" name="description"><?php echo UserDB::getUserDescription($_SESSION["user_id"]); ?></textarea>
                <h4 class="float-start" id="textCounter"></h4><br>
                <input type="submit" onclick="dataChanged=false" class="btn btn-secondary float-end" value="Uložiť" name="submit">
            </form>

            <script src="js/TextareaCounter.js"></script>
        </div>
    </div>

    <br>

    <script>
        function checkDataChanged(){
            if(dataChanged) return "Úpravy nie sú uložené. Naozaj chcete opustiť stránku?";
        }
    </script>

    <?php include "web/footer.php"; ?>
</div>
</body>
</html>