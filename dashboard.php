<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/ai.php';
require_login(['OWNER']);
$u = current_user();

if (!$u['shop_id']) {
    header('Location: create_shop.php');
    exit;
}

$stmt = db()->prepare('SELECT * FROM shops WHERE id = ?');
$stmt->execute([$u['shop_id']]);
$shop = $stmt->fetch();
if (!$shop) {
    flash_set('error','Shop not found.');
    header('Location: create_shop.php');
    exit;
}

if ($shop['status'] !== 'VERIFIED') {
    header('Location: verification.php');
    exit;
}

$pdo = db();
$lowStock = $pdo->prepare('SELECT COUNT(*) c FROM products WHERE shop_id=? AND stock_qty <= low_stock_threshold');
$lowStock->execute([$shop['id']]);
$lowStockCount = (int)($lowStock->fetch()['c'] ?? 0);

// AI restock predictions for low-stock items
$lowPred = ai_low_stock_predictions((int)$shop['id'], 14);

$pendingEmployees = $pdo->prepare("SELECT COUNT(*) c FROM users WHERE role='EMPLOYEE' AND shop_id=? AND employee_status='PENDING'");
$pendingEmployees->execute([$shop['id']]);
$pendingEmployeesCount = (int)($pendingEmployees->fetch()['c'] ?? 0);

$orderCount = $pdo->prepare('SELECT COUNT(*) c FROM orders WHERE shop_id=?');
$orderCount->execute([$shop['id']]);
$orderCount = (int)($orderCount->fetch()['c'] ?? 0);
?>

<div class="d-flex justify-content-between align-items-center">
  <div>
    <h2 class="mb-0">Owner Dashboard</h2>
    <div class="text-muted"><?= htmlspecialchars($shop['name']) ?> (<?= htmlspecialchars($shop['area']) ?>)</div>
  </div>
  <a class="btn btn-outline-secondary" href="../index.php">Home</a>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="text-muted">Low stock items</div>
        <div class="display-6"><?= $lowStockCount ?></div>
        <a href="inventory.php" class="btn btn-sm btn-primary">View Inventory</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="text-muted">Pending employees</div>
        <div class="display-6"><?= $pendingEmployeesCount ?></div>
        <a href="employees.php" class="btn btn-sm btn-primary">Manage Employees</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="text-muted">Total orders</div>
        <div class="display-6"><?= $orderCount ?></div>
        <a href="orders.php" class="btn btn-sm btn-primary">View Orders</a>
      </div>
    </div>
  </div>
</div>

<?php if ($lowPred): ?>
  <div class="card mt-3"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0">🤖 AI Restock Predictor</h5>
        <div class="text-muted small">Low-stock alerts + predicted stockout time (based on last 14 days sales).</div>
      </div>
      <a class="btn btn-sm btn-outline-primary" href="inventory.php">Open Inventory</a>
    </div>
    <div class="table-responsive mt-3">
      <table class="table table-striped align-middle mb-0">
        <thead><tr><th>Product</th><th class="text-end">Stock</th><th class="text-end">Threshold</th><th class="text-end">Avg/day</th><th class="text-end">Est. stockout</th></tr></thead>
        <tbody>
          <?php foreach ($lowPred as $p): ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                <div class="small text-muted">Sold (14d): <?= (int)$p['sold_lookback'] ?></div>
              </td>
              <td class="text-end"><?= (int)$p['stock_qty'] ?></td>
              <td class="text-end"><?= (int)$p['threshold'] ?></td>
              <td class="text-end"><?= htmlspecialchars($p['avg_daily']) ?></td>
              <td class="text-end">
                <?php if ($p['stockout_date']): ?>
                  <span class="badge text-bg-warning">~<?= htmlspecialchars($p['stockout_date']) ?> (<?= (int)$p['days_to_stockout'] ?>d)</span>
                <?php else: ?>
                  <span class="text-muted">Not enough data</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div></div>
<?php endif; ?>

<div class="row g-3 mt-1">
  <div class="col-md-3"><a class="btn btn-outline-dark w-100" href="inventory.php">Inventory</a></div>
  <div class="col-md-3"><a class="btn btn-outline-dark w-100" href="offers.php">Offers & Deals</a></div>
  <div class="col-md-3"><a class="btn btn-outline-dark w-100" href="orders.php">Orders</a></div>
  <div class="col-md-3"><a class="btn btn-outline-dark w-100" href="pos.php">POS</a></div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
