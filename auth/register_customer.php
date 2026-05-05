<?php
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $area = trim($_POST['area'] ?? '');

    if ($name==='' || $email==='' || $pass==='' || $address==='' || $area==='') {
        flash_set('error', 'Please fill all required fields.');
        header('Location: register_customer.php');
        exit;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        flash_set('error', 'Email already registered.');
        header('Location: register_customer.php');
        exit;
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users(name,email,password_hash,role,phone,address,area,reward_points) VALUES (?,?,?,?,?,?,?,0)");
    $stmt->execute([$name,$email,$hash,'CUSTOMER',$phone,$address,$area]);
    $user_id = (int)$pdo->lastInsertId();

    login_user(['id'=>$user_id,'name'=>$name,'email'=>$email,'role'=>'CUSTOMER']);
    header('Location: ../customer/dashboard.php');
    exit;
}
?>

<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <h3 class="mb-3">Customer Signup</h3>
        <form method="post">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Full Name</label>
              <input name="name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input name="email" type="email" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Password</label>
              <input name="password" type="password" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input name="phone" class="form-control" placeholder="Optional">
            </div>
            <div class="col-md-12">
              <label class="form-label">Address</label>
              <input name="address" class="form-control" required>
            </div>
            <div class="col-md-12">
              <label class="form-label">Area Keyword</label>
              <input name="area" class="form-control" required placeholder="e.g., Dhanmondi, Mirpur">
              <div class="form-text">Used to show nearby verified shops matching your area.</div>
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary" type="submit">Create Customer Account</button>
            <a class="btn btn-link" href="../index.php">Back</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
