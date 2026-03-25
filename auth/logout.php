<?php
require_once("../config/db.php");

/* Clear all session data and destroy the session cookie,
   effectively logging the user out, then redirect to home. */
$_SESSION = [];
session_destroy();
header("Location: /901_VotingProj/index.php");
exit();
?>
