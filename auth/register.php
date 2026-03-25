<?php
require_once("../includes/navbar.php");
$error = ""; $success = "";

/* Handle registration form: validate inputs server-side, then insert
   a new user with a bcrypt-hashed password into the users table. */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm  = trim($_POST["confirm"]);

    /* Server-side validation (JS validates first, but this is the safety net) */
    if (empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        /* password_hash() uses bcrypt by default — never store plain text passwords */
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $hashed);
        if ($stmt->execute()) {
            $success = "Account created! You can now log in.";
        } else {
            /* INSERT fails if email already exists (UNIQUE constraint on the column) */
            $error = "That email is already registered.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register &mdash; Online Voting</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="auth-page">
<?php renderNav(); ?>

<div class="auth-wrap">
    <div class="auth-card">
        <h2 class="auth-title">Create account</h2>
        <p class="auth-subtitle">Join to start voting and creating polls</p>

        <!-- PHP error/success messages from server-side validation -->
        <?php if ($error):   ?><div class="auth-error"><?php echo htmlspecialchars($error);   ?></div><?php endif; ?>
        <?php if ($success): ?><div class="auth-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <!-- JS error container shown before submit if client validation fails -->
        <div class="auth-error" id="js-error" style="display:none;"></div>

        <form method="POST" class="auth-form" id="reg-form" novalidate>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="you@example.com" autocomplete="email">
                <span class="field-error" id="email-error"></span>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <input type="password" name="password" id="password" placeholder="Min. 6 characters" autocomplete="new-password">
                    <button type="button" class="toggle-pw" onclick="togglePw('password', this)" tabindex="-1">Show</button>
                </div>
                <span class="field-error" id="password-error"></span>

            </div>
            <div class="form-group">
                <label for="confirm">Confirm Password</label>
                <div class="input-wrap">
                    <input type="password" name="confirm" id="confirm" placeholder="Repeat password" autocomplete="new-password">
                    <button type="button" class="toggle-pw" onclick="togglePw('confirm', this)" tabindex="-1">Show</button>
                </div>
                <span class="field-error" id="confirm-error"></span>
            </div>
            <button type="submit" class="btn btn-primary btn-full" id="reg-btn">Create Account</button>
        </form>

        <div class="auth-footer">
            <a href="login.php">Already have an account? Login</a>
        </div>
    </div>
</div>

<script>
var form          = document.getElementById('reg-form');
var emailInput    = document.getElementById('email');
var passwordInput = document.getElementById('password');
var confirmInput  = document.getElementById('confirm');

/* Attach real-time listeners so errors appear as the user types, not just on submit */
emailInput.addEventListener('input',    function() { validateEmail(false); });
passwordInput.addEventListener('input', function() { validatePassword(false); if(confirmInput.value) validateConfirm(false); });
confirmInput.addEventListener('input',  function() { validateConfirm(false); });

/* Clear errors when the user clicks into a field */
emailInput.addEventListener('focus',    function() { setFieldError('email-error','');    this.classList.remove('input-invalid'); });
passwordInput.addEventListener('focus', function() { setFieldError('password-error',''); this.classList.remove('input-invalid'); });
confirmInput.addEventListener('focus',  function() { setFieldError('confirm-error','');  this.classList.remove('input-invalid'); });

/* Run all three validators on submit; only proceed if all pass */
form.addEventListener('submit', function(e) {
    e.preventDefault();
    var emailOk   = validateEmail(true);
    var passOk    = validatePassword(true);
    var confirmOk = validateConfirm(true);
    if (emailOk && passOk && confirmOk) {
        document.getElementById('reg-btn').textContent = 'Creating account...';
        document.getElementById('reg-btn').disabled = true;
        form.submit();
    }
});

/* Checks email is non-empty and matches a basic email pattern */
function validateEmail(show) {
    var val = emailInput.value.trim();
    if (!val) { if(show) setFieldError('email-error','Email is required.',emailInput); return false; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) { if(show) setFieldError('email-error','Enter a valid email address.',emailInput); return false; }
    setFieldError('email-error',''); emailInput.classList.remove('input-invalid'); return true;
}

/* Checks password is non-empty and at least 6 characters */
function validatePassword(show) {
    var val = passwordInput.value;
    if (!val) { if(show) setFieldError('password-error','Password is required.',passwordInput); return false; }
    if (val.length < 6) { if(show) setFieldError('password-error','Must be at least 6 characters.',passwordInput); return false; }
    setFieldError('password-error',''); passwordInput.classList.remove('input-invalid'); return true;
}

/* Checks confirm field is non-empty and matches the password field exactly */
function validateConfirm(show) {
    var val = confirmInput.value;
    if (!val) { if(show) setFieldError('confirm-error','Please confirm your password.',confirmInput); return false; }
    if (val !== passwordInput.value) { if(show) setFieldError('confirm-error','Passwords do not match.',confirmInput); return false; }
    setFieldError('confirm-error',''); confirmInput.classList.remove('input-invalid'); return true;
}

/* Shows or clears a field-level error message and toggles the red border */
function setFieldError(id, msg, input) {
    document.getElementById(id).textContent = msg;
    if (input) input.classList.toggle('input-invalid', msg !== '');
}

/* Toggles password visibility between dots and plain text */
function togglePw(id, btn) {
    var input = document.getElementById(id);
    if (input.type === 'password') { input.type = 'text';     btn.textContent = 'Hide'; }
    else                           { input.type = 'password'; btn.textContent = 'Show'; }
}

</script>
</body>
</html>