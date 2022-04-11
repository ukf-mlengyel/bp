<?php
// header
session_start();

$logged_in = isset($_SESSION["user_id"]);

$middle = $logged_in ? "
                <li class='nav-item'>
                    <a class='nav-link' href='index.php'>Skládky</a>
                </li>
                <li class='nav-item'>
                    <a class='nav-link' href='bins.php'>Koše</a>
                </li>
                <li class='nav-item'>
                    <a class='nav-link' href='userlist.php'>Používatelia</a>
                </li>
" : "";

echo "
<nav class='navbar navbar-dark navbar-expand-md sticky-top justify-content-center'>
    <div class='container'>
        <a href='index.php' class='navbar-brand navbar-dark d-flex w-50 me-auto'>bin me!</a>
        <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#collapsingNavbar3'>
            <span class='navbar-toggler-icon'></span>
        </button>
        <div class='navbar-collapse collapse w-100' id='collapsingNavbar3'>

            <ul class='navbar-nav w-100 justify-content-center'>
                $middle
            </ul>
            <ul class='nav navbar-nav ms-auto w-100 justify-content-end'>
            ";

if($logged_in) echo "
                <li class='nav-item d-none d-sm-none d-md-block'>
                    <a class='nav-link' href='profile.php'>
                        <img class='profilepic navbar-pic' src='images/user_thumb/{$_SESSION["userpicture"]}.jpg'>
                    </a>
                </li>

                <li class='nav-item dropdown'>
                    <a class='nav-link dropdown-toggle' id='navbarScrollingDropdown' role='button' data-bs-toggle='dropdown' aria-expanded='false'> {$_SESSION["username"]} </a>
                    <ul class='dropdown-menu dropdown-menu-right' aria-labelledby='navbarScrollingDropdown'>
                        <li><a class='dropdown-item' href='profile.php'>Zobraziť profil</a></li>
                        <li><a class='dropdown-item' href='editprofile.php'>Upraviť profil</a></li>
                        
                        <li><hr class='dropdown-divider'></li>
                        
                        <li><a class='dropdown-item' href='https://mirzi.cc/binme-debug.apk'>Stiahnuť aplikáciu (Android)</a></li>
                        
                        <li><hr class='dropdown-divider'></li>
                        
                        <li><a class='dropdown-item' href='logout.php'>Odhlásiť sa</a></li>
                    </ul>
                </li>
                "; else echo "
                <li class='nav-item'>
                    <a class='nav-link' href='register.php'>Zaregistrovať sa</a>
                </li>
                <li class='nav-item'>
                    <a class='nav-link' href='login.php'>Prihlásiť sa</a>
                </li>
                
                ";

                echo "
            </ul>
        </div>
    </div>
</nav>
";
?>
