<?php
// Global search endpoint for topbar search.
require_once __DIR__ . '/includes/header.php';

require_login();

$pdo = db();
$u = current_user();
$role = $u['role'] ?? '';
$q = trim($_GET['q'] ?? '');

$title = 'Search';

$results = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    if ($role === 'customer') {
        $st = $pdo->prepare("SELECT id, name, type, address FROM shop WHERE status='verified' AND (name LIKE ? OR address LIKE ? OR outlet_name LIKE ?) ORDER BY id DESC LIMIT 30");
        $st->execute([$like,$like,$like]);
        $results = $st->fetchAll();
    } elseif ($role === 'shop_owner' || $role === 'employee') {
        // Determine shop id
        if ($role === 'shop_owner') {
            $st = $pdo->prepare('SELECT id FROM shop WHERE shopownerid=? ORDER BY id DESC LIMIT 1');
            $st->execute([(int)$u['id']]);
            $shop = $st->fetch();
            $shopId = (int)($shop['id'] ?? 0);
        } else {
            $st = $pdo->prepare('SELECT shopid FROM employee WHERE id=? LIMIT 1');
            $st->execute([(int)$u['id']]);
            $emp = $st->fetch();
            $shopId = (int)($emp['shopid'] ?? 0);
        }

        if ($shopId > 0) {
            $st = $pdo->prepare("SELECT id, name, sku, current_stock FROM product WHERE shopid=? AND (name LIKE ? OR sku LIKE ?) ORDER BY id DESC LIMIT 50");
            $st->execute([$shopId, $like, $like]);
            $results = $st->fetchAll();
        }
    }
}
?>

<div class="card">
  <div class="h1"><?= h(t('search')) ?></div>
  <form class="form" method="get" action="<?= BASE_URL ?>/search.php" style="max-width:640px">
    <div>
      <label><?= h(t('search')) ?></label>
      <input name="q" value="<?= h($q) ?>" placeholder="<?= h(t('search')) ?>..." />
    </div>
    <button class="btn" type="submit"><?= h(t('search')) ?></button>
  </form>
</div>

<?php if ($q === ''): ?>
  <div class="card"><div class="muted">Type something to search.</div></div>
<?php else: ?>
  <div class="card">
    <h3>Results</h3>
    <?php if (!$results): ?>
      <div class="muted">No results found.</div>
    <?php else: ?>
      <table class="table">
        <thead>
          <?php if (($u['role'] ?? '') === 'customer'): ?>
            <tr><th>Shop</th><th>Type</th><th>Address</th><th></th></tr>
          <?php else: ?>
            <tr><th>Product</th><th>SKU</th><th>Stock</th><th></th></tr>
          <?php endif; ?>
        </thead>
        <tbody>
        <?php foreach ($results as $r): ?>
          <?php if (($u['role'] ?? '') === 'customer'): ?>
            <tr>
              <td><?= h($r['name'] ?? '') ?></td>
              <td><?= h($r['type'] ?? '') ?></td>
              <td><?= h($r['address'] ?? '') ?></td>
              <td><a class="btn secondary" href="<?= BASE_URL ?>/customer/shop.php?shop_id=<?= (int)$r['id'] ?>">Open</a></td>
            </tr>
          <?php else: ?>
            <tr>
              <td><?= h($r['name'] ?? '') ?></td>
              <td><?= h($r['sku'] ?? '') ?></td>
              <td><?= (int)($r['current_stock'] ?? 0) ?></td>
              <td><a class="btn secondary" href="<?= BASE_URL ?>/shop_owner/inventory.php">Go</a></td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
