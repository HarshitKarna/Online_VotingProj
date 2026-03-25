<?php
require_once("../includes/navbar.php");

/* Auto-expire polls: any poll whose end_time has passed but is still
   marked 'active' gets updated to 'ended' before the page renders. */
$conn->query("UPDATE polls SET status='ended' WHERE status='active' AND end_time <= NOW()");

/* Fetch all polls, showing active ones first, then sorted by newest */
$result = $conn->query("SELECT * FROM polls ORDER BY status='active' DESC, created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>All Polls &mdash; Online Voting</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php renderNav(); ?>

<div class="page-wrap">
    <div class="page-header">
        <div>
            <h1 class="page-title">All Polls</h1>
            <p class="page-subtitle">Click any poll to view it. Login required to vote.</p>
        </div>
    </div>

    <!-- Quick-jump search: submits the code as a GET param to view_poll.php -->
    <div class="poll-search-bar">
        <form method="GET" action="view_poll.php">
            <input type="text" name="code" placeholder="Enter 8-digit poll code..." maxlength="8">
            <button type="submit" class="btn btn-primary">Go</button>
        </form>
    </div>

    <?php if ($result->num_rows > 0): ?>
    <!-- Poll tile grid: each tile is clickable and navigates to the poll's view page -->
    <div class="poll-tile-grid">
        <?php while ($poll = $result->fetch_assoc()):
            $endTs   = strtotime($poll['end_time']);
            $ended   = ($poll['status'] === 'ended' || time() >= $endTs);
            $pollUrl = "/901_VotingProj/polls/view_poll.php?code=" . urlencode($poll['code']);
        ?>
        <div class="poll-tile <?php echo $ended ? 'tile-ended' : 'tile-active'; ?>"
             onclick="window.location='<?php echo $pollUrl; ?>'" role="button" tabindex="0">

            <div class="ptile-top">
                <h3 class="ptile-title"><?php echo htmlspecialchars($poll['title']); ?></h3>
                <!-- Timer element: JS fills this with a live countdown, or "Ended" -->
                <div class="ptile-timer <?php echo $ended ? 'timer-ended' : ''; ?>"
                     <?php if (!$ended): ?>data-end="<?php echo $endTs * 1000; ?>"<?php endif; ?>>
                    <?php echo $ended ? 'Ended' : ''; ?>
                </div>
            </div>

            <!-- Status badge: green "Live" or grey "Ended" -->
            <span class="ptile-badge <?php echo $ended ? 'badge-ended' : 'badge-active'; ?>">
                <?php echo $ended ? '&#9679; Ended' : '&#9679; Live'; ?>
            </span>

            <p class="ptile-question"><?php echo htmlspecialchars($poll['question']); ?></p>

            <!-- Poll code with copy and share buttons -->
            <div class="ptile-bottom">
                <div class="ptile-code-wrap">
                    <span class="ptile-code-label">Code:</span>
                    <span class="ptile-code"><?php echo htmlspecialchars($poll['code']); ?></span>
                    <!-- stopPropagation prevents the tile click from firing when these buttons are clicked -->
                    <button class="ptile-copy-btn"  onclick="event.stopPropagation();copyToClip('<?php echo $poll['code']; ?>',this)"  title="Copy code">&#10697;</button>
                    <button class="ptile-share-btn" onclick="event.stopPropagation();shareUrl('/901_VotingProj/polls/view_poll.php?code=<?php echo $poll['code']; ?>',this)" title="Share link">&#8599;</button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">&#128205;</div>
        <p>No polls yet. <a href="/901_VotingProj/polls/create_poll.php">Create the first one!</a></p>
    </div>
    <?php endif; ?>
</div>

<script src="../assets/script.js"></script>
<script>
/* Live countdown timers: find every timer element with a data-end timestamp
   and tick down every second until it hits zero, then show "Ended". */
document.querySelectorAll('.ptile-timer[data-end]').forEach(function(el) {
    var end = parseInt(el.dataset.end);
    function tick() {
        var diff = end - Date.now();
        if (diff <= 0) { el.textContent = 'Ended'; el.classList.add('timer-ended'); return; }
        var h = Math.floor(diff/3600000), m = Math.floor((diff%3600000)/60000), s = Math.floor((diff%60000)/1000);
        el.textContent = (h>0?h+'h ':'')+m+'m '+s+'s';
        setTimeout(tick, 1000);
    }
    tick();
});

/* Copies the poll code text to clipboard and briefly shows a checkmark */
function copyToClip(text, btn) {
    navigator.clipboard.writeText(text).then(function() {
        var o=btn.innerHTML; btn.innerHTML='&#10003;'; btn.classList.add('copied');
        setTimeout(function(){ btn.innerHTML=o; btn.classList.remove('copied'); },1500);
    });
}

/* Copies the full poll URL to clipboard for sharing */
function shareUrl(path, btn) {
    navigator.clipboard.writeText(window.location.origin+path).then(function() {
        var o=btn.innerHTML; btn.innerHTML='&#10003;'; btn.classList.add('copied');
        setTimeout(function(){ btn.innerHTML=o; btn.classList.remove('copied'); },1500);
    });
}
</script>
</body>
</html>
