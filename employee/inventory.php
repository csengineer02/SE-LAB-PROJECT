<?php
require_once __DIR__ . '/../includes/header.php';
require_login(['employee']);
$pdo = db();
$u = current_user();

$st = $pdo->prepare("SELECT e.shopid AS id, s.status FROM employee e JOIN shop s ON s.id=e.shopid WHERE e.id=? LIMIT 1");
$st->execute([$u["id"]]);
$shop = $st->fetch();
if (!$shop) {
    flash_set("error", "Employee profile not found.");
    redirect("/employee/dashboard.php");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'add';
    if ($mode === 'add') {
        $name = trim($_POST['name'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $buying_price = (float)($_POST['buying_price'] ?? 0);
        $selling_price = (float)($_POST['selling_price'] ?? 0);
        $unit = trim($_POST['unit'] ?? 'pcs');
        $stock = (int)($_POST['current_stock'] ?? 0);
        $min_stock = (int)($_POST['min_stock_level'] ?? 5);
        $brand_name = trim($_POST['brand_name'] ?? '');
        $category_name = trim($_POST['category_name'] ?? '');
        $barcode_code = trim($_POST['barcode_code'] ?? '');

        if ($name==='' || $sku==='' || $brand_name==='' || $category_name==='' || $barcode_code==='') {
            flash_set('error', 'Name, SKU, Brand, Category and Barcode are required.');
            redirect('/employee/inventory.php');
        }

        // Brand
        $st = $pdo->prepare('SELECT Brand_id FROM brand WHERE Name=? LIMIT 1');
        $st->execute([$brand_name]);
        $brand = $st->fetch();
        if (!$brand) {
            $pdo->prepare('INSERT INTO brand (Name) VALUES (?)')->execute([$brand_name]);
            $brandId = (int)$pdo->lastInsertId();
        } else {
            $brandId = (int)$brand['Brand_id'];
        }

        // Category
        $st = $pdo->prepare('SELECT Category_id FROM category WHERE Name=? LIMIT 1');
        $st->execute([$category_name]);
        $cat = $st->fetch();
        if (!$cat) {
            $pdo->prepare('INSERT INTO category (Name) VALUES (?)')->execute([$category_name]);
            $catId = (int)$pdo->lastInsertId();
        } else {
            $catId = (int)$cat['Category_id'];
        }

        // Barcode
        $st = $pdo->prepare('SELECT id FROM barcode WHERE code=? LIMIT 1');
        $st->execute([$barcode_code]);
        $bc = $st->fetch();
        if (!$bc) {
            $pdo->prepare('INSERT INTO barcode (code) VALUES (?)')->execute([$barcode_code]);
            $bcId = (int)$pdo->lastInsertId();
        } else {
            $bcId = (int)$bc['id'];
        }

        $st = $pdo->prepare('INSERT INTO product (name, sku, buying_price, selling_price, unit, current_stock, expirydate, shopid, brandBrand_id, CategoryCategory_id, barcodeid, min_stock_level)
                             VALUES (?,?,?,?,?, ?, CURRENT_TIMESTAMP, ?,?,?,?,?)');
        $st->execute([$name, $sku, $buying_price, $selling_price, $unit, $stock, (int)$shop['id'], $brandId, $catId, $bcId, $min_stock]);

        flash_set('success', 'Product added.');
        redirect('/employee/inventory.php');
    }

    if ($mode === 'adjust_stock') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $delta = (int)($_POST['delta'] ?? 0);
        if ($productId > 0 && $delta !== 0) {
            $pdo->prepare('UPDATE product SET current_stock = GREATEST(current_stock + ?, 0) WHERE id=? AND shopid=?')
                ->execute([$delta, $productId, (int)$shop['id']]);
            flash_set('success', 'Stock updated.');
        }
        redirect('/employee/inventory.php');
    }
}

$products = $pdo->prepare('SELECT p.*, b.Name AS brand, c.Name AS category, bc.code AS barcode
                           FROM product p
                           JOIN brand b ON b.Brand_id = p.brandBrand_id
                           JOIN category c ON c.Category_id = p.CategoryCategory_id
                           JOIN barcode bc ON bc.id = p.barcodeid
                           WHERE p.shopid=? ORDER BY p.id DESC');
$products->execute([(int)$shop['id']]);
$products = $products->fetchAll();

$title = 'Inventory';
?>
<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
  <div>
    <h3 class="mb-1">Inventory</h3>
    <div class="text-muted">Manage products, stock, buying price and selling price.</div>
  </div>
  <a class="btn btn-outline-success" href="dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card app-card">
      <div class="card-body">
        <h6 class="mb-3">Add Product</h6>
        <form class="row g-3 form-modern" method="post">
          <input type="hidden" name="mode" value="add" />
          <div class="col-12">
            <label class="form-label">Name *</label>
            <input class="form-control" name="name" required />
          </div>
          <div class="col-12">
            <label class="form-label">SKU *</label>
            <input class="form-control" name="sku" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Buying Price</label>
            <input class="form-control" name="buying_price" type="number" step="0.01" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Selling Price</label>
            <input class="form-control" name="selling_price" type="number" step="0.01" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Unit</label>
            <input class="form-control" name="unit" placeholder="pcs / kg / l" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Current Stock</label>
            <input class="form-control" name="current_stock" type="number" value="0" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Min Stock Level</label>
            <input class="form-control" name="min_stock_level" type="number" value="5" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Barcode *</label>
            <input class="form-control" name="barcode_code" required placeholder="scan or type" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Brand *</label>
            <input class="form-control" name="brand_name" required placeholder="e.g., Pran" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Category *</label>
            <input class="form-control" name="category_name" required placeholder="e.g., Snacks" />
          </div>
          <div class="col-12 d-grid">
            <button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg me-1"></i>Add Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card app-card">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h6 class="mb-0">Products</h6>
          <span class="text-muted small">Low stock highlighted</span>
        </div>

        <?php if (!$products): ?>
          <div class="text-muted">No products yet.</div>
        <?php else: ?>
          <div class="table-responsive table-modern">
            <table class="table align-middle mb-0">
              <thead>
                <tr>
                  <th>Product</th>
                  <th class="text-end">Stock</th>
                  <th class="text-end">Buy</th>
                  <th class="text-end">Sell</th>
                  <th>Brand</th>
                  <th>Category</th>
                  <th style="width:220px">Adjust</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($products as $p): ?>
                <?php $low = ((int)$p['current_stock'] <= (int)($p['min_stock_level'] ?? 5)); ?>
                <tr class="<?= $low ? 'table-warning' : '' ?>">
                  <td>
                    <div class="fw-semibold"><?= h($p['name']) ?></div>
                    <div class="text-muted small">SKU: <?= h($p['sku']) ?> &middot; Barcode: <?= h($p['barcode']) ?></div>
                  </td>
                  <td class="text-end fw-bold"><?= (int)$p['current_stock'] ?></td>
                  <td class="text-end"><?= number_format((float)$p['buying_price'],2) ?></td>
                  <td class="text-end"><?= number_format((float)($p['selling_price'] ?? 0),2) ?></td>
                  <td><?= h($p['brand']) ?></td>
                  <td><?= h($p['category']) ?></td>
                  <td>
                    <form method="post" class="d-flex gap-2 align-items-center">
                      <input type="hidden" name="mode" value="adjust_stock" />
                      <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>" />
                      <input class="form-control form-control-sm" name="delta" type="number" placeholder="+ / -" />
                      <button class="btn btn-sm btn-outline-success" type="submit">Update</button>
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
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
