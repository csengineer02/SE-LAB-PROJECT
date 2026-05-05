<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/util.php';
require_login(['EMPLOYEE']);
require_perm('POS_ACCESS');
$u=current_user();
$pdo=db();
$id=(int)($_GET['id']??0);
$sale=$pdo->prepare("SELECT ps.*, s.name AS shop_name, s.address AS shop_address, s.phone AS shop_phone
  FROM pos_sales ps JOIN shops s ON s.id=ps.shop_id WHERE ps.id=? AND ps.shop_id=?");
$sale->execute([$id,$u['shop_id']]);
$sale=$sale->fetch();
if(!$sale){ http_response_code(404); echo 'Not found'; exit; }
$items=$pdo->prepare("SELECT pi.*, p.name, p.sku, p.barcode FROM pos_items pi JOIN products p ON p.id=pi.product_id WHERE pi.pos_sale_id=?");
$items->execute([$id]);
$items=$items->fetchAll();

$qrData = 'GROCER360|POS|'.$sale['id'].'|'.$sale['receipt_code'].'|'.$sale['total_amount'];
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data='.urlencode($qrData);
?>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="d-flex align-items-center gap-2">
          <div class="rounded-3" style="width:44px;height:44px;background:rgba(47,191,113,.18);display:flex;align-items:center;justify-content:center;font-weight:800;color:var(--g360-dark)">G</div>
          <div>
            <div class="h4 mb-0"><?= htmlspecialchars($sale['shop_name']) ?></div>
            <div class="text-muted"><?= htmlspecialchars($sale['shop_address']) ?> • <?= htmlspecialchars($sale['shop_phone']) ?></div>
          </div>
        </div>
        <div class="mt-3">
          <div class="fw-semibold">Receipt #<?= (int)$sale['id'] ?> <span class="badge badge-g360 ms-2"><?= htmlspecialchars($sale['receipt_code']) ?></span></div>
          <div class="text-muted">Date: <?= htmlspecialchars($sale['created_at']) ?> • Cashier: <?= htmlspecialchars($u['name']) ?></div>
          <div class="text-muted">Customer: <?= htmlspecialchars($sale['customer_name']) ?> • Payment: <?= htmlspecialchars($sale['payment_method']) ?></div>
        </div>
      </div>
      <div class="text-center">
        <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR" class="img-fluid" style="max-width:140px">
        <div class="small text-muted mt-1">Scan for reference</div>
      </div>
    </div>

    <hr>
    <div class="table-responsive">
      <table class="table">
        <thead class="table-light"><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Unit</th><th class="text-end">Disc</th><th class="text-end">Total</th></tr></thead>
        <tbody>
          <?php foreach($items as $it): ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($it['name']) ?></div>
                <div class="small text-muted">SKU: <?= htmlspecialchars($it['sku']) ?> • Barcode: <?= htmlspecialchars($it['barcode']) ?></div>
              </td>
              <td class="text-end"><?= (int)$it['qty'] ?></td>
              <td class="text-end"><?= number_format((float)$it['unit_price'],2) ?></td>
              <td class="text-end"><?= number_format((float)$it['discount_amount'],2) ?></td>
              <td class="text-end fw-semibold"><?= number_format((float)$it['line_total'],2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="row justify-content-end">
      <div class="col-md-5">
        <div class="p-3 rounded-3" style="background:rgba(47,191,113,.10)">
          <div class="d-flex justify-content-between"><span class="text-muted">Subtotal</span><span class="fw-semibold"><?= number_format((float)$sale['subtotal_amount'],2) ?></span></div>
          <div class="d-flex justify-content-between"><span class="text-muted">Discount</span><span class="fw-semibold"><?= number_format((float)$sale['discount_total'],2) ?></span></div>
          <hr class="my-2">
          <div class="d-flex justify-content-between"><span class="fw-bold">Grand Total</span><span class="fw-bold fs-5"><?= number_format((float)$sale['total_amount'],2) ?></span></div>
        </div>
      </div>
    </div>

    <div class="d-print-none mt-3 d-flex gap-2">
      <button class="btn btn-primary" onclick="window.print()">Print / Save PDF</button>
      <a class="btn btn-outline-secondary" href="pos.php">Back to POS</a>
    </div>
  </div>
</div>

<style>
@media print{
  nav,.d-print-none{display:none!important}
  body{background:#fff}
  .container{max-width:100%!important}
  .card{box-shadow:none!important}
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
