<?php
require_once __DIR__ . '/../includes/header.php';
require_login(['OWNER']);
$u = current_user();
if (!$u['shop_id']) { header('Location: create_shop.php'); exit; }

$shop = db()->prepare('SELECT * FROM shops WHERE id=? AND owner_user_id=?');
$shop->execute([$u['shop_id'],$u['id']]);
$shop = $shop->fetch();
if (!$shop || $shop['status']!=='VERIFIED') { header('Location: verification.php'); exit; }

$pdo = db();

if (isset($_GET['approve'])) {
    $emp_id = (int)$_GET['approve'];
    $stmt = $pdo->prepare("UPDATE users SET employee_status='APPROVED' WHERE id=? AND role='EMPLOYEE' AND shop_id=?");
    $stmt->execute([$emp_id,$shop['id']]);
    flash_set('success','Employee approved.');
    header('Location: employees.php');
    exit;
}
if (isset($_GET['reject'])) {
    $emp_id = (int)$_GET['reject'];
    $stmt = $pdo->prepare("UPDATE users SET employee_status='REJECTED' WHERE id=? AND role='EMPLOYEE' AND shop_id=?");
    $stmt->execute([$emp_id,$shop['id']]);
    flash_set('success','Employee rejected.');
    header('Location: employees.php');
    exit;
}

$employees = $pdo->prepare("SELECT id,name,email,employee_role,employee_status,created_at FROM users WHERE role='EMPLOYEE' AND shop_id=? ORDER BY created_at DESC");
$employees->execute([$shop['id']]);
$employees = $employees->fetchAll();
?>

<h3>Employees</h3>
<p class="text-muted">Approve employees who signed up for your shop.</p>

<div class="card mt-3"><div class="card-body">
  <div class="table-responsive">
    <table class="table table-striped align-middle mb-0">
  <thead>
    <tr>
      <th>Name</th>
      <th>Email</th>
      <th>Role</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($employees as $e): ?>
      <tr>
        <td><?= htmlspecialchars($e['name']) ?></td>
        <td><?= htmlspecialchars($e['email']) ?></td>
        <td><?= htmlspecialchars($e['employee_role'] ?? 'STAFF') ?></td>
        <td>
          <span class="badge bg-<?= $e['employee_status']==='APPROVED'?'success':($e['employee_status']==='REJECTED'?'danger':'warning') ?>">
            <?= htmlspecialchars($e['employee_status']) ?>
          </span>
        </td>
        <td>
          <?php if ($e['employee_status']==='PENDING'): ?>
            <a class="btn btn-sm btn-success" href="employees.php?approve=<?= (int)$e['id'] ?>">Approve</a>
            <a class="btn btn-sm btn-outline-danger" href="employees.php?reject=<?= (int)$e['id'] ?>">Reject</a>
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
  </div>
</div></div>

<a class="btn btn-outline-secondary" href="dashboard.php">Back to Dashboard</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
