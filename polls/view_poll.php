<?php
require_once("../includes/navbar.php");

/* Get the poll code from the URL query string */
$code = trim($_GET['code'] ?? '');
if (!$code) die("<div class='page-wrap'><p>No poll code provided.</p></div>");

/* Fetch the poll record */
$stmt = $conn->prepare("SELECT * FROM polls WHERE code=?");
$stmt->bind_param("s", $code); $stmt->execute();
$poll = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$poll) die("<div class='page-wrap'><p>Poll not found.</p></div>");

/* Determine if the poll has ended by comparing current time to end_time */
$endTs   = strtotime($poll['end_time']);
$isEnded = ($poll['status'] === 'ended' || time() >= $endTs);

/* Fetch all options for this poll, along with each option's vote count via subquery */
$optRes = $conn->query("SELECT o.*, (SELECT COUNT(*) FROM votes v WHERE v.option_id=o.id) as votes
    FROM options o WHERE o.poll_code='" . $conn->real_escape_string($code) . "'");
$optionList = []; $hasDesc = false;
while ($opt = $optRes->fetch_assoc()) {
    $optionList[] = $opt;
    /* Check if any option has a non-empty description to decide which voting UI to show */
    if (!empty(trim($opt['description'] ?? ''))) $hasDesc = true;
}

/* Check if the current user has already voted and which option they chose */
$userEmail = $_SESSION['email'] ?? ''; $voted = false; $userVoteId = null;
if ($userEmail) {
    $stmt = $conn->prepare("SELECT option_id FROM votes WHERE poll_code=? AND user_email=?");
    $stmt->bind_param("ss", $code, $userEmail); $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($res) { $voted = true; $userVoteId = $res['option_id']; }
}

/* Sort a copy of options by votes descending for the results display */
$optionsResults = $optionList;
usort($optionsResults, function($a, $b) { return $b['votes'] - $a['votes']; });

/* Count total valid votes (excluding disqualified options) for percentage calculation */
$tvRes = $conn->query("SELECT COUNT(*) as t FROM votes v JOIN options o ON v.option_id=o.id
    WHERE o.poll_code='" . $conn->real_escape_string($code) . "' AND o.is_disqualified=0");
$totalVotes = $tvRes->fetch_assoc()['t'] ?? 0;

/* Results are visible if: the poll has ended, OR the creator enabled show_results */
$canViewResults = $userEmail && ($poll['show_results'] || $isEnded);

/* Show the vote form only to logged-in users who haven't voted yet on an active poll */
$showVoteForm = !$isEnded && !$voted && $userEmail;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($poll['title']); ?> &mdash; Online Voting</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php renderNav(); ?>

<div class="page-wrap">

    <!-- Poll header: timer, title, question, code with copy/share -->
    <div class="view-poll-header">
        <div class="vp-timer-wrap">
            <?php if ($isEnded): ?>
                <!-- Static "Ended" label for closed polls -->
                <span class="vp-timer timer-ended">Ended</span>
            <?php else: ?>
                <!-- data-end holds the Unix timestamp in milliseconds for JS to countdown from -->
                <span class="vp-timer" id="vpCountdown" data-end="<?php echo $endTs * 1000; ?>">...</span>
            <?php endif; ?>
        </div>
        <h1 class="vp-title"><?php echo htmlspecialchars($poll['title']); ?></h1>
        <p class="vp-question"><?php echo htmlspecialchars($poll['question']); ?></p>

        <!-- Poll metadata: code + copy/share buttons + live/ended status badge -->
        <div class="vp-meta">
            <div class="ptile-code-wrap">
                <span class="ptile-code-label">Code:</span>
                <span class="ptile-code"><?php echo htmlspecialchars($code); ?></span>
                <button class="ptile-copy-btn"  onclick="copyToClip('<?php echo htmlspecialchars($code); ?>', this)"  title="Copy code">&#10697;</button>
                <button class="ptile-share-btn" onclick="shareUrl('<?php echo htmlspecialchars($code); ?>', this)"    title="Share link">&#8599;</button>
            </div>
            <span class="vp-status <?php echo $isEnded ? 'status-ended' : 'status-active'; ?>">
                <?php echo $isEnded ? '&#9679; Ended' : '&#9679; Live'; ?>
            </span>
        </div>
    </div>

    <?php if ($showVoteForm): ?>
    <!-- VOTING FORM: only shown to logged-in users who haven't voted yet on a live poll -->
    <form method="POST" action="vote.php" id="vote-form">
        <input type="hidden" name="poll_code" value="<?php echo htmlspecialchars($code); ?>">
        <p class="vote-instruction">
            <?php echo $hasDesc
                ? 'Click a tile to select. Use the &#8505; button to read its description.'
                : 'Select an option and cast your vote.'; ?>
        </p>

        <?php if ($hasDesc): ?>
        <!-- TILE VOTING UI: used when options have descriptions.
             Each tile has an info button that opens a description modal. -->
        <div class="vote-tile-grid">
            <?php foreach ($optionList as $opt): if ($opt['is_disqualified']) continue; ?>
            <label class="vote-tile">
                <input type="radio" name="option_id" value="<?php echo $opt['id']; ?>" hidden required>
                <span class="vote-tile-text"><?php echo htmlspecialchars($opt['option_text']); ?></span>
                <?php if (!empty(trim($opt['description'] ?? ''))): ?>
                <!-- Info button: opens a centered modal showing the option's description -->
                <button type="button" class="desc-peek-btn"
                    onclick="event.preventDefault();openModal(<?php echo htmlspecialchars(json_encode($opt['option_text'])); ?>,<?php echo htmlspecialchars(json_encode($opt['description'])); ?>)">&#8505;</button>
                <?php endif; ?>
            </label>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- SIMPLE LIST VOTING UI: used when options have no descriptions -->
        <div class="vote-options-list">
            <?php foreach ($optionList as $opt): ?>
            <label class="vote-option <?php echo $opt['is_disqualified'] ? 'disqualified' : ''; ?>">
                <input type="radio" name="option_id" value="<?php echo $opt['id']; ?>"
                    <?php echo $opt['is_disqualified'] ? 'disabled hidden' : 'hidden required'; ?>>
                <span><?php echo htmlspecialchars($opt['option_text']); ?></span>
                <?php if ($opt['is_disqualified']): ?><em class="disq-label">Disqualified</em><?php endif; ?>
            </label>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary vote-submit-btn">Cast Vote</button>
    </form>

    <?php elseif (!$userEmail): ?>
    <!-- Prompt guest users to log in before voting -->
    <div class="vp-notice"><p>You need to <a href="/901_VotingProj/auth/login.php">login</a> to vote.</p></div>

    <?php else: ?>
    <!-- Shown when user has already voted or poll has ended -->
    <div class="vp-notice">
        <?php if ($voted): ?><p class="voted-msg">&#10003; You have already voted.</p>
        <?php else: ?><p class="ended-msg">This poll has ended.</p><?php endif; ?>
    </div>

    <?php if ($canViewResults): ?>
    <!-- RESULTS SECTION: sorted by votes descending. Winner gets enlarged styling when ended. -->
    <div class="results-section">
        <h3 class="results-title">Results</h3>
        <?php $place = 0; foreach ($optionsResults as $opt):
            if ($opt['is_disqualified']): ?>
            <!-- Disqualified options shown greyed out with their reason -->
            <div class="result-row disqualified-row">
                <div class="result-label">
                    <span class="result-rank">&#9940;</span>
                    <span><?php echo htmlspecialchars($opt['option_text']); ?> &mdash; <em>DQ: <?php echo htmlspecialchars($opt['disqualify_reason']); ?></em></span>
                </div>
                <div class="progress"><div class="progress-bar bg-dark" style="width:100%">DQ</div></div>
            </div>
        <?php else:
            $pct      = $totalVotes > 0 ? round(($opt['votes'] / $totalVotes) * 100) : 0;
            $barClass = $place===0 ? 'bg-gold' : ($place===1 ? 'bg-blue' : 'bg-teal');
            $trophies = ['&#127942;','&#129352;','&#129353;']; /* gold, silver, bronze */
            $trophy   = $isEnded ? ($trophies[$place] ?? ($place+1).'.') : ($place+1).'.';
            $myVote   = ($userVoteId == $opt['id']) ? ' &#10003;' : '';
        ?>
            <!-- result-winner class enlarges trophy and name for 1st place on ended polls -->
            <div class="result-row <?php echo ($place===0 && $isEnded) ? 'result-winner' : ''; ?>" style="animation-delay:<?php echo $place*0.08; ?>s">
                <div class="result-label">
                    <span class="result-rank"><?php echo $trophy; ?></span>
                    <span><?php echo htmlspecialchars($opt['option_text']); ?><?php echo $myVote; ?></span>
                    <span class="result-votes"><?php echo $opt['votes']; ?> vote<?php echo $opt['votes']!=1?'s':''; ?></span>
                </div>
                <div class="progress">
                    <div class="progress-bar <?php echo $barClass; ?>" style="width:<?php echo $pct; ?>%"><?php echo $pct; ?>%</div>
                </div>
            </div>
        <?php $place++; endif; endforeach; ?>
    </div>

    <?php elseif ($voted): ?>
    <!-- Results hidden: show which option the user voted for, but no counts -->
    <div class="vp-notice">
        <p>Results will show when the poll ends or the creator allows it.</p>
        <h4 style="margin-top:14px;">Your vote:</h4>
        <?php foreach ($optionList as $opt): ?>
        <p class="<?php echo ($userVoteId==$opt['id']) ? 'my-vote-highlight' : ''; ?>">
            <?php echo htmlspecialchars($opt['option_text']); ?><?php echo ($userVoteId==$opt['id']) ? ' &#10003;' : ''; ?>
        </p>
        <?php endforeach; ?>
    </div>
    <?php endif; endif; ?>
</div>

<!-- Description modal: centered overlay, closed by clicking outside or pressing Escape -->
<div id="modal-overlay" class="modal-overlay" onclick="closeModal()">
    <div class="desc-modal" onclick="event.stopPropagation()">
        <button class="modal-close-btn" onclick="closeModal()">&#10005;</button>
        <h2 id="modal-title" class="modal-title"></h2>
        <p  id="modal-desc"  class="modal-desc"></p>
    </div>
</div>

<script src="../assets/script.js"></script>
<script>
/* Live countdown timer for active polls */
var vpt = document.getElementById('vpCountdown');
if (vpt) {
    var end = parseInt(vpt.dataset.end);
    function tickVP() {
        var d = end - Date.now();
        if (d <= 0) { vpt.textContent = 'Ended'; vpt.classList.add('timer-ended'); return; }
        var h = Math.floor(d/3600000), m = Math.floor((d%3600000)/60000), s = Math.floor((d%60000)/1000);
        vpt.textContent = (h>0?h+'h ':'')+m+'m '+s+'s';
        setTimeout(tickVP, 1000);
    }
    tickVP();
}

/* Tile voting: clicking a tile selects its hidden radio input and highlights the tile */
document.querySelectorAll('.vote-tile').forEach(function(t) {
    t.addEventListener('click', function(e) {
        if (e.target.classList.contains('desc-peek-btn')) return; /* don't select on info button click */
        document.querySelectorAll('.vote-tile').forEach(function(x){ x.classList.remove('selected'); });
        this.classList.add('selected');
        this.querySelector('input[type=radio]').checked = true;
    });
});

/* List voting: clicking a row selects its hidden radio input */
document.querySelectorAll('.vote-option:not(.disqualified)').forEach(function(o) {
    o.addEventListener('click', function() {
        document.querySelectorAll('.vote-option').forEach(function(x){ x.classList.remove('selected'); });
        this.classList.add('selected');
        this.querySelector('input[type=radio]').checked = true;
    });
});

/* Opens the description modal with the option's title and description text */
function openModal(title, desc) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-desc').textContent  = desc || 'No description.';
    document.getElementById('modal-overlay').classList.add('open');
    document.body.classList.add('modal-open');
}

/* Closes the modal by removing the 'open' class */
function closeModal() {
    document.getElementById('modal-overlay').classList.remove('open');
    document.body.classList.remove('modal-open');
}

/* Allow closing the modal with the Escape key */
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });

/* Copies the poll code to clipboard */
function copyToClip(text, btn) {
    navigator.clipboard.writeText(text).then(function() {
        var o=btn.innerHTML; btn.innerHTML='&#10003;'; btn.classList.add('copied');
        setTimeout(function(){ btn.innerHTML=o; btn.classList.remove('copied'); }, 1500);
    });
}

/* Builds the full poll URL and copies it to clipboard for sharing */
function shareUrl(code, btn) {
    var url = window.location.origin + '/901_VotingProj/polls/view_poll.php?code=' + code;
    navigator.clipboard.writeText(url).then(function() {
        var o=btn.innerHTML; btn.innerHTML='&#10003;'; btn.classList.add('copied');
        setTimeout(function(){ btn.innerHTML=o; btn.classList.remove('copied'); }, 1500);
    });
}
</script>
</body>
</html>
