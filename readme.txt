Server možno spustiť lokálne cez XAMPP (najmenej verzia 8), 
akurát treba nahradiť predvolenú MariaDB databázu za MySQL.
https://stackoverflow.com/questions/39654428/how-can-i-change-mariadb-to-mysql-in-xampp

Prihlasovacie údaje do databázy nastavíme v
server/class/database/DB.php (riadky 3 - 6):
const SERVER = "localhost";
const USERNAME = "root";
const PASSWORD = "";
const DBNAME = "bin_me";

Ak chceme testovať aplikáciu napr. cez emulátor, treba zmeniť URL servera na lokálnu IP počítača.
aplikácia/app/src/main/java/com/mirzi/binme/Helper/BM.java (riadok 8):
public static final String SERVER_URL = "http://192.168.1.3/bin-me-server";
