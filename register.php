<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/mailer.php';
if (current_user()) redirect('dashboard.php');
$errors = [];
$name = $email = $phone = ''; $role = 'seeker';
if (is_post()) {
    verify_csrf();
    $name = trim((string)($_POST['name'] ?? '')); $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $phone = trim((string)($_POST['phone'] ?? '')); $role = (string)($_POST['role'] ?? 'seeker');
    $password = (string)($_POST['password'] ?? '');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Name must be between 2 and 100 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!in_array($role, ['seeker','owner'], true)) $errors[] = 'Choose a valid account type.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO users (name,email,phone,password_hash,role) VALUES (?,?,?,?,?)');
            $stmt->execute([$name,$email,$phone,password_hash($password, PASSWORD_DEFAULT),$role]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id'=>(int)db()->lastInsertId(),'name'=>$name,'email'=>$email,'role'=>$role,'phone'=>$phone];
            flash('success','Welcome to EstateHub. Your account is ready.');
            send_mail($email, $name, 'Welcome to EstateHub, '.$name.'!', '<p>Hi '.e($name).',</p><p>Your EstateHub account is ready. <a href="'.url('login.php').'">Sign in</a></p>');
            redirect('dashboard.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') $errors[] = 'That email address is already registered.';
            else { error_log($e->getMessage()); $errors[] = 'Registration could not be completed. Please try again.'; }
        }
    }
}
$pageTitle = 'Create account'; require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap"><div class="auth-card"><span class="eyebrow">Welcome to EstateHub</span><h1>Create your account</h1><p class="muted">Save homes, contact owners and manage listings.</p>
<?php foreach($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
<form method="post" class="form-stack"><?= csrf_field() ?><label>Full name<input name="name" value="<?= e($name) ?>" required maxlength="100" autocomplete="name"></label><label>Email address<input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></label><label>Phone (optional)<input name="phone" value="<?= e($phone) ?>" maxlength="30" autocomplete="tel"></label><label>Account type<select name="role"><option value="seeker" <?= $role==='seeker'?'selected':'' ?>>Property seeker</option><option value="owner" <?= $role==='owner'?'selected':'' ?>>Property owner / agent</option></select></label><label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label><button class="btn btn-full" type="submit">Create account</button></form><p class="auth-switch">Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a></p></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
