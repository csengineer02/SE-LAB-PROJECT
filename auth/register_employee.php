<?php
require_once __DIR__ . '/../includes/header.php';

$shops = db()->query("SELECT id, name, area FROM shops WHERE status='VERIFIED' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $shop_id = (int)($_POST['shop_id'] ?? 0);
    $emp_role = trim($_POST['emp_role'] ?? 'STAFF');

    if ($name==='' || $email==='' || $pass==='') {
        flash_set('error', 'All fields are required.');
        header('Location: register_employee.php');
        exit;
    }
    if ($shop_id <= 0) {
        flash_set('error', 'Please select a verified shop.');
        header('Location: register_employee.php');
        exit;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        flash_set('error', 'Email already registered.');
        header('Location: register_employee.php');
        exit;
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users(name,email,password_hash,role,shop_id,employee_status,employee_role) VALUES (?,?,?,?,?,'PENDING',?)");
    $stmt->execute([$name,$email,$hash,'EMPLOYEE',$shop_id,$emp_role]);

    flash_set('success', 'Employee account created. Wait for owner approval, then login.');
    header('Location: login.php');
    exit;
}
?>

<div class="row justify-content-center">
  <div class="col-md-7">
    <div class="card">
      <div class="card-body">
        <h3 class="mb-3">Employee Signup</h3>
        <?php if (!$shops): ?>
          <div class="alert alert-warning">No verified shops found yet. Ask an owner to create a shop and admin to verify it.</div>
        <?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input name="password" type="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Select Shop (Verified)</label>
            <select name="shop_id" class="form-select" required>
              <option value="">-- Select --</option>
              <?php foreach ($shops as $s): ?>
                <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['area']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Employee Role</label>
            <select name="emp_role" class="form-select">
              <option value="STAFF">Staff</option>
              <option value="CASHIER">Cashier (billing/POS)</option>
              <option value="MANAGER">Manager (low-stock alerts)</option>
            </select>
          </div>
          <button class="btn btn-primary" type="submit" <?= $shops ? '' : 'disabled' ?>>Create Employee Account</button>
          <a class="btn btn-link" href="../index.php">Back</a>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
