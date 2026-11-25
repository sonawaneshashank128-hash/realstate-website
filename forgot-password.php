<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/mailer.php';

// Runtime DDL — create table if it doesn't exist yet
db()->exec('CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pr_token (token),
    INDEX idx_pr_email (email)
) ENGINE=InnoDB');

if (current_user()) {
    redirect('dashboard.php');
}

$sent   = false;
$errors = [];

if (is_post()) {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email.';
    }
    if (!$errors) {
        // Always set $sent = true to prevent user enumeration
        $sent = true;

        $stmt = db()->prepare('SELECT id, name FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Remove any existing token for this email
            $del = db()->prepare('DELETE FROM password_resets WHERE email = ?');
            $del->execute([$email]);

            $token = bin2hex(random_bytes(32));
            $ins   = db()->prepare(
                'INSERT INTO password_resets (email, token, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
            );
            $ins->execute([$email, $token]);

            send_mail(
                $email,
                $user['name'],
                'Reset your EstateHub password',
                '<p>Click to reset your password: '
                . '<a href="' . url('reset-password.php') . '?token=' . rawurlencode($token) . '">Reset password</a>.'
                . ' This link expires in 1 hour.</p>'
            );
        }
    }
}

$pageTitle = 'Forgot Password';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
  <div class="auth-card">
    <span class="eyebrow">Account recovery</span>
    <h1>Forgot your password?</h1>
    <p class="muted">Enter your account email and we will send you a reset link.</p>

    <?php if ($sent): ?>
      <div class="alert alert-success">
        If that email is registered, a reset link has been sent. Check your inbox.
      </div>
    <?php else: ?>
      <?php if ($errors): ?>
        <div class="alert alert-error"><?= e($errors[0]) ?></div>
      <?php endif; ?>
      <form method="post" class="form-stack">
        <?= csrf_field() ?>
        <label>Email address
          <input type="email" name="email" required autocomplete="email"
                 value="<?= e((string)($_POST['email'] ?? '')) ?>">
        </label>
        <button class="btn btn-full" type="submit">Send reset link</button>
      </form>
    <?php endif; ?>

    <p class="auth-switch"><a href="<?= e(url('login.php')) ?>">Back to log in</a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
