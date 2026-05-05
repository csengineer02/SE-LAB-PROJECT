<?php
require_once __DIR__ . '/../includes/header.php';
require_login(['employee']);

$pdo = db();
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $old = (string)($_POST['old_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');

    try {
        // Update display name (users)
        if ($name !== '') {
            $pdo->prepare('UPDATE users SET name=? WHERE id=? AND role=\'employee\'')->execute([$name, (int)$u['id']]);
            $_SESSION['user']['name'] = $name;
        }

        // Password change (optional)
        if ($new !== '' || $old !== '') {
            if ($old === '' || $new === '') {
                throw new Exception('To change password, both old and new passwords are required.');
            }
            if (strlen($new) < 4) {
                throw new Exception('New password is too short.');
            }

            $st = $pdo->prepare('SELECT password_hash FROM users WHERE id=? AND role=\'employee\' LIMIT 1');
            $st->execute([(int)$u['id']]);
            $hash = (string)($st->fetchColumn() ?: '');
            if ($hash === '' || !password_verify($old, $hash)) {
                throw new Exception('Old password is incorrect.');
            }
            $newHash = password_hash($new, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE users SET password_hash=? WHERE id=? AND role=\'employee\'')->execute([$newHash, (int)$u['id']]);

            // Keep legacy employee table in sync if it exists
            try {
                $pdo->prepare('UPDATE employee SET password=? WHERE id=?')->execute([$newHash, (int)$u['id']]);
            } catch (Throwable $e) {
                // ignore
            }
        }

        flash_set('success', 'Settings updated.');
        redirect('/employee/settings.php');
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        redirect('/employee/settings.php');
    }
}

// Prefill
$st = $pdo->prepare('SELECT name, phone FROM users WHERE id=? AND role=\'employee\' LIMIT 1');
$st->execute([(int)$u['id']]);
$me = $st->fetch() ?: ['name'=>$u['name'] ?? '', 'phone'=>$u['phone'] ?? ''];

$title = 'Settings';
?>

<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
  <div>
    <h3 class="mb-1">Settings</h3>
    <div class="text-muted">Update your profile and password.</div>
  </div>
  <a class="btn btn-outline-success" href="dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card app-card">
      <div class="card-body">
        <h6 class="mb-3">Profile</h6>
        <form method="post" class="row g-3 form-modern">
          <div class="col-12">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" value="<?= h((string)($me['name'] ?? '')) ?>" />
          </div>
          <div class="col-12">
            <label class="form-label">Phone</label>
            <input class="form-control" value="<?= h((string)($me['phone'] ?? '')) ?>" readonly />
          </div>
          <div class="col-12 d-grid">
            <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card app-card">
      <div class="card-body">
        <h6 class="mb-3">Change Password</h6>
        <form method="post" class="row g-3 form-modern">
          <div class="col-12">
            <label class="form-label">Old Password</label>
            <input class="form-control" type="password" name="old_password" />
          </div>
          <div class="col-12">
            <label class="form-label">New Password</label>
            <input class="form-control" type="password" name="new_password" />
            <div class="text-muted small mt-1">Minimum 4 characters.</div>
          </div>
          <div class="col-12 d-grid">
            <button class="btn btn-soft" type="submit"><i class="bi bi-shield-lock me-1"></i>Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
