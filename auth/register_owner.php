<?php
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($name==='' || $email==='' || $pass==='') {
        flash_set('error', 'All fields are required.');
        header('Location: register_owner.php');
        exit;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        flash_set('error', 'Email already registered.');
        header('Location: register_owner.php');
        exit;
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users(name,email,password_hash,role) VALUES (?,?,?,\'OWNER\')');
    $stmt->execute([$name,$email,$hash]);
    $user_id = (int)$pdo->lastInsertId();

    // Auto login
    $user = ['id'=>$user_id,'name'=>$name,'email'=>$email,'role'=>'OWNER'];
    login_user($user);
    header('Location: ../owner/create_shop.php');
    exit;
}
?>

<div class="row justify-content-center">
  <div class="col-md-7">
    <div class="card">
      <div class="card-body">
        <h3 class="mb-3">Owner Signup</h3>
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
          <button class="btn btn-primary" type="submit">Create Owner Account</button>
          <a class="btn btn-link" href="../index.php">Back</a>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
