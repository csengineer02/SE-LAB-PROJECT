<?php
require_once __DIR__ . '/../includes/header.php';
require_login(['employee']);

$pdo = db();
$u   = current_user();

$st = $pdo->prepare("
  SELECT e.shopid, s.name AS shop_name, s.status
  FROM employee e
  JOIN shop s ON s.id = e.shopid
  WHERE e.id = ?
  LIMIT 1
");
$st->execute([$u['id']]);
$emp = $st->fetch();

if (!$emp) {
  flash_set('error', 'Employee profile not found.');
  redirect('/logout.php');
}

$shopId = (int)$emp['shopid'];

$lowStock = [];
if ($shopId > 0) {
  $st = $pdo->prepare("
    SELECT id, name, current_stock, min_stock_level
    FROM product
    WHERE shopid = ?
      AND current_stock <= IFNULL(min_stock_level, 5)
    ORDER BY current_stock ASC
    LIMIT 20
  ");
  $st->execute([$shopId]);
  $lowStock = $st->fetchAll();

  $today = new DateTime('today');

  foreach ($lowStock as &$p) {
    $st2 = $pdo->prepare("
      SELECT COALESCE(SUM(quantity), 0) AS sold
      FROM orderitem
      WHERE productid = ?
        AND date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    ");
    $st2->execute([(int)$p['id']]);

    $sold = (int)($st2->fetchColumn() ?: 0);
    $avg  = $sold / 14.0;

    if ($avg > 0.01) {
      $days = (int)ceil(((int)$p['current_stock']) / $avg);
      $pred = clone $today;
      $pred->modify('+' . $days . ' days');

      $p['pred_days'] = $days;
      $p['pred_date'] = $pred->format('Y-m-d');
    } else {
      $p['pred_days'] = null;
      $p['pred_date'] = null;
    }
  }
  unset($p);
}

$title = 'Employee Dashboard';
?>

<div class="card">
  <div class="h1">Employee Dashboard</div>

  <?php if (!empty($lowStock)): ?>
    <div class="alert alert-warning mt-3">
      <div class="fw-semibold mb-2">
        <i class="bi bi-exclamation-triangle me-1"></i> Low stock alert
      </div>

      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th>Product</th>
              <th class="text-end">Stock</th>
              <th class="text-end">Min</th>
              <th>Predicted stock-out</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($lowStock as $p): ?>
              <tr>
                <td><?= h($p['name']) ?></td>
                <td class="text-end fw-bold"><?= (int)$p['current_stock'] ?></td>
                <td class="text-end"><?= (int)$p['min_stock_level'] ?></td>
                <td>
                  <?php if (!empty($p['pred_date'])): ?>
                    <span class="badge bg-warning-subtle text-dark border">
                      ~<?= h($p['pred_date']) ?> (<?= (int)$p['pred_days'] ?> days)
                    </span>
                  <?php else: ?>
                    <span class="text-muted">Not enough data</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="mt-2">
        <a class="btn btn-sm btn-outline-success" href="inventory.php">Restock now</a>
        <a class="btn btn-sm btn-outline-success" href="ai_low_stock.php" style="margin-left:8px">View</a>
      </div>
    </div>
  <?php endif; ?>

  <div class="muted">
    Shop: <b><?= h($emp['shop_name']) ?></b>
    (ID <?= (int)$emp['shopid'] ?>)
    &middot; Status: <?= h($emp['status']) ?>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <h3>Inventory</h3>
    <p class="muted">Add products and update stock.</p>
    <a class="btn" href="inventory.php">Open Inventory</a>
  </div>

  <div class="card">
    <h3>POS / Billing</h3>
    <p class="muted">Create walk-in orders and reduce stock.</p>
    <a class="btn" href="pos.php">Open POS</a>
  </div>

  <div class="card">
    <h3>Orders</h3>
    <p class="muted">See assigned online orders.</p>
    <a class="btn" href="orders.php">View Orders</a>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
