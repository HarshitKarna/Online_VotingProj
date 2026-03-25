<?php
require_once("../config/db.php");

/* Validate that exactly 8 characters were provided */
$code = trim($_GET['code'] ?? '');
if (strlen($code) !== 8) die("Invalid poll code.");

/* Look up the poll and redirect to view it, or show not found */
$stmt = $conn->prepare("SELECT code FROM polls WHERE code = ?");
$stmt->bind_param("s", $code); $stmt->execute();
$res = $stmt->get_result(); $stmt->close();

if ($res->num_rows > 0) {
    header("Location: view_poll.php?code=" . urlencode($code)); exit();
} else {
    echo "<div style='text-align:center;margin-top:50px;'><h3>Poll Not Found</h3><a href='/901_VotingProj/index.php'>Back to Home</a></div>";
}
?>
