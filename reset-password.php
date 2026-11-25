<?php
require_once __DIR__ . '/config/config.php';

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

// Sanitise token from query string
$token = preg_replace('/[^a-f0-9]/i', '', (string)($_GET['token'] ?? ''));
$token = mb_substr($token, 0, 64);

// Look up a valid, non-expired token
$stmt = db()->prepare(
    'SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1'
);
$stmt->execute([$token]);
$reset = $stmt->fetch();

$errors = [];

if ($reset && is_post()) {
    verify_csrf();
    $pass    = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');

    if (strlen($pass) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($pass !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $upd = db()->prepare(
            'UPDATE users SET password_hash = ? WHERE email = ?'
        );
        $upd->execute([password_hash($pass, PASSWORD_DEFAULT), $reset['email']]);

        $del = db()->prepare('DELETE FROM password_resets WHERE token = ?');
        $del->execute([$token]);

        flash('success', 'Password updated. Please log in.');
        redirect('login.php');
    }
}

$pageTitle = 'Reset Password';
require __DIR__ . '/includes/header.php';
?>

<?php if (!$reset): ?>
<section class="auth-wrap">
  <div class="auth-card">
    <span class="eyebrow">Invalid link</span>
    <h1>Link expired or invalid</h1>
    <p class="muted">This reset link has expired or is invalid. Please request a new one.</p>
    <p class="auth-switch"><a href="<?= e(url('forgot-password.php')) ?>">Request a new reset link</a></p>
  </div>
</section>

<?php else: ?>
<section class="auth-wrap">
  <div class="auth-card">
    <span class="eyebrow">Account recovery</span>
    <h1>Set a new password</h1>
    <p class="muted">Choose a strong password for your EstateHub account.</p>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $err): ?>
          <p><?= e($err) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" class="form-stack">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <label>New password
        <input type="password" name="password" required minlength="8" autocomplete="new-password">
      </label>
      <label>Confirm new password
        <input type="password" name="confirm" required minlength="8" autocomplete="new-password">
      </label>
      <button class="btn btn-full" type="submit">Update password</button>
    </form>

    <p class="auth-switch"><a href="<?= e(url('login.php')) ?>">Back to log in</a></p>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
