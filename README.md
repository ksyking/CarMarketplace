1) Requirements

XAMPP (PHP 8+) on Windows

Keep Apache/MySQL on default ports (80/3306)

2) Install project

Unzip autotrade into:
C:\xampp\htdocs\autotrade

Start XAMPP Control Panel → Start Apache and MySQL.

3) Create database

Open http://localhost/phpmyadmin.

Create database named autotrade (utf8mb4_general_ci is fine).

Click the new autotrade DB → Import → choose autotrade.sql → Go.

If you’d rather use a different DB name, update app/db.php:

$dsn = 'mysql:host=localhost;dbname=autotrade;charset=utf8mb4';

4) Visit the site

Open: http://localhost/autotrade/
