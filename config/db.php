<?php
/* Start session only if one isn't already active.
   This prevents "session already started" errors when db.php
   is included multiple times across different pages. */
if(session_status() === PHP_SESSION_NONE) session_start();

/* Database connection credentials for XAMPP's default MySQL setup. */
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "901_votingproj";

/* Open a MySQLi connection. If it fails, stop execution immediately. */
$conn = new mysqli($host, $user, $pass, $dbname);
if($conn->connect_error) die("DB connection failed: " . $conn->connect_error);

/* Sync both PHP and MySQL to the same timezone (Kathmandu, UTC+5:45).
   Without this, PHP's date() and MySQL's NOW() would use different clocks,
   causing poll end times to be calculated incorrectly. */
date_default_timezone_set('Asia/Kathmandu');
$conn->query("SET time_zone = '+05:45'");

/* Helper function: checks if the currently logged-in user is a master admin.
   Queries the DB, so any user can be promoted to
   master admin by setting is_master_admin=1 in the users table. */
function isMasterAdmin($conn) {
    if(!isset($_SESSION['email'])) return false;
    $stmt = $conn->prepare("SELECT is_master_admin FROM users WHERE email=?");
    $stmt->bind_param("s", $_SESSION['email']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row && (int)$row['is_master_admin'] === 1;
}
?>
