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

$q = trim((string)($_POST['q'] ?? ($_GET['q'] ?? '')));
$qty = (int)($_POST['qty'] ?? 1);
if ($qty < 1) $qty = 1;
if ($qty > 999999) $qty = 999999;

$found = null;

function find_product_by_scan(PDO $pdo, int $shopId, string $q): ?array {
    $q = trim($q);
    if ($q === '') return null;
    $st = $pdo->prepare("SELECT p.id, p.name, p.sku, p.unit, p.current_stock,
            bc.code AS barcode
        FROM product p
        LEFT JOIN barcode bc ON bc.id = p.barcodeid
        WHERE p.shopid = ? AND (p.sku = ? OR bc.code = ?)
        LIMIT 1");
    $st->execute([$shopId, $q, $q]);
    $r = $st->fetch();
    return $r ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = (string)($_POST['mode'] ?? 'lookup');
    if ($mode === 'stock_in') {
        $found = find_product_by_scan($pdo, $shopId, $q);
        if (!$found) {
            flash_set('error', 'Product not found for this SKU/Barcode.');
            redirect('/employee/scan.php');
        }
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE product SET current_stock = current_stock + ? WHERE id=? AND shopid=?')
                ->execute([$qty, (int)$found['id'], $shopId]);

            // Record transaction (best-effort)
            $pdo->prepare('INSERT INTO stocktransaction (quantity, type, date, productid, employeeid) VALUES (?,?,?,?,?)')
                ->execute([(string)$qty, 'in', date('Y-m-d'), (int)$found['id'], (int)$u['id']]);

            $pdo->commit();
            flash_set('success', 'Stock updated. +' . $qty . ' added to ' . ($found['name'] ?? 'product') . '.');
            redirect('/employee/scan.php?q=' . urlencode($q));
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash_set('error', 'Stock update failed: ' . $e->getMessage());
            redirect('/employee/scan.php');
        }
    }
}

if ($q !== '') {
    $found = find_product_by_scan($pdo, $shopId, $q);
}

$title = 'Scan Products';
?>

<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
  <div>
    <h3 class="mb-1">Scan Products</h3>
    <div class="text-muted">Search by SKU or barcode, then take action.</div>
  </div>
  <a class="btn btn-outline-success" href="dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card app-card">
      <div class="card-body">
        <h6 class="mb-3">Find Product (Scan)</h6>
        <form method="get" class="row g-2 form-modern">
          <div class="col-8">
            <label class="form-label visually-hidden">SKU or Barcode</label>
            <input class="form-control" name="q" value="<?= h($q) ?>" placeholder="SKU or barcode" />
          </div>
          <div class="col-4 d-grid">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Find</button>
          </div>
          <div class="col-12">
            <div class="text-muted small">Tip: if your scanner types into the input, press Enter to search.</div>
          </div>
        </form>

        <hr class="my-4" />

        <h6 class="mb-3">Stock In (Add Qty)</h6>
        <form method="post" class="row g-2 form-modern">
          <input type="hidden" name="mode" value="stock_in" />
          <div class="col-7">
            <input class="form-control" name="q" value="<?= h($q) ?>" placeholder="SKU or barcode" required />
          </div>
          <div class="col-3">
            <input class="form-control" name="qty" type="number" min="1" value="<?= (int)$qty ?>" />
          </div>
          <div class="col-2 d-grid">
            <button class="btn btn-soft" type="submit" title="Add stock"><i class="bi bi-plus-lg"></i></button>
          </div>
          <div class="col-12">
            <div class="text-muted small">Adds stock and records a <code>stocktransaction</code> entry (type <code>in</code>).</div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card app-card">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h6 class="mb-0">Result</h6>
          <span class="badge badge-soft"><i class="bi bi-upc-scan me-1"></i>Scanner</span>
        </div>

        <?php if (!$q): ?>
          <div class="text-muted">Scan a barcode or type a SKU to see the product details.</div>
        <?php elseif (!$found): ?>
          <div class="alert alert-warning app-alert" role="alert"><i class="bi bi-exclamation-triangle me-2"></i>Product not found for <strong><?= h($q) ?></strong>.</div>
        <?php else: ?>
          <div class="p-3 rounded-4 bg-white border" style="border-color: rgba(229,231,235,.95)!important;">
            <div class="d-flex align-items-start justify-content-between gap-3">
              <div>
                <div class="fw-bold fs-5 mb-1"><?= h($found['name'] ?? '') ?></div>
                <div class="text-muted small">SKU: <?= h($found['sku'] ?? '') ?> &middot; Barcode: <?= h($found['barcode'] ?? '') ?></div>
                <div class="mt-2">
                  <span class="chip chip-info">Stock: <?= (int)($found['current_stock'] ?? 0) ?></span>
                  <span class="chip chip-success ms-2">Unit: <?= h($found['unit'] ?? '') ?></span>
                </div>
              </div>
              <div class="d-flex flex-column gap-2" style="min-width:200px">
                <a class="btn btn-outline-success" href="<?= BASE_URL ?>/employee/pos.php?q=<?= urlencode((string)($found['sku'] ?? $q)) ?>">
                  <i class="bi bi-receipt-cutoff me-1"></i>Open in POS
                </a>
                <a class="btn btn-soft" href="<?= BASE_URL ?>/employee/inventory.php">
                  <i class="bi bi-box-seam me-1"></i>Go to Inventory
                </a>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
