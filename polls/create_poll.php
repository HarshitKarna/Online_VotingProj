<?php
require_once("../includes/navbar.php");

/* Redirect guests to login — only registered users can create polls */
if (!isset($_SESSION['email'])) { header("Location: /901_VotingProj/auth/login.php"); exit(); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title        = trim($_POST['title']);
    $question     = trim($_POST['question']);
    $minutes      = intval($_POST['duration']);
    $show_results = isset($_POST['show_results']) ? 1 : 0;

    /* Generate a unique 8-digit poll code. Loop until we find one
       that doesn't already exist in the polls table. */
    do {
        $code  = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        $chk   = $conn->prepare("SELECT id FROM polls WHERE code=?");
        $chk->bind_param("s", $code); $chk->execute(); $chk->store_result();
        $exists = $chk->num_rows > 0;
        $chk->close();
    } while ($exists);

    /* Calculate end time by adding the duration in minutes to the current time */
    $end_time = date("Y-m-d H:i:s", strtotime("+$minutes minutes"));

    $stmt = $conn->prepare("INSERT INTO polls (code,title,question,creator_email,show_results,end_time) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssssis", $code, $title, $question, $_SESSION['email'], $show_results, $end_time);
    $stmt->execute(); $stmt->close();

    /* Insert each option. Empty option fields are skipped. */
    $options = $_POST['options'] ?? [];
    $descs   = $_POST['descriptions'] ?? [];
    foreach ($options as $i => $opt) {
        $opt = trim($opt); if ($opt === '') continue;
        $desc = trim($descs[$i] ?? '');
        $stmt = $conn->prepare("INSERT INTO options (poll_code, option_text, description) VALUES (?,?,?)");
        $stmt->bind_param("sss", $code, $opt, $desc); $stmt->execute(); $stmt->close();
    }

    header("Location: /901_VotingProj/admin/dashboard.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Poll &mdash; Online Voting</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php renderNav(); ?>

<div class="page-wrap">
    <div class="page-header"><h1 class="page-title">Create a Poll</h1></div>
    <div class="create-card">

        <!-- Step 1: ask if options need descriptions before showing the full form -->
        <div id="step-desc-ask" class="desc-ask-screen">
            <div class="desc-ask-icon">&#128221;</div>
            <h2>Add descriptions to options?</h2>
            <p>Give each option a short write-up (up to 250 words) to help voters choose.</p>
            <div class="desc-ask-btns">
                <button class="btn btn-primary"   onclick="startForm(true)">Yes, add descriptions</button>
                <button class="btn btn-secondary" onclick="startForm(false)">No, keep it simple</button>
            </div>
        </div>

        <!-- Step 2: the actual poll creation form, shown after the choice above -->
        <form method="POST" id="create-form" style="display:none;">
            <!-- Tracks whether descriptions were requested; read by PHP on submit -->
            <input type="hidden" name="has_descriptions" id="has_desc_input" value="0">

            <div class="form-section">
                <label class="form-label">Poll Title</label>
                <input type="text" name="title" class="form-input" placeholder="e.g. Best Programming Language" required>
            </div>
            <div class="form-section">
                <label class="form-label">Question</label>
                <textarea name="question" class="form-input form-textarea" placeholder="What do you want to ask?" required></textarea>
            </div>

            <!-- Dynamic options list: JS adds/removes rows, min 2, max 10 -->
            <div class="form-section">
                <div class="options-header">
                    <label class="form-label">Options <span id="opt-count-label">(4)</span></label>
                    <div class="options-controls">
                        <button type="button" class="opt-ctrl-btn" onclick="removeOption()">&#8722;</button>
                        <button type="button" class="opt-ctrl-btn" onclick="addOption()">&#65291;</button>
                    </div>
                </div>
                <div id="options-list"></div>
            </div>

            <div class="form-section form-row">
                <div class="form-group-half">
                    <label class="form-label">Duration (minutes)</label>
                    <input type="number" name="duration" class="form-input" placeholder="e.g. 60" min="1" required>
                </div>
                <!-- Toggle switch for show_results: if checked, voters can see results while poll is live -->
                <div class="form-group-half" style="display:flex;align-items:center;padding-top:22px;">
                    <label class="show-results-label">
                        <div class="toggle-wrap">
                            <input type="checkbox" name="show_results" id="show_results" class="toggle-input">
                            <span class="toggle-slider"></span>
                        </div>
                        Show results to voters
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Create Poll</button>
        </form>
    </div>
</div>

<script>
var withDesc = false, optCount = 4, MAX = 10, MIN = 2;

/* Called when user picks Yes/No on the description prompt.
   Hides the prompt, reveals the form, and builds the initial 4 option rows. */
function startForm(useDesc) {
    withDesc = useDesc;
    document.getElementById('has_desc_input').value = useDesc ? '1' : '0';
    document.getElementById('step-desc-ask').style.display  = 'none';
    document.getElementById('create-form').style.display = 'block';
    var list = document.getElementById('options-list'); list.innerHTML = '';
    for (var i = 0; i < 4; i++) addOptionEl(i);
    updateCount();
}

/* Add a new option row (up to MAX) */
function addOption() { if (optCount >= MAX) return; addOptionEl(optCount); optCount++; updateCount(); }

/* Remove the last option row (down to MIN) */
function removeOption() {
    if (optCount <= MIN) return;
    document.getElementById('options-list').removeChild(document.getElementById('options-list').lastElementChild);
    optCount--; updateCount();
}

/* Re-number option labels and re-index input names after any add/remove */
function updateCount() {
    document.getElementById('opt-count-label').textContent = '(' + optCount + ')';
    document.getElementById('options-list').querySelectorAll('.option-block').forEach(function(el, i) {
        el.querySelector('.option-num').textContent = (i+1) + '.';
        el.querySelector('input[type=text]').name   = 'options[' + i + ']';
        var ta = el.querySelector('textarea');
        if (ta) ta.name = 'descriptions[' + i + ']';
    });
}

/* Build one option row's HTML, with an optional description textarea */
function addOptionEl(index) {
    var list  = document.getElementById('options-list');
    var block = document.createElement('div'); block.className = 'option-block';
    block.innerHTML =
        '<div class="option-row"><span class="option-num">' + (index+1) + '.</span>'
        + '<input type="text" name="options[' + index + ']" class="form-input" placeholder="Option ' + (index+1) + '"></div>'
        + (withDesc
            ? '<div class="desc-row"><textarea name="descriptions[' + index + ']" class="form-input desc-textarea"'
              + ' placeholder="Description for option ' + (index+1) + ' (max 250 words)" oninput="countWords(this)"></textarea>'
              + '<div class="word-counter"><span class="wc-count">0</span> / 250 words</div></div>'
            : '');
    list.appendChild(block);
}

/* Limits the description textarea to 250 words and updates the counter */
function countWords(ta) {
    var words = ta.value.trim().split(/\s+/).filter(function(w){ return w.length > 0; });
    if (words.length > 250) { ta.value = words.slice(0,250).join(' '); words = words.slice(0,250); }
    ta.parentElement.querySelector('.wc-count').textContent = words.length;
    ta.parentElement.querySelector('.word-counter').classList.toggle('over-limit', words.length >= 250);
}
</script>
</body>
</html>
