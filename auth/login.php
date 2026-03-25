<?php
/* Include navbar.php for session start, DB connection, and renderNav().
   Must come before DOCTYPE so session is ready before any output. */
require_once("../includes/navbar.php");

$error = "";

/* Handle form submission: look up the email, then verify the password
   against the hash stored in the DB using password_verify(). */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    /* Prepared statement prevents SQL injection when querying by email (was a common error that i ran into) */
    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed);
        $stmt->fetch();
        /* password_verify() safely compares plaintext against the stored hash */
        if (password_verify($password, $hashed)) {
            $_SESSION["email"] = $email; /* Store email in session to mark user as logged in */
            header("Location: /901_VotingProj/index.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login &mdash; Online Voting</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="auth-page">
<?php renderNav(); ?>

<div class="auth-wrap">
    <div class="auth-card">
        <h2 class="auth-title">Welcome back</h2>
        <p class="auth-subtitle">Sign in to your account</p>

        <!-- Server-side error (wrong password, email not found) shown after form submit -->
        <?php if ($error): ?>
            <div class="auth-error" id="server-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- JS error box: shown by JavaScript before the form submits if fields are invalid -->
        <div class="auth-error" id="js-error" style="display:none;"></div>

        <!-- novalidate disables browser's built-in validation so our JS handles it instead -->
        <form method="POST" class="auth-form" id="login-form" novalidate>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="you@example.com" autocomplete="email">
                <!-- Field-level error message shown below the input by JS -->
                <span class="field-error" id="email-error"></span>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <!-- input-wrap allows the Show/Hide button to overlay the input -->
                <div class="input-wrap">
                    <input type="password" name="password" id="password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" autocomplete="current-password">
                    <button type="button" class="toggle-pw" onclick="togglePw('password', this)" tabindex="-1">Show</button>
                </div>
                <span class="field-error" id="password-error"></span>
            </div>
            <button type="submit" class="btn btn-primary btn-full" id="login-btn">Sign In</button>
        </form>

        <div class="auth-footer">
            <a href="register.php">Don't have an account? Register</a>
            <a href="/901_VotingProj/index.php">&larr; Back to Home</a>
        </div>
    </div>
</div>

<script>
var form          = document.getElementById('login-form');
var emailInput    = document.getElementById('email');
var passwordInput = document.getElementById('password');
var jsError       = document.getElementById('js-error');

/* Real-time validation: check the field as the user types */
emailInput.addEventListener('input', function() { validateEmail(false); });
passwordInput.addEventListener('input', function() { validatePassword(false); });

/* Clear field error when user focuses back on the input */
emailInput.addEventListener('focus', function() { setFieldError('email-error', ''); this.classList.remove('input-invalid'); });
passwordInput.addEventListener('focus', function() { setFieldError('password-error', ''); this.classList.remove('input-invalid'); });

/* On submit: run both validators. Only allow form to submit if both pass. */
form.addEventListener('submit', function(e) {
    e.preventDefault();
    jsError.style.display = 'none';
    var emailOk = validateEmail(true);
    var passOk  = validatePassword(true);
    if (emailOk && passOk) {
        /* Disable button to prevent double-submit */
        document.getElementById('login-btn').textContent = 'Signing in...';
        document.getElementById('login-btn').disabled = true;
        form.submit();
    }
});

/* Validates email format using a basic regex */
function validateEmail(showError) {
    var val = emailInput.value.trim();
    if (val === '') {
        if (showError) setFieldError('email-error', 'Email is required.', emailInput);
        return false;
    }
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(val)) {
        if (showError) setFieldError('email-error', 'Please enter a valid email address.', emailInput);
        return false;
    }
    setFieldError('email-error', '');
    emailInput.classList.remove('input-invalid');
    return true;
}

/* Validates that password is not empty and meets minimum length */
function validatePassword(showError) {
    var val = passwordInput.value;
    if (val === '') {
        if (showError) setFieldError('password-error', 'Password is required.', passwordInput);
        return false;
    }
    if (val.length < 6) {
        if (showError) setFieldError('password-error', 'Password must be at least 6 characters.', passwordInput);
        return false;
    }
    setFieldError('password-error', '');
    passwordInput.classList.remove('input-invalid');
    return true;
}

/* Shows or clears an error message below a field, and toggles the red border */
function setFieldError(id, msg, input) {
    var el = document.getElementById(id);
    el.textContent = msg;
    if (input) input.classList.toggle('input-invalid', msg !== '');
}

/* Toggles password field between hidden (dots) and visible (plain text) */
function togglePw(id, btn) {
    var input = document.getElementById(id);
    if (input.type === 'password') { input.type = 'text';     btn.textContent = 'Hide'; }
    else                           { input.type = 'password'; btn.textContent = 'Show'; }
}
</script>
</body>
</html>
