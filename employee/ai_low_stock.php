<?php
require_once __DIR__ . '/../includes/header.php';
require_login(['employee']);

$pdo = db();
$u = current_user();

$st = $pdo->prepare('SELECT shopid FROM employee WHERE id=? LIMIT 1');
$st->execute([(int)$u['id']]);
$emp = $st->fetch();
$shopId = (int)($emp['shopid'] ?? 0);
if ($shopId <= 0) {
    flash_set('error', 'Employee profile is missing Shop ID.');
    redirect('/employee/dashboard.php');
}

$min = (int)($_GET['min'] ?? 5);
if ($min < 1) $min = 1;
if ($min > 500) $min = 500;

$st = $pdo->prepare('SELECT id, name, sku, unit, current_stock FROM product WHERE shopid=? AND current_stock <= ? ORDER BY current_stock ASC, id DESC');
$st->execute([$shopId, $min]);
$low = $st->fetchAll();

// Basic stock-out prediction (best-effort)
$pred_days = []; // productId => daysLeft (float|null)
if ($low) {
    $ids = array_map(fn($r) => (int)$r['id'], $low);
    $ph = implode(',', array_fill(0, count($ids), '?'));
    // avg daily OUT for last 30 days
    $sql = "SELECT productid, SUM(CAST(quantity AS SIGNED)) AS out_qty\n            FROM stocktransaction\n            WHERE type IN ('out','sale') AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)\n              AND productid IN ($ph)\n            GROUP BY productid";
    $st2 = $pdo->prepare($sql);
    $st2->execute($ids);
    $out = $st2->fetchAll();
    $outMap = [];
    foreach ($out as $row) {
        $outMap[(int)$row['productid']] = (int)($row['out_qty'] ?? 0);
    }
    foreach ($low as $r) {
        $pid = (int)$r['id'];
        $outQty = $outMap[$pid] ?? 0;
        if ($outQty <= 0) {
            $pred_days[$pid] = null;
        } else {
            $avg = $outQty / 30.0;
            $pred_days[$pid] = ((int)$r['current_stock'] <= 0) ? 0.0 : ((float)$r['current_stock'] / $avg);
        }
    }
}

$title = 'Low Stock Alert ()';
?>

<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
  <div>
    <h3 class="mb-1">Low Stock ()</h3>
    <div class="text-muted">Alerts when stock is below threshold and estimates stock-out time (if sales data exists).</div>
  </div>
  <a class="btn btn-outline-success" href="dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card app-card mb-3">
  <div class="card-body">
    <form class="row g-3 align-items-end form-modern" method="get" action="<?= BASE_URL ?>/employee/ai_low_stock.php">
      <div class="col-md-4">
        <label class="form-label">Min level (alert when stock ≤)</label>
        <input class="form-control" type="number" name="min" min="1" max="500" value="<?= (int)$min ?>" />
      </div>
      <div class="col-md-3 d-grid">
        <button class="btn btn-primary" type="submit"><i class="bi bi-play-fill me-1"></i>Run</button>
      </div>
      <div class="col-md-5">
        <div class="text-muted small">Prediction uses last 30 days OUT transactions from <code>stocktransaction</code>.</div>
      </div>
    </form>
  </div>
</div>

<div class="card app-card">
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h6 class="mb-0">Low stock items</h6>
      <span class="text-muted small"><?= count($low) ?> item(s)</span>
    </div>

    <?php if (!$low): ?>
      <div class="text-muted">No low stock items found for the chosen threshold.</div>
    <?php else: ?>
      <div class="table-responsive table-modern">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Product</th>
              <th>SKU</th>
              <th class="text-end">Current</th>
              <th class="text-end">Suggested reorder</th>
              <th class="text-end">Est. days to stock-out</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($low as $p):
            $pid = (int)$p['id'];
            $cur = (int)($p['current_stock'] ?? 0);
            $suggest = max(0, ($min * 3) - $cur);
            $days = $pred_days[$pid] ?? null;
          ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= h($p['name'] ?? '') ?></div>
                <div class="text-muted small">Unit: <?= h($p['unit'] ?? '') ?></div>
              </td>
              <td class="text-muted"><?= h($p['sku'] ?? '') ?></td>
              <td class="text-end fw-bold"><span class="badge text-bg-warning"><?= (int)$cur ?></span></td>
              <td class="text-end"><span class="badge text-bg-success"><?= (int)$suggest ?></span></td>
              <td class="text-end">
                <?php if ($days === null): ?>
                  <span class="text-muted">N/A</span>
                <?php else: ?>
                  <?= number_format((float)$days, 1) ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
