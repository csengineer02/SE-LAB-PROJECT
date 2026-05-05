<?php
require_once __DIR__ . '/../includes/header.php';
require_login(['employee']);
$pdo = db();
$u = current_user();

// Employee's shop
$st = $pdo->prepare('SELECT shopid FROM employee WHERE id=? LIMIT 1');
$st->execute([$u['id']]);
$shopId = (int)($st->fetchColumn() ?: 0);
if ($shopId <= 0) {
    flash_set('error', 'Shop not found.');
    redirect('/employee/dashboard.php');
}

$allowedStatuses = ['placed','verified','packed','out_for_delivery','delivered','cancelled'];

// Delivery hub: available riders for assignment (used by the "Assign rider" dropdown)
$st = $pdo->prepare("SELECT rider_id, name, phone FROM deliveryrider WHERE active_status='Active' ORDER BY rider_id DESC");
$st->execute();
$riders = $st->fetchAll();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_rider'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $riderId = (int)($_POST['rider_id'] ?? 0);
    if ($orderId > 0 && $riderId > 0) {
        // Ensure the order belongs to this shop
        $st = $pdo->prepare("SELECT o.order_id, c.address AS customer_address, s.address AS shop_address
                             FROM orders o
                             JOIN customer c ON c.id=o.customerid
                             JOIN orderitem oi ON oi.Orderorder_id=o.order_id
                             JOIN product pr ON pr.id=oi.productid
                             JOIN shop s ON s.id=pr.shopid
                             WHERE o.order_id=? AND pr.shopid=? LIMIT 1");
        $st->execute([$orderId, $shopId]);
        $row = $st->fetch();
        if ($row) {
            // Create delivery row if missing
            $st2 = $pdo->prepare("SELECT id FROM delivery WHERE Orderorder_id=? LIMIT 1");
            $st2->execute([$orderId]);
            $delId = (int)($st2->fetchColumn() ?: 0);
            if ($delId <= 0) {
                $st3 = $pdo->prepare("INSERT INTO delivery (status, pickup_address, delivery_address, expected_delivery_time, delivered_at, Orderorder_id, deliveryriderrider_id)
                                      VALUES (0, ?, ?, NOW(), '00:00:00', ?, ?)");
                $st3->execute([$row['shop_address'], $row['customer_address'], $orderId, $riderId]);
            } else {
                $pdo->prepare("UPDATE delivery SET deliveryriderrider_id=?, status=0 WHERE id=?")->execute([$riderId, $delId]);
            }
            // Set order to verified if still placed
            $pdo->prepare("UPDATE orders SET status=CASE WHEN status='placed' THEN 'verified' ELSE status END WHERE order_id=?")->execute([$orderId]);
            flash_set('success','Rider assigned.');
        }
    }
    redirect($_SERVER['PHP_SELF']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = (string)($_POST['status'] ?? '');
    if ($orderId > 0 && in_array($newStatus, $allowedStatuses, true)) {
        // Allow any employee of this shop to update the order status
        $st = $pdo->prepare("UPDATE orders o
            JOIN orderitem oi ON oi.Orderorder_id = o.order_id
            JOIN product pr ON pr.id = oi.productid
            SET o.status=?
            WHERE o.order_id=? AND pr.shopid=?");
        $st->execute([$newStatus, $orderId, $shopId]);
        flash_set('success', 'Order status updated.');
    }
    redirect('/employee/orders.php');
}

$orders = $pdo->prepare("SELECT o.order_id, o.status, p.total_amount, p.delivery_charge, p.payment_method, p.payment_status, p.date,
                            c.name AS customer_name, c.phone AS customer_phone,
                            COUNT(oi.id) AS item_count
                         FROM orders o
                         JOIN payment p ON p.id = o.paymentid
                         JOIN customer c ON c.id = o.customerid
                         JOIN orderitem oi ON oi.Orderorder_id = o.order_id
                         JOIN product pr ON pr.id = oi.productid
                         WHERE pr.shopid = ?
                         GROUP BY o.order_id
                         ORDER BY o.order_id DESC");
$orders->execute([$shopId]);
$orders = $orders->fetchAll();

$title = 'Shop Orders';
?>
<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
  <div>
    <h3 class="mb-1">Orders</h3>
    <div class="text-muted">All online orders for this shop. Update status to keep customers informed.</div>
  </div>
  <a class="btn btn-outline-success" href="dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card app-card">
  <div class="card-body">
    <?php if (!$orders): ?>
      <div class="text-muted">No orders yet.</div>
    <?php else: ?>
      <div class="table-modern table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th style="width:90px">Order</th>
              <th>Customer</th>
              <th style="width:280px">Status</th>
              <th>Payment</th>
              <th class="text-end">Amount</th>
              <th style="width:120px">Date</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td class="fw-semibold">#<?= (int)$o['order_id'] ?></td>
              <td>
                <div class="fw-semibold"><?= h($o['customer_name'] ?? '') ?></div>
                <div class="text-muted small"><?= h($o['customer_phone'] ?? '') ?></div>
              </td>
              <td>
                
              <form method="post" class="d-flex align-items-center gap-2 mb-2">
                <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
                <input type="hidden" name="assign_rider" value="1">
                <select class="form-select form-select-sm" name="rider_id" style="min-width:160px">
                  <option value="">Assign rider...</option>
                  <?php foreach ($riders as $r): ?>
                    <option value="<?= (int)$r['rider_id'] ?>"><?= h($r['name']) ?> (<?= h($r['phone']) ?>)</option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-soft" type="submit">Assign</button>
              </form>
<form method="post" class="d-flex align-items-center gap-2">
                  <input type="hidden" name="update_status" value="1" />
                  <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>" />
                  <select class="form-select form-select-sm" name="status">
                    <?php foreach ($allowedStatuses as $s): ?>
                      <option value="<?= h($s) ?>" <?= ($s === $o['status']) ? 'selected' : '' ?>><?= h($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-sm btn-outline-success" type="submit">Update</button>
                </form>
              </td>
              <td>
                <span class="text-muted small"><?= h($o['payment_method']) ?></span>
                <div class="small fw-semibold"><?= h($o['payment_status']) ?></div>
              </td>
              <td class="text-end fw-semibold"><?= number_format((float)$o['total_amount'], 0) ?></td>
              <td><?= h($o['date']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
