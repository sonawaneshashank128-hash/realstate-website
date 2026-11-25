<?php
require_once __DIR__ . '/config/config.php';
if (current_user()) {
    if (current_user()['role'] === 'admin') redirect('admin/index.php');
    redirect('index.php');
}
$error = ''; $email = '';
if (is_post()) {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? ''))); $password = (string)($_POST['password'] ?? '');
    $stmt = db()->prepare('SELECT id,name,email,phone,password_hash,role FROM users WHERE email=? LIMIT 1'); $stmt->execute([$email]); $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        unset($user['password_hash']); $_SESSION['user'] = $user;
        flash('success','You are now signed in.');
        // Admin goes straight to admin panel, everyone else to homepage
        if ($user['role'] === 'admin') {
            redirect('admin/index.php');
        }
        redirect('index.php');
    }
    $error = 'Email or password is incorrect.';
}
$pageTitle = 'Log in'; require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap"><div class="auth-card"><span class="eyebrow">Good to see you</span><h1>Welcome back</h1><p class="muted">Sign in to continue your property journey.</p><?php if($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?><form method="post" class="form-stack"><?= csrf_field() ?><label>Email address<input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="btn btn-full" type="submit">Log in</button></form><p class="auth-switch">New to EstateHub? <a href="<?= e(url('register.php')) ?>">Create an account</a></p><p class="auth-switch"><a href="<?= e(url('forgot-password.php')) ?>">Forgot your password?</a></p></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
