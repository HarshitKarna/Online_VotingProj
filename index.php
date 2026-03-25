<?php
/* Load navbar.php first (pure PHP, no output) so the session and DB
   are ready before we start building the page. */
require_once("includes/navbar.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Voting</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="landing-page">

<?php renderNav(); /* Print the navigation bar inside <body> */ ?>

<!-- Landing hero section: full-viewport centered layout with animated background orbs -->
<div class="landing-hero">

    <!-- Three blurred gradient orbs that drift slowly in the background (CSS animation) -->
    <div class="hero-bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <!-- Main headline -->
    <div class="hero-header">
        <h1 class="hero-title">Online <span class="hero-accent">Voting</span></h1>
        <p class="hero-subtitle">Create polls. Cast votes. See results.</p>
    </div>

    <!-- Three action tiles: Browse, Account (login/logout), Create -->
    <div class="hero-tiles">

        <!-- Tile 1: always links to the poll listing page -->
        <a href="/901_VotingProj/polls/poll_list.php" class="hero-tile tile-browse">
            <div class="tile-float-bg"></div>
            <div class="tile-icon">&#128499;</div>
            <h2 class="tile-title">Browse Polls</h2>
            <p class="tile-desc">Explore all active and past polls. View options, results, and participate.</p>
            <span class="tile-cta">Explore &rarr;</span>
        </a>

        <!-- Tile 2: shows logout info if logged in, otherwise links to login page -->
        <?php if (isset($_SESSION['email'])): ?>
        <div class="hero-tile tile-account tile-loggedin">
            <div class="tile-float-bg"></div>
            <div class="tile-icon">&#10003;</div>
            <h2 class="tile-title">Signed In</h2>
            <p class="tile-desc">You're logged in as <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong></p>
            <a href="/901_VotingProj/auth/logout.php" class="tile-cta-btn">Logout</a>
        </div>
        <?php else: ?>
        <a href="/901_VotingProj/auth/login.php" class="hero-tile tile-account">
            <div class="tile-float-bg"></div>
            <div class="tile-icon">&#128273;</div>
            <h2 class="tile-title">Login / Register</h2>
            <p class="tile-desc">Sign in to vote on polls and create your own. New here? Register in seconds.</p>
            <span class="tile-cta">Get Started &rarr;</span>
        </a>
        <?php endif; ?>

        <!-- Tile 3: goes to create poll if logged in, otherwise redirects to login first -->
        <a href="<?php echo isset($_SESSION['email']) ? '/901_VotingProj/polls/create_poll.php' : '/901_VotingProj/auth/login.php'; ?>" class="hero-tile tile-create">
            <div class="tile-float-bg"></div>
            <div class="tile-icon">&#9889;</div>
            <h2 class="tile-title">Create a Poll</h2>
            <p class="tile-desc">Build your own poll with custom options, time limits, and result visibility controls.</p>
            <span class="tile-cta">Start Building &rarr;</span>
        </a>

    </div>
</div>

<script src="assets/script.js"></script>
</body>
</html>
