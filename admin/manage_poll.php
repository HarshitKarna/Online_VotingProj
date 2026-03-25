<?php
require_once("../includes/navbar.php");
if (!isset($_SESSION['email'])) { header("Location: /901_VotingProj/auth/login.php"); exit(); }

/* Fetch the poll by code from query string */
$code = trim($_GET['code'] ?? '');
if (!$code) die("No poll code.");
$stmt = $conn->prepare("SELECT * FROM polls WHERE code=?");
$stmt->bind_param("s", $code); $stmt->execute();
$poll = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$poll) die("Poll not found.");

/* Only the poll creator or a master admin can manage this poll */
$isMaster = isMasterAdmin($conn);
if (!$isMaster && $poll['creator_email'] !== $_SESSION['email']) die("Access denied.");

$isEnded = ($poll['status'] === 'ended' || time() >= strtotime($poll['end_time']));

/* Fetch options with their vote counts using a LEFT JOIN so options with 0 votes appear too */
$optRes = $conn->query("SELECT o.*, COUNT(v.id) as votes FROM options o
    LEFT JOIN votes v ON o.id=v.option_id
    WHERE o.poll_code='" . $conn->real_escape_string($code) . "'
    GROUP BY o.id ORDER BY votes DESC");
$options = []; while ($o = $optRes->fetch_assoc()) $options[] = $o;

/* Total votes excludes disqualified options so percentages are calculated correctly */
$totalVotes = array_sum(array_column(array_filter($options, fn($o) => !$o['is_disqualified']), 'votes'));

/* Voter list for the audit table at the bottom */
$votersRes = $conn->query("SELECT user_email, voted_at FROM votes
    WHERE poll_code='" . $conn->real_escape_string($code) . "' ORDER BY voted_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage: <?php echo htmlspecialchars($poll['title']); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php renderNav(); ?>

<div class="page-wrap">

    <!-- Poll summary card: title, question, code, action buttons, stats -->
    <div class="manage-header-card">
        <div class="manage-title-row">
            <div>
                <h1 class="manage-title"><?php echo htmlspecialchars($poll['title']); ?></h1>
                <p class="manage-question"><?php echo htmlspecialchars($poll['question']); ?></p>
                <span class="manage-code">Code: <strong><?php echo $poll['code']; ?></strong></span>
            </div>
            <div class="manage-actions">
                <!-- End Poll button becomes Delete Poll once the poll has ended -->
                <form method="POST" action="end_delete_poll.php" onsubmit="return confirm('Are you sure?')">
                    <input type="hidden" name="poll_code" value="<?php echo $poll['code']; ?>">
                    <button class="btn <?php echo $isEnded ? 'btn-danger' : 'btn-warning'; ?>">
                        <?php echo $isEnded ? '&#128465; Delete' : '&#9209; End Poll'; ?>
                    </button>
                </form>
                <a href="/901_VotingProj/polls/view_poll.php?code=<?php echo $poll['code']; ?>" class="btn btn-secondary">View</a>
                <a href="/901_VotingProj/admin/dashboard.php" class="btn btn-secondary">&#8592; Back</a>
            </div>
        </div>

        <!-- Quick stats: total votes, option count, live timer or "Ended" -->
        <div class="manage-stats">
            <div class="stat-pill">Votes: <strong><?php echo $totalVotes; ?></strong></div>
            <div class="stat-pill">Options: <strong><?php echo count($options); ?></strong></div>
            <div class="stat-pill">
                <?php if ($isEnded): ?>
                    Ended
                <?php else: ?>
                    <!-- JS countdown timer — same approach as poll_list and view_poll -->
                    <span id="mgCountdown" data-end="<?php echo strtotime($poll['end_time'])*1000; ?>">...</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Results grid: each option shown as a tile with a progress bar and disqualify button -->
    <h3 class="section-heading">Results</h3>
    <div class="manage-options-grid">
        <?php foreach ($options as $i => $opt):
            $pct      = $totalVotes > 0 && !$opt['is_disqualified'] ? round(($opt['votes']/$totalVotes)*100) : 0;
            $barClass = $opt['is_disqualified'] ? 'bg-dark' : ($i===0 ? 'bg-gold' : ($i===1 ? 'bg-blue' : 'bg-teal'));
            $trophies = ['&#127942;','&#129352;','&#129353;'];
            $trophy   = $opt['is_disqualified'] ? '&#9940;' : ($trophies[$i] ?? ($i+1).'.');
        ?>
        <!-- winner-tile enlarges the trophy and name for 1st place when poll has ended -->
        <div class="manage-option-tile <?php echo $opt['is_disqualified'] ? 'disq-tile' : ($i===0 && $isEnded ? 'winner-tile' : ''); ?>">
            <div class="mot-header">
                <span class="mot-rank"><?php echo $trophy; ?></span>
                <span class="mot-name"><?php echo htmlspecialchars($opt['option_text']); ?></span>
                <span class="mot-votes"><?php echo $opt['is_disqualified'] ? 'DQ' : $opt['votes'].' votes'; ?></span>
            </div>
            <div class="progress">
                <div class="progress-bar <?php echo $barClass; ?>" style="width:<?php echo $opt['is_disqualified'] ? '100' : $pct; ?>%">
                    <?php echo $opt['is_disqualified'] ? 'Disqualified' : $pct.'%'; ?>
                </div>
            </div>
            <?php if ($opt['is_disqualified']): ?>
                <p class="disq-reason"><em>Reason: <?php echo htmlspecialchars($opt['disqualify_reason']); ?></em></p>
            <?php else: ?>
                <!-- Link to the dedicated disqualify page, passing option ID and poll code -->
                <a href="disqualify_option.php?id=<?php echo $opt['id']; ?>&poll=<?php echo urlencode($code); ?>" class="btn btn-danger btn-sm disq-btn">Disqualify</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Voter audit list: shows who voted and when (emails, no option shown for anonymity) -->
    <h3 class="section-heading">Voters (<?php echo $votersRes->num_rows; ?>)</h3>
    <div class="voters-list">
        <?php if ($votersRes->num_rows === 0): ?>
            <p class="no-voters">No votes yet.</p>
        <?php else: while ($v = $votersRes->fetch_assoc()): ?>
        <div class="voter-row">
            <span class="voter-email">&#128100; <?php echo htmlspecialchars($v['user_email']); ?></span>
            <span class="voter-time"><?php echo date('M j, Y g:i A', strtotime($v['voted_at'])); ?></span>
        </div>
        <?php endwhile; endif; ?>
    </div>
</div>

<script src="../assets/script.js"></script>
<script>
/* Live countdown for the stats pill — same tick logic used across the site */
var mg = document.getElementById('mgCountdown');
if (mg) {
    var end = parseInt(mg.dataset.end);
    function tickMg() {
        var d = end - Date.now();
        if (d <= 0) { mg.textContent = 'Ended'; return; }
        var h = Math.floor(d/3600000), m = Math.floor((d%3600000)/60000), s = Math.floor((d%60000)/1000);
        mg.textContent = (h>0?h+'h ':'')+m+'m '+s+'s';
        setTimeout(tickMg, 1000);
    }
    tickMg();
}
</script>
</body>
</html>
