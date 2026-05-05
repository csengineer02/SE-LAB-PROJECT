<?php
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($email === '' || $pass === '') {
        flash_set('error', 'Email and password are required.');
        header('Location: login.php');
        exit;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password_hash'])) {
        flash_set('error', 'Invalid credentials.');
        header('Location: login.php');
        exit;
    }

    // For employees: block if not approved
    if ($user['role'] === 'EMPLOYEE' && ($user['employee_status'] ?? 'PENDING') !== 'APPROVED') {
        flash_set('error', 'Employee account pending approval by the shop owner.');
        header('Location: login.php');
        exit;
    }

    login_user($user);

    // Redirect by role
    switch ($user['role']) {
        case 'ADMIN': header('Location: ../admin/dashboard.php'); break;
        case 'OWNER': header('Location: ../owner/dashboard.php'); break;
        case 'EMPLOYEE': header('Location: ../employee/dashboard.php'); break;
        case 'CUSTOMER': header('Location: ../customer/dashboard.php'); break;
        default: header('Location: ../index.php'); break;
    }
    exit;
}
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <h3 class="mb-3">Login</h3>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input name="password" type="password" class="form-control" required>
          </div>
          <button class="btn btn-primary" type="submit">Login</button>
          <a class="btn btn-link" href="../index.php">Back</a>
        </form>
        <hr>
        <p class="mb-0"><small>Admin demo: admin@grocer360.local / admin123</small></p>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
