<?php
require_once __DIR__ . '/../includes/header.php';
require_login(['employee']);
$pdo = db();
$u = current_user();

$st = $pdo->prepare('SELECT shopid FROM employee WHERE id=? LIMIT 1');
$st->execute([$u['id']]);
$shopId = (int)($st->fetchColumn() ?: 0);
if ($shopId<=0) {
    flash_set('error','Shop not found.');
    redirect('/employee/dashboard.php');
}

// Load products (use selling_price if available)
$products = $pdo->prepare('SELECT id,name,sku,COALESCE(NULLIF(selling_price,0), buying_price) AS price,unit,current_stock FROM product WHERE shopid=? ORDER BY name');
$products->execute([$shopId]);
$products = $products->fetchAll();

$prefill = trim($_GET['q'] ?? '');
$prefillId = 0;
if ($prefill !== '') {
    // Try to find by SKU or barcode
    $st = $pdo->prepare('SELECT p.id FROM product p LEFT JOIN barcode b ON b.id=p.barcodeid WHERE p.shopid=? AND (p.sku=? OR b.code=?) LIMIT 1');
    $st->execute([$shopId, $prefill, $prefill]);
    $prefillId = (int)($st->fetchColumn() ?: 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    $method = $_POST['payment_method'] ?? 'cash';

    if ($productId<=0 || $qty<=0) {
        flash_set('error','Select a product and quantity.');
        redirect('/employee/pos.php');
    }

    // get product
    $st = $pdo->prepare('SELECT COALESCE(NULLIF(selling_price,0), buying_price) AS price, current_stock FROM product WHERE id=? AND shopid=?');
    $st->execute([$productId,$shopId]);
    $p = $st->fetch();
    if (!$p) {
        flash_set('error','Product not found.');
        redirect('/employee/pos.php');
    }
    if ((int)$p['current_stock'] < $qty) {
        flash_set('error','Not enough stock.');
        redirect('/employee/pos.php');
    }

    $total = (float)$p['price'] * $qty;

    $pdo->beginTransaction();
    try {
        // Ensure walk-in customer exists (id=1) - you can change this later
        $custId = 1;

        // Ensure 0% discount exists
        $st = $pdo->prepare('SELECT id FROM discount WHERE shopid=? AND percentage=0 ORDER BY id DESC LIMIT 1');
        $st->execute([$shopId]);
        $disc = $st->fetch();
        if (!$disc) {
            $pdo->prepare('INSERT INTO discount (percentage,start_date,end_date,shopid) VALUES (0,CURDATE(),DATE_ADD(CURDATE(), INTERVAL 10 YEAR),?)')
                ->execute([$shopId]);
            $discountId = (int)$pdo->lastInsertId();
        } else {
            $discountId = (int)$disc['id'];
        }

        $txn = 'pos_' . bin2hex(random_bytes(6));
        $pdo->prepare('INSERT INTO payment (total_amount,payment_method,payment_status,date,transaction_id,customerid,discountid)
                       VALUES (?,?,?,?,?,?,?)')
            ->execute([(int)round($total), $method, 'paid', date('Y-m-d'), $txn, $custId, $discountId]);
        $paymentId = (int)$pdo->lastInsertId();

        $pdo->prepare('INSERT INTO orders (status, customerid, employeeid, paymentid) VALUES (?,?,?,?)')
            ->execute(['completed', $custId, $u['id'], $paymentId]);
        $orderId = (int)$pdo->lastInsertId();

        $pdo->prepare('INSERT INTO orderitem (date,time,quantity,productid,Orderorder_id) VALUES (?,?,?,?,?)')
            ->execute([date('Y-m-d'), date('H:i:s'), $qty, $productId, $orderId]);

        $pdo->prepare('UPDATE product SET current_stock=current_stock-? WHERE id=? AND shopid=?')
            ->execute([$qty,$productId,$shopId]);

        $pdo->commit();
        flash_set('success','POS order completed. Order ID: ' . $orderId);
        redirect('/employee/pos.php');
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash_set('error','POS failed: ' . $e->getMessage());
        redirect('/employee/pos.php');
    }
}

$title = 'POS';
?>

<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
  <div>
    <h3 class="mb-1">POS / Billing</h3>
    <div class="text-muted">Scan SKU/Barcode or select product to complete a walk-in sale.</div>
  </div>
  <a class="btn btn-outline-success" href="dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card app-card">
      <div class="card-body">
        <h6 class="mb-3">Find Product (Scan)</h6>
        <form class="row g-2 form-modern" method="get">
          <div class="col-8">
            <input class="form-control" name="q" value="<?= h($prefill) ?>" placeholder="SKU or Barcode" />
          </div>
          <div class="col-4 d-grid">
            <button class="btn btn-soft" type="submit"><i class="bi bi-upc-scan me-1"></i>Find</button>
          </div>
        </form>
        <div class="text-muted small mt-2">Tip: type SKU or barcode number.</div>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card app-card">
      <div class="card-body">
        <h6 class="mb-3">Create Sale</h6>
        <form class="row g-3 form-modern" method="post">
          <div class="col-12">
            <label class="form-label">Product</label>
            <select class="form-select" name="product_id" required>
              <option value="">Select a product</option>
              <?php foreach ($products as $p): ?>
                <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$prefillId) ? 'selected' : '' ?>>
                  <?= h($p['name']) ?> (stock <?= (int)$p['current_stock'] ?>, price <?= number_format((float)$p['price'],2) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Quantity</label>
            <input class="form-control" type="number" name="qty" value="1" min="1" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Payment Method</label>
            <select class="form-select" name="payment_method">
              <option value="cash">Cash</option>
              <option value="bkash">bKash (mock)</option>
              <option value="card">Card (mock)</option>
            </select>
          </div>
          <div class="col-12 d-grid">
            <button class="btn btn-primary" type="submit"><i class="bi bi-receipt-cutoff me-1"></i>Complete Sale</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
