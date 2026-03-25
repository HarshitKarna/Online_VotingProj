<?php
/* This file sets up the DB connection and defines renderNav(),
   which every page calls inside <body> to print the navigation bar.
   Keeping logic separate from output means pages can load their
   <head> and CSS before any HTML is rendered. */
require_once(__DIR__ . "/../config/db.php");

/* renderNav() builds the navbar HTML as a string and echoes it.
   It checks $_SESSION['email'] to decide whether to show
   login/register links (guest) or user pill + logout (logged in). */
function renderNav() {
    $email = $_SESSION['email'] ?? '';

    /* Outer nav bar with sticky positioning (handled in CSS) */
    $nav  = '<nav class="navbar" id="mainNav">';
    $nav .= '<div class="nav-brand"><a href="/901_VotingProj/index.php" class="nav-logo">';
    $nav .= '<span class="logo-icon">&#9670;</span>';
    $nav .= '<span class="logo-text">Online Voting</span></a></div>';

    /* Desktop nav links — differ based on login state */
    $nav .= '<div class="nav-links">';
    if ($email) {
        /* Logged-in: show Create Poll, Dashboard, and a user pill with logout */
        $nav .= '<a href="/901_VotingProj/polls/create_poll.php" class="nav-link">+ Create Poll</a>';
        $nav .= '<a href="/901_VotingProj/admin/dashboard.php" class="nav-link">&#9881; Dashboard</a>';
        $nav .= '<div class="nav-user-pill">';
        $nav .= '<span class="nav-user-email">' . htmlspecialchars($email) . '</span>';
        $nav .= '<a href="/901_VotingProj/auth/logout.php" class="nav-logout">Logout</a>';
        $nav .= '</div>';
    } else {
        /* Guest: show Login (with glow animation) and Register */
        $nav .= '<a href="/901_VotingProj/auth/login.php" class="nav-link nav-link-glow">Login</a>';
        $nav .= '<a href="/901_VotingProj/auth/register.php" class="nav-link nav-link-register">Register</a>';
    }
    $nav .= '</div>';

    /* Hamburger button — visible only on mobile (CSS hides it on desktop) */
    $nav .= '<button class="nav-hamburger" onclick="document.getElementById(\'mobileMenu\').classList.toggle(\'open\')" aria-label="Menu">&#9776;</button>';
    $nav .= '</nav>';

    /* Mobile dropdown menu — toggled by the hamburger button above */
    $nav .= '<div class="nav-mobile-menu" id="mobileMenu">';
    if ($email) {
        $nav .= '<a href="/901_VotingProj/polls/create_poll.php">+ Create Poll</a>';
        $nav .= '<a href="/901_VotingProj/admin/dashboard.php">Dashboard</a>';
        $nav .= '<span class="mob-email">' . htmlspecialchars($email) . '</span>';
        $nav .= '<a href="/901_VotingProj/auth/logout.php">Logout</a>';
    } else {
        $nav .= '<a href="/901_VotingProj/auth/login.php">Login</a>';
        $nav .= '<a href="/901_VotingProj/auth/register.php">Register</a>';
    }
    $nav .= '</div>';

    /* Inline JS: adds a shadow to the navbar when the user scrolls down */
    $nav .= '<script>window.addEventListener("scroll",function(){document.getElementById("mainNav").classList.toggle("navbar-scrolled",window.scrollY>20);});</script>';

    echo $nav;
}
?>
