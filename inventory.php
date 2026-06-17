<?php
require_once __DIR__ . '/../includes/header.php';
require_login(['OWNER']);
$u = current_user();
if (!$u['shop_id']) { header('Location: create_shop.php'); exit; }

$shop = db()->prepare('SELECT * FROM shops WHERE id=? AND owner_user_id=?');
$shop->execute([$u['shop_id'],$u['id']]);
$shop = $shop->fetch();
if (!$shop || $shop['status']!=='VERIFIED') { header('Location: verification.php'); exit; }

$pdo = db();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare('DELETE FROM products WHERE id=? AND shop_id=?')->execute([$id,$shop['id']]);
    flash_set('success','Product deleted.');
    header('Location: inventory.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $sku = trim($_POST['sku'] ?? '');
        $barcode = trim($_POST['barcode'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock_qty'] ?? 0);
        $threshold = (int)($_POST['low_stock_threshold'] ?? 5);
        if ($name==='') {
            flash_set('error','Product name is required.');
            header('Location: inventory.php');
            exit;
        }
        $stmt = $pdo->prepare('INSERT INTO products(shop_id,sku,barcode,category,name,price,stock_qty,low_stock_threshold) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([$shop['id'],$sku,$barcode ?: null,$category ?: null,$name,$price,$stock,$threshold]);
        flash_set('success','Product added.');
        header('Location: inventory.php');
        exit;
    }
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $barcode = trim($_POST['barcode'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock_qty'] ?? 0);
        $threshold = (int)($_POST['low_stock_threshold'] ?? 5);
        $pdo->prepare('UPDATE products SET barcode=?, category=?, price=?, stock_qty=?, low_stock_threshold=? WHERE id=? AND shop_id=?')
            ->execute([$barcode ?: null,$category ?: null,$price,$stock,$threshold,$id,$shop['id']]);
        flash_set('success','Product updated.');
        header('Location: inventory.php');
        exit;
    }
}

$products = $pdo->prepare('SELECT * FROM products WHERE shop_id=? ORDER BY name');
$products->execute([$shop['id']]);
$products = $products->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center">
  <h3 class="mb-0">Inventory</h3>
  <a class="btn btn-outline-secondary" href="dashboard.php">Back</a>
</div>

<div class="card my-3">
  <div class="card-body">
    <h5>Add Product</h5>
    <form method="post" class="row g-3">
      <input type="hidden" name="action" value="add">
      <div class="col-md-2">
        <label class="form-label">SKU</label>
        <input name="sku" class="form-control" placeholder="Optional">
      </div>
      <div class="col-md-2">
        <label class="form-label">Barcode</label>
        <input name="barcode" class="form-control" placeholder="Optional">
      </div>
      <div class="col-md-2">
        <label class="form-label">Category</label>
        <input name="category" class="form-control" placeholder="e.g., Snacks">
      </div>
      <div class="col-md-4">
        <label class="form-label">Name *</label>
        <input name="name" class="form-control" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Price</label>
        <input name="price" type="number" step="0.01" class="form-control" value="0">
      </div>
      <div class="col-md-2">
        <label class="form-label">Stock</label>
        <input name="stock_qty" type="number" class="form-control" value="0">
      </div>
      <div class="col-md-2">
        <label class="form-label">Low-stock threshold</label>
        <input name="low_stock_threshold" type="number" class="form-control" value="5">
      </div>
      <div class="col-12">
        <button class="btn btn-primary" type="submit">Add</button>
      </div>
    </form>
  </div>
</div>

<div class="card"><div class="card-body">
  <div class="table-responsive">
    <table class="table table-striped align-middle mb-0">
  <thead>
    <tr>
      <th>SKU</th>
      <th>Barcode</th>
      <th>Category</th>
      <th>Name</th>
      <th style="width:120px">Price</th>
      <th style="width:120px">Stock</th>
      <th style="width:150px">Low-stock</th>
      <th style="width:140px">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($products as $p): $low = ((int)$p['stock_qty'] <= (int)$p['low_stock_threshold']); $fid='f'.(int)$p['id']; ?>
      <tr class="<?= $low ? 'table-warning' : '' ?>">
        <td><?= htmlspecialchars($p['sku'] ?? '') ?></td>
        <td><input form="<?= $fid ?>" name="barcode" class="form-control form-control-sm" value="<?= htmlspecialchars($p['barcode'] ?? '') ?>"></td>
        <td><input form="<?= $fid ?>" name="category" class="form-control form-control-sm" value="<?= htmlspecialchars($p['category'] ?? '') ?>"></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td><input form="<?= $fid ?>" name="price" type="number" step="0.01" class="form-control form-control-sm" value="<?= htmlspecialchars($p['price']) ?>"></td>
        <td><input form="<?= $fid ?>" name="stock_qty" type="number" class="form-control form-control-sm" value="<?= (int)$p['stock_qty'] ?>"></td>
        <td><input form="<?= $fid ?>" name="low_stock_threshold" type="number" class="form-control form-control-sm" value="<?= (int)$p['low_stock_threshold'] ?>"></td>
        <td class="d-flex gap-2">
          <form id="<?= $fid ?>" method="post" class="m-0">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          </form>
          <button class="btn btn-sm btn-success" type="submit" form="<?= $fid ?>">Save</button>
          <a class="btn btn-sm btn-outline-danger" href="inventory.php?delete=<?= (int)$p['id'] ?>" onclick="return confirm('Delete product?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
  </div>
</div></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
