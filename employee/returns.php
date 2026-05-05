<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/util.php';
require_login(['EMPLOYEE']);
require_perm('RETURNS');
$u=current_user();
$pdo=db();
if(!$u['shop_id']){ header('Location: create_shop.php'); exit; }
$shop=$pdo->prepare('SELECT * FROM shops WHERE id=?');
$shop->execute([$u['shop_id']]);
$shop=$shop->fetch();
if(!$shop || $shop['status']!=='VERIFIED'){ header('Location: dashboard.php'); exit; }

if($_SERVER['REQUEST_METHOD']==='POST'){
  $type=$_POST['type']??'POS';
  $ref_id=(int)($_POST['ref_id']??0);
  $reason=trim($_POST['reason']??'');
  if(!in_array($type,['POS','ORDER'],true) || $ref_id<=0){ flash_set('error','Invalid return request.'); header('Location: returns.php'); exit; }
  $pdo->beginTransaction();
  try{
    if($type==='POS'){
      $sale=$pdo->prepare('SELECT * FROM pos_sales WHERE id=? AND shop_id=?');
      $sale->execute([$ref_id,$shop['id']]);
      $sale=$sale->fetch();
      if(!$sale) throw new Exception('Sale not found');
      $items=$pdo->prepare('SELECT * FROM pos_items WHERE pos_sale_id=?');
      $items->execute([$ref_id]);
      foreach($items->fetchAll() as $it){
        $pdo->prepare('UPDATE products SET stock_qty = stock_qty + ? WHERE id=? AND shop_id=?')
          ->execute([(int)$it['qty'],(int)$it['product_id'],$shop['id']]);
      }
      $refund=(float)$sale['total_amount'];
    } else {
      $ord=$pdo->prepare('SELECT * FROM orders WHERE id=? AND shop_id=?');
      $ord->execute([$ref_id,$shop['id']]);
      $ord=$ord->fetch();
      if(!$ord) throw new Exception('Order not found');
      $items=$pdo->prepare('SELECT * FROM order_items WHERE order_id=?');
      $items->execute([$ref_id]);
      foreach($items->fetchAll() as $it){
        $pdo->prepare('UPDATE products SET stock_qty = stock_qty + ? WHERE id=? AND shop_id=?')
          ->execute([(int)$it['qty'],(int)$it['product_id'],$shop['id']]);
      }
      $pdo->prepare("UPDATE orders SET delivery_status='RETURNED' WHERE id=? AND shop_id=?")->execute([$ref_id,$shop['id']]);
      $pdo->prepare("INSERT INTO order_events(order_id,status,note,created_by_user_id) VALUES (?,?,?,?)")
        ->execute([$ref_id,'RETURNED','Return processed',$u['id']]);
      $refund=(float)$ord['total_amount'];
    }
    $pdo->prepare('INSERT INTO returns(shop_id,type,ref_id,created_by_user_id,reason,refund_amount) VALUES (?,?,?,?,?,?)')
      ->execute([$shop['id'],$type,$ref_id,$u['id'],$reason,$refund]);
    $pdo->commit();
    flash_set('success','Return/refund processed.');
  } catch(Throwable $e){
    $pdo->rollBack();
    flash_set('error','Failed to process return/refund.');
  }
  header('Location: returns.php');
  exit;
}

$recent=$pdo->prepare('SELECT * FROM returns WHERE shop_id=? ORDER BY created_at DESC LIMIT 25');
$recent->execute([$shop['id']]);
$recent=$recent->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center">
  <div>
    <h3 class="mb-1">Returns & Refunds</h3>
    <div class="text-muted">Restore stock and record refunds for POS sales or delivery orders</div>
  </div>
  <a class="btn btn-outline-secondary" href="dashboard.php">Back</a>
</div>

<div class="card my-3"><div class="card-body">
  <h5 class="mb-3">Process Return</h5>
  <form method="post" class="row g-2">
    <div class="col-md-3">
      <label class="form-label">Type</label>
      <select name="type" class="form-select">
        <option value="POS">POS Sale</option>
        <option value="ORDER">Customer Order</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">ID</label>
      <input name="ref_id" type="number" min="1" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Reason (optional)</label>
      <input name="reason" class="form-control" placeholder="Damaged item / customer request / ...">
    </div>
    <div class="col-12">
      <button class="btn btn-primary" type="submit" onclick="return confirm('This will restore stock. Continue?')">Process</button>
    </div>
  </form>
</div></div>

<h5 class="mt-4">Recent Returns</h5>
<div class="table-responsive">
<table class="table table-striped align-middle">
  <thead><tr><th>Type</th><th>Ref</th><th class="text-end">Refund</th><th>Reason</th><th>Time</th></tr></thead>
  <tbody>
    <?php foreach($recent as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['type']) ?></td>
        <td>#<?= (int)$r['ref_id'] ?></td>
        <td class="text-end"><?= number_format((float)$r['refund_amount'],2) ?></td>
        <td><?= htmlspecialchars($r['reason'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
