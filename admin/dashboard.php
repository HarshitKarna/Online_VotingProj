<?php
require_once("../includes/navbar.php");

/* Redirect guests away from the dashboard */
if (!isset($_SESSION['email'])) { header("Location: /901_VotingProj/auth/login.php"); exit(); }

$isMaster = isMasterAdmin($conn);

/* Master admin sees all polls; regular users only see their own */
if ($isMaster) {
    $result = $conn->query("SELECT * FROM polls ORDER BY created_at DESC");
} else {
    $stmt = $conn->prepare("SELECT * FROM polls WHERE creator_email=? ORDER BY created_at DESC");
    $stmt->bind_param("s", $_SESSION['email']); $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard &mdash; Online Voting</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php renderNav(); ?>

<div class="page-wrap">
    <div class="page-header">
        <div>
            <!-- Title changes based on whether user is a master admin or regular user -->
            <h1 class="page-title"><?php echo $isMaster ? 'All Polls (Admin)' : 'My Polls'; ?></h1>
        </div>
        <a href="/901_VotingProj/polls/create_poll.php" class="btn btn-primary">+ New Poll</a>
    </div>

    <?php if ($result->num_rows === 0): ?>
    <div class="empty-state">
        <div class="empty-icon">&#128202;</div>
        <p>No polls yet. <a href="/901_VotingProj/polls/create_poll.php">Create your first!</a></p>
    </div>
    <?php else: ?>

    <!-- Grid of poll management tiles -->
    <div class="dash-poll-grid">
        <?php while ($poll = $result->fetch_assoc()):
            $ended = ($poll['status'] === 'ended' || time() >= strtotime($poll['end_time'])); ?>
        <div class="dash-tile <?php echo $ended ? 'dash-tile-ended' : 'dash-tile-active'; ?>">
            <div class="dash-tile-top">
                <h3 class="dash-tile-title"><?php echo htmlspecialchars($poll['title']); ?></h3>
                <span class="dash-badge <?php echo $ended ? 'badge-ended' : 'badge-active'; ?>"><?php echo $ended ? 'Ended' : 'Live'; ?></span>
            </div>
            <p class="dash-tile-q"><?php echo htmlspecialchars($poll['question']); ?></p>
            <div class="dash-tile-meta">
                Code: <strong><?php echo $poll['code']; ?></strong>
                <!-- Master admin also sees which user created the poll -->
                <?php if ($isMaster): ?> &mdash; <span class="dash-creator"><?php echo htmlspecialchars($poll['creator_email']); ?></span><?php endif; ?>
            </div>
            <div class="dash-tile-actions">
                <a href="manage_poll.php?code=<?php echo $poll['code']; ?>" class="btn btn-primary btn-sm">Manage</a>
                <a href="/901_VotingProj/polls/view_poll.php?code=<?php echo $poll['code']; ?>" class="btn btn-secondary btn-sm">View</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<script src="../assets/script.js"></script>
</body>
</html>
