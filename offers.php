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
// products for product-specific offers
$prods = $pdo->prepare('SELECT id,name FROM products WHERE shop_id=? ORDER BY name');
$prods->execute([$shop['id']]);
$prods = $prods->fetchAll();
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare('DELETE FROM offers WHERE id=? AND shop_id=?')->execute([$id,$shop['id']]);
    flash_set('success','Offer deleted.');
    header('Location: offers.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $discount = (float)($_POST['discount_percent'] ?? 0);
    $applies_to = $_POST['applies_to'] ?? 'ALL';
    $product_id = $applies_to==='PRODUCT' ? (int)($_POST['product_id'] ?? 0) : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    if ($title==='') {
        flash_set('error','Title is required.');
        header('Location: offers.php');
        exit;
    }
    $stmt = $pdo->prepare('INSERT INTO offers(shop_id,title,description,discount_percent,applies_to,product_id,is_active,start_date,end_date) VALUES (?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$shop['id'],$title,$description,$discount,$applies_to,$product_id,$is_active,$start_date?:null,$end_date?:null]);
    flash_set('success','Offer created.');
    header('Location: offers.php');
    exit;
}
$offers = $pdo->prepare('SELECT * FROM offers WHERE shop_id=? ORDER BY created_at DESC');
$offers->execute([$shop['id']]);
$offers = $offers->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center">
  <h3 class="mb-0">Offers & Deals</h3>
  <a class="btn btn-outline-secondary" href="dashboard.php">Back</a>
</div>

<div class="card my-3">
  <div class="card-body">
    <h5>Create Offer</h5>
    <form method="post" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Title *</label>
        <input name="title" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Discount %</label>
        <input name="discount_percent" type="number" step="0.01" class="form-control" value="0">
      </div>
      <div class="col-md-3">
        <label class="form-label">Scope</label>
        <select name="applies_to" class="form-select" onchange="document.getElementById('productWrap').style.display=(this.value==='PRODUCT'?'block':'none')">
          <option value="ALL">All products</option>
          <option value="PRODUCT">Specific product</option>
        </select>
      </div>
      <div class="col-md-6" id="productWrap" style="display:none">
        <label class="form-label">Product</label>
        <select name="product_id" class="form-select">
          <option value="0">Select product...</option>
          <?php foreach($prods as $p): ?>
            <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Start (optional)</label>
        <input name="start_date" type="date" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">End (optional)</label>
        <input name="end_date" type="date" class="form-control">
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
          <label class="form-check-label" for="is_active">Active</label>
        </div>
      </div>
      <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2"></textarea>
      </div>
      <div class="col-12">
        <button class="btn btn-primary" type="submit">Create</button>
      </div>
    </form>
  </div>
</div>

<table class="table table-striped align-middle">
  <thead>
    <tr><th>Title</th><th>Discount</th><th>Scope</th><th>Active</th><th>Window</th><th>Description</th><th class="text-end">Actions</th></tr>
  </thead>
  <tbody>
    <?php foreach ($offers as $o): ?>
      <tr>
        <td><?= htmlspecialchars($o['title']) ?></td>
        <td><?= htmlspecialchars($o['discount_percent']) ?>%</td>
        <td><?= htmlspecialchars($o['applies_to'] ?? 'ALL') ?><?= ($o['applies_to'] ?? '')==='PRODUCT' ? ' #'.(int)$o['product_id'] : '' ?></td>
        <td><?= ((int)($o['is_active'] ?? 1)) ? '<span class="badge badge-g360">Yes</span>' : '<span class="badge text-bg-secondary">No</span>' ?></td>
        <td><?= htmlspecialchars($o['start_date'] ?? '') ?><?= ($o['end_date']??'') ? ' → '.htmlspecialchars($o['end_date']) : '' ?></td>
        <td><?= htmlspecialchars($o['description']) ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-danger" href="offers.php?delete=<?= (int)$o['id'] ?>" onclick="return confirm('Delete offer?')">Delete</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
