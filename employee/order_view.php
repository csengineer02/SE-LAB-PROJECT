<?php
require_once __DIR__ . '/../includes/header.php';
require_login(['EMPLOYEE']);
$u = current_user();
$id = (int)($_GET['id'] ?? 0);
$pdo = db();
if (($u['employee_status'] ?? 'PENDING') !== 'APPROVED' || !$u['shop_id'] || $id<=0) { header('Location: orders.php'); exit; }

$shop = $pdo->prepare('SELECT * FROM shops WHERE id=?');
$shop->execute([$u['shop_id']]);
$shop = $shop->fetch();
if (!$shop || $shop['status']!=='VERIFIED') { header('Location: dashboard.php'); exit; }

$order = $pdo->prepare('SELECT o.*, u.name customer_name, u.phone customer_phone, u.address customer_address FROM orders o JOIN users u ON u.id=o.customer_user_id WHERE o.id=? AND o.shop_id=?');
$order->execute([$id,$shop['id']]);
$order = $order->fetch();
if (!$order) { flash_set('error','Order not found.'); header('Location: orders.php'); exit; }

$items = $pdo->prepare('SELECT oi.*, p.name product_name FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?');
$items->execute([$id]);
$items = $items->fetchAll();
?>

<h3>Order #<?= (int)$order['id'] ?></h3>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h5 class="card-title">Customer</h5>
      <div><strong><?= htmlspecialchars($order['customer_name']) ?></strong></div>
      <div><?= htmlspecialchars($order['customer_phone'] ?? '') ?></div>
      <div><?= htmlspecialchars($order['delivery_address']) ?></div>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h5 class="card-title">Order</h5>
      <div>Status: <span class="badge bg-secondary"><?= htmlspecialchars($order['status']) ?></span></div>
      <div>Payment: <?= htmlspecialchars($order['payment_method']) ?></div>
      <div>Total: <strong><?= htmlspecialchars($order['total_amount']) ?></strong></div>
      <div class="text-muted">Created: <?= htmlspecialchars($order['created_at']) ?></div>
    </div></div>
  </div>
</div>

<div class="card my-3">
  <div class="card-body">
    <h5>Items</h5>
    <table class="table">
      <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Line Total</th></tr></thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><?= htmlspecialchars($it['product_name']) ?></td>
            <td><?= (int)$it['qty'] ?></td>
            <td><?= htmlspecialchars($it['unit_price']) ?></td>
            <td><?= htmlspecialchars($it['line_total']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<a class="btn btn-outline-secondary" href="orders.php">Back to Orders</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
