<?php
require_once __DIR__ . '/config/config.php';
require_login();

$user = current_user();

if (is_post()) {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $name  = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors[] = 'Name must be between 2 and 100 characters.';
        }
        if (mb_strlen($phone) > 30) {
            $errors[] = 'Phone number must not exceed 30 characters.';
        }

        if (empty($errors)) {
            $stmt = db()->prepare('UPDATE users SET name=?, phone=? WHERE id=?');
            $stmt->execute([$name, $phone !== '' ? $phone : null, $user['id']]);
            // Refresh session
            $_SESSION['user']['name']  = $name;
            $_SESSION['user']['phone'] = $phone !== '' ? $phone : null;
            flash('success', 'Profile updated successfully.');
            redirect('profile.php');
        } else {
            flash('error', implode(' ', $errors));
            redirect('profile.php');
        }
    }

    if ($action === 'change_password') {
        $current  = (string)($_POST['current_password'] ?? '');
        $new      = (string)($_POST['new_password'] ?? '');
        $confirm  = (string)($_POST['confirm_password'] ?? '');

        $errors = [];
        if ($current === '') {
            $errors[] = 'Current password is required.';
        }
        if (mb_strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        }

        if (empty($errors)) {
            $stmt = db()->prepare('SELECT password_hash FROM users WHERE id=? LIMIT 1');
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch();

            if ($row && password_verify($current, $row['password_hash'])) {
                $newHash = password_hash($new, PASSWORD_BCRYPT);
                $upd = db()->prepare('UPDATE users SET password_hash=? WHERE id=?');
                $upd->execute([$newHash, $user['id']]);
                flash('success', 'Password changed successfully.');
                redirect('profile.php');
            } else {
                flash('error', 'Current password is incorrect.');
                redirect('profile.php');
            }
        } else {
            flash('error', implode(' ', $errors));
            redirect('profile.php');
        }
    }
}

// Fetch fresh user row for display (name/phone may have just changed)
$stmt = db()->prepare('SELECT id, name, email, phone, role, created_at FROM users WHERE id=? LIMIT 1');
$stmt->execute([$user['id']]);
$dbUser = $stmt->fetch();

// Property count (only relevant for owners)
$propertyCount = 0;
if ($dbUser['role'] === 'owner') {
    $cntStmt = db()->prepare('SELECT COUNT(*) FROM properties WHERE owner_id=?');
    $cntStmt->execute([$dbUser['id']]);
    $propertyCount = (int)$cntStmt->fetchColumn();
}

$pageTitle = 'My profile';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Account settings</span>
    <h1>My profile</h1>
    <p>Manage your personal details and account security.</p>
  </div>
</section>

<section class="form-page">
  <div class="container" style="max-width:860px;">

    <!-- Account stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(<?= $dbUser['role'] === 'owner' ? '3' : '2' ?>,1fr);margin-bottom:36px;">
      <div class="stat-card">
        <span>Member since</span>
        <strong style="font-size:1.15rem;"><?= e(date('M Y', strtotime($dbUser['created_at']))) ?></strong>
      </div>
      <div class="stat-card">
        <span>Account role</span>
        <strong style="font-size:1.15rem;"><?= e(ucfirst($dbUser['role'])) ?></strong>
      </div>
      <?php if ($dbUser['role'] === 'owner'): ?>
      <div class="stat-card">
        <span>Your listings</span>
        <strong><?= $propertyCount ?></strong>
      </div>
      <?php endif; ?>
    </div>

    <!-- Edit Profile -->
    <div class="form-card" style="margin-bottom:28px;">
      <span class="eyebrow">Personal details</span>
      <h1>Edit profile</h1>
      <form method="post" class="form-stack">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_profile">
        <label>
          Full name <span style="color:var(--danger)">*</span>
          <input type="text" name="name" value="<?= e($dbUser['name']) ?>" required minlength="2" maxlength="100" autocomplete="name">
        </label>
        <label>
          Email address
          <input type="email" value="<?= e($dbUser['email']) ?>" disabled style="background:#f5f7f2;color:var(--muted);">
          <small style="color:var(--muted);">Email cannot be changed.</small>
        </label>
        <label>
          Phone number <span style="color:var(--muted);font-weight:400;">(optional)</span>
          <input type="tel" name="phone" value="<?= e($dbUser['phone'] ?? '') ?>" maxlength="30" autocomplete="tel">
        </label>
        <button class="btn" type="submit">Save changes</button>
      </form>
    </div>

    <!-- Change Password -->
    <div class="form-card">
      <span class="eyebrow">Security</span>
      <h1>Change password</h1>
      <form method="post" class="form-stack">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_password">
        <label>
          Current password
          <input type="password" name="current_password" required autocomplete="current-password">
        </label>
        <label>
          New password <span style="color:var(--muted);font-weight:400;">(min. 8 characters)</span>
          <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
        </label>
        <label>
          Confirm new password
          <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
        </label>
        <button class="btn" type="submit">Update password</button>
      </form>
    </div>

  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
