<?php
require_once __DIR__ . '/../includes/header.php';
require_login(['OWNER']);
$u = current_user();

// If already has a shop, redirect to dashboard
$stmt = db()->prepare('SELECT * FROM shops WHERE owner_user_id = ? LIMIT 1');
$stmt->execute([$u['id']]);
$existing = $stmt->fetch();
if ($existing) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($name==='' || $area==='' || $address==='') {
        flash_set('error', 'Name, area and address are required.');
        header('Location: create_shop.php');
        exit;
    }
    $pdo = db();
    $stmt = $pdo->prepare("INSERT INTO shops(owner_user_id,name,area,address,phone,status) VALUES (?,?,?,?,?,'PENDING')");
    $stmt->execute([$u['id'],$name,$area,$address,$phone]);
    $shop_id = (int)$pdo->lastInsertId();
    $pdo->prepare('UPDATE users SET shop_id = ? WHERE id = ?')->execute([$shop_id,$u['id']]);

    // Refresh session shop_id
    $user = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $user->execute([$u['id']]);
    login_user($user->fetch());

    header('Location: verification.php');
    exit;
}
?>

<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <h3 class="mb-3">Create Your Shop</h3>
        <p class="text-muted">After creation, your shop will be <strong>PENDING</strong> until admin verifies.</p>
        <form method="post">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Shop Name</label>
              <input name="name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Area</label>
              <input name="area" class="form-control" required placeholder="e.g., Mirpur">
            </div>
            <div class="col-md-12">
              <label class="form-label">Address</label>
              <input name="address" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input name="phone" class="form-control" placeholder="Optional">
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary" type="submit">Submit for Verification</button>
            <a class="btn btn-link" href="../index.php">Back</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
