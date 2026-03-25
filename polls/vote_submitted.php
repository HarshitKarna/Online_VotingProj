<?php
require_once("../includes/navbar.php");
$code = trim($_GET['code'] ?? '');
if (!$code) die("<div class='page-wrap'><p>Invalid poll.</p></div>");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vote Submitted &mdash; Online Voting</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php renderNav(); ?>

<!-- Confirmation screen shown after a successful vote -->
<div class="page-wrap">
    <div class="submitted-card">
        <div class="submitted-icon">&#128499;</div>
        <h2 class="submitted-title">Vote Submitted!</h2>
        <p class="submitted-msg">Thank you for participating. Your vote has been recorded.</p>
        <!-- Link back to the poll so user can see results if they're visible -->
        <a href="view_poll.php?code=<?php echo urlencode($code); ?>" class="btn btn-primary">View Poll</a>
        <a href="/901_VotingProj/polls/poll_list.php" class="btn btn-secondary">Browse Polls</a>
    </div>
</div>
</body>
</html>
