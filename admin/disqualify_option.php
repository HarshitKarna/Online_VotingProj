<?php
require_once("../includes/navbar.php");
if (!isset($_SESSION['email'])) { header("Location: /901_VotingProj/auth/login.php"); exit(); }

$option_id = intval($_GET['id'] ?? 0);
$poll_code = trim($_GET['poll'] ?? '');
if (!$option_id || !$poll_code) die("Invalid request.");

/* Fetch the option and its parent poll's creator email in one query via JOIN */
$stmt = $conn->prepare("SELECT o.*, p.creator_email FROM options o
    JOIN polls p ON o.poll_code=p.code WHERE o.id=? AND o.poll_code=?");
$stmt->bind_param("is", $option_id, $poll_code); $stmt->execute();
$option = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$option) die("Option not found.");

/* Only the poll's creator or a master admin can disqualify options */
$isMaster = isMasterAdmin($conn);
if (!$isMaster && $option['creator_email'] !== $_SESSION['email']) die("Access denied.");

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');
    if (!$reason) {
        $error = "Please provide a reason.";
    } else {
        /* Mark the option as disqualified and store the reason */
        $stmt = $conn->prepare("UPDATE options SET is_disqualified=1, disqualify_reason=? WHERE id=?");
        $stmt->bind_param("si", $reason, $option_id); $stmt->execute(); $stmt->close();

        /* Delete all votes cast for this option so they don't affect results */
        $stmt = $conn->prepare("DELETE FROM votes WHERE option_id=?");
        $stmt->bind_param("i", $option_id); $stmt->execute(); $stmt->close();

        header("Location: manage_poll.php?code=" . urlencode($poll_code)); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Disqualify Option &mdash; Online Voting</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php renderNav(); ?>

<div class="page-wrap">
    <div class="disq-page-card">
        <h2 class="disq-page-title">&#9940; Disqualify Option</h2>
        <p class="disq-option-name">Option: <strong><?php echo htmlspecialchars($option['option_text']); ?></strong></p>

        <!-- Warning: makes it clear this action removes votes and cannot be undone -->
        <p class="disq-warning">&#9888; This removes all votes for this option and cannot be undone.</p>

        <?php if ($error): ?><div class="auth-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST" class="disq-form">
            <div class="form-group">
                <label class="form-label">Reason for disqualification</label>
                <textarea name="reason" class="form-input" rows="3" placeholder="Explain why..." required></textarea>
            </div>
            <div class="disq-form-btns">
                <a href="manage_poll.php?code=<?php echo urlencode($poll_code); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-danger">Confirm Disqualify</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
