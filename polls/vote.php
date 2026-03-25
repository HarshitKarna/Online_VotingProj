<?php
require_once("../config/db.php");

/* Reject unauthenticated requests */
if (!isset($_SESSION['email'])) die("Login required.");
if (!isset($_POST['poll_code'], $_POST['option_id'])) die("Invalid request.");

$poll_code  = trim($_POST['poll_code']);
$option_id  = intval($_POST['option_id']);
$user_email = $_SESSION['email'];

/* Verify the poll exists and is still active before accepting the vote */
$stmt = $conn->prepare("SELECT * FROM polls WHERE code = ?");
$stmt->bind_param("s", $poll_code); $stmt->execute();
$poll = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$poll || $poll['status'] === 'ended' || time() >= strtotime($poll['end_time'])) die("Poll is not active.");

/* Verify the chosen option belongs to this poll and is not disqualified */
$stmt = $conn->prepare("SELECT * FROM options WHERE id = ? AND poll_code = ?");
$stmt->bind_param("is", $option_id, $poll_code); $stmt->execute();
$option = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$option || $option['is_disqualified']) die("Invalid option.");

/* Insert the vote. The UNIQUE constraint on (poll_code, user_email) in the DB
   ensures one vote per user per poll — the INSERT fails if they already voted. */
$stmt = $conn->prepare("INSERT INTO votes (poll_code, user_email, option_id) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $poll_code, $user_email, $option_id);
if (!$stmt->execute()) die("You have already voted or an error occurred.");
$stmt->close();

header("Location: vote_submitted.php?code=" . urlencode($poll_code));
exit();
?>
