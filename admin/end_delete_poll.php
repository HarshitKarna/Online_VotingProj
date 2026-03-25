<?php
require_once("../config/db.php");
if (!isset($_SESSION['email'])) die("Login required.");
if (!isset($_POST['poll_code'])) die("Invalid request.");

$poll_code = trim($_POST['poll_code']);

/* Fetch the poll to verify it exists and check ownership */
$stmt = $conn->prepare("SELECT * FROM polls WHERE code=?");
$stmt->bind_param("s", $poll_code); $stmt->execute();
$poll = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$poll) die("Poll not found.");

/* Only the creator or a master admin can end/delete this poll */
$isMaster = isMasterAdmin($conn);
if (!$isMaster && $poll['creator_email'] !== $_SESSION['email']) die("Access denied.");

$isEnded = ($poll['status'] === 'ended' || time() >= strtotime($poll['end_time']));

if (!$isEnded) {
    /* Poll is still live — mark it as ended and stay on the manage page */
    $stmt = $conn->prepare("UPDATE polls SET status='ended' WHERE code=?");
    $stmt->bind_param("s", $poll_code); $stmt->execute(); $stmt->close();
    header("Location: manage_poll.php?code=" . urlencode($poll_code));
} else {
    /* Poll already ended — delete it entirely. The ON DELETE CASCADE foreign keys
       in the schema automatically delete all related options and votes too. */
    $stmt = $conn->prepare("DELETE FROM polls WHERE code=?");
    $stmt->bind_param("s", $poll_code); $stmt->execute(); $stmt->close();
    header("Location: dashboard.php");
}
exit();
?>
