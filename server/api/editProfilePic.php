<?php
include_once "../class/actions/Account.php";

session_start();

try{
    echo Account::changeProfilePicture( $_FILES["picture"] );
}catch (Throwable $e){
    echo Account::removeProfilePicture();
}