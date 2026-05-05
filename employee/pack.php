<?php
require_once __DIR__ . '/../includes/header.php';
require_login(['employee']);

$pdo = db();
$u = current_user();

$st = $pdo->prepare('SELECT shopid FROM employee WHERE id=? LIMIT 1');
$st->execute([$u['id']]);
$shopId = (int)($st->fetchColumn() ?: 0);
if ($shopId <= 0) {
    flash_set('error', 'Shop not found.');
    redirect('/employee/dashboard.php');
}

$allowedNext = ['placed' => 'verified', 'verified' => 'packed', 'packed' => 'out_for_delivery'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_status'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = (string)($_POST['status'] ?? '');
    $ok = in_array($newStatus, ['verified','packed','out_for_delivery'], true);
    if ($orderId > 0 && $ok) {
        $st = $pdo->prepare("UPDATE orders o
            JOIN orderitem oi ON oi.Orderorder_id = o.order_id
            JOIN product pr ON pr.id = oi.productid
            SET o.status = ?
            WHERE o.order_id = ? AND pr.shopid = ?");
        $st->execute([$newStatus, $orderId, $shopId]);
        flash_set('success', 'Order updated.');
    }
    redirect('/employee/pack.php');
}

// Orders for this shop that are not completed
$orders = $pdo->prepare("SELECT o.order_id, o.status, p.total_amount, p.payment_method, p.payment_status, p.date,
        c.name AS customer_name, c.phone AS customer_phone,
        COUNT(oi.id) AS items
    FROM orders o
    JOIN payment p ON p.id = o.paymentid
    JOIN customer c ON c.id = o.customerid
    JOIN orderitem oi ON oi.Orderorder_id = o.order_id
    JOIN product pr ON pr.id = oi.productid
    WHERE pr.shopid = ? AND o.status IN ('placed','verified','packed','out_for_delivery')
    GROUP BY o.order_id
    ORDER BY o.order_id DESC");
$orders->execute([$shopId]);
$orders = $orders->fetchAll();

$title = 'Pack Orders';
?>

<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
  <div>
    <h3 class="mb-1">Pack Orders</h3>
    <div class="text-muted">Verify &amp; pack items, then mark ready for delivery.</div>
  </div>
  <a class="btn btn-outline-success" href="dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card app-card">
  <div class="card-body">
    <?php if (!$orders): ?>
      <div class="text-muted">No orders to pack right now.</div>
    <?php else: ?>
      <div class="table-responsive table-modern">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Order</th>
              <th>Customer</th>
              <th>Status</th>
              <th>Payment</th>
              <th class="text-end">Amount</th>
              <th style="width:220px">Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($orders as $o):
            $st = (string)($o['status'] ?? 'placed');
            $next = $allowedNext[$st] ?? null;
          ?>
            <tr>
              <td class="fw-semibold">#<?= (int)$o['order_id'] ?>
                <div class="text-muted small"><?= (int)$o['items'] ?> item(s) &middot; <?= h($o['date'] ?? '') ?></div>
              </td>
              <td>
                <div class="fw-semibold"><?= h($o['customer_name'] ?? '') ?></div>
                <div class="text-muted small"><?= h($o['customer_phone'] ?? '') ?></div>
              </td>
              <td><span class="chip chip-info"><?= h($st) ?></span></td>
              <td>
                <div class="text-muted small"><?= h($o['payment_method'] ?? '') ?></div>
                <div class="small fw-semibold"><?= h($o['payment_status'] ?? '') ?></div>
              </td>
              <td class="text-end fw-semibold"><?= number_format((float)($o['total_amount'] ?? 0), 0) ?></td>
              <td>
                <form method="post" class="d-flex gap-2">
                  <input type="hidden" name="set_status" value="1" />
                  <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>" />
                  <select class="form-select form-select-sm" name="status">
                    <?php foreach (['verified','packed','out_for_delivery'] as $opt): ?>
                      <option value="<?= h($opt) ?>" <?= ($opt === $st) ? 'selected' : '' ?>><?= h($opt) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-sm btn-outline-success" type="submit">
                    <i class="bi bi-check2-circle me-1"></i>Update
                  </button>
                </form>
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
