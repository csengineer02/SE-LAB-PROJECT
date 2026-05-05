<?php
$title = 'Grocer 360';
require_once __DIR__ . '/includes/public_header.php';
?>

<section class="app-hero mb-4">
  <div class="row align-items-center g-4">
    <div class="col-lg-7">
      <div class="app-chip mb-3"><i class="bi bi-geo-alt"></i> ERP + local grocery marketplace</div>
      <h1 class="display-6 fw-bold mb-2"><?= h(t('welcome')) ?></h1>
      <p class="text-muted mb-0"><?= h(t('hero_subtitle')) ?></p>
    </div>
    <div class="col-lg-5">
      <div class="p-4 rounded-4 bg-white border" style="box-shadow: var(--shadow-sm);">
        <div class="d-flex align-items-center gap-3">
          <div class="app-avatar"><i class="bi bi-basket2"></i></div>
          <div>
            <div class="fw-bold"><?= h(t('get_started')) ?></div>
            <div class="text-muted small"><?= h(t('choose_role')) ?></div>
          </div>
        </div>
        <div class="d-flex gap-2 mt-3">
          <a class="btn btn-success w-50" href="<?= BASE_URL ?>/signup.php"><?= h(t('signup')) ?></a>
          <a class="btn btn-outline-success w-50" href="<?= BASE_URL ?>/login.php"><?= h(t('login')) ?></a>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="row g-3">
  <div class="col-md-6 col-lg-3">
    <div class="app-role-card p-4 h-100">
      <div class="d-flex align-items-center gap-2 mb-2">
        <div class="app-avatar" style="width:44px;height:44px"><i class="bi bi-shop"></i></div>
        <h3 class="h5 fw-bold mb-0">Shop Owner</h3>
      </div>
      <p class="text-muted mb-3">Create a shop, get verified by admin, manage inventory, staff, orders & POS.</p>
      <a class="btn btn-success" href="<?= BASE_URL ?>/signup.php?role=shop_owner"><i class="bi bi-person-plus me-1"></i> Sign up as Shop Owner</a>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="app-role-card p-4 h-100">
      <div class="d-flex align-items-center gap-2 mb-2">
        <div class="app-avatar" style="width:44px;height:44px"><i class="bi bi-people"></i></div>
        <h3 class="h5 fw-bold mb-0">Employee</h3>
      </div>
      <p class="text-muted mb-3">Join a shop, get approved by owner, manage inventory and take orders/billing.</p>
      <a class="btn btn-success" href="<?= BASE_URL ?>/signup.php?role=employee"><i class="bi bi-person-plus me-1"></i> Sign up as Employee</a>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="app-role-card p-4 h-100">
      <div class="d-flex align-items-center gap-2 mb-2">
        <div class="app-avatar" style="width:44px;height:44px"><i class="bi bi-bag-heart"></i></div>
        <h3 class="h5 fw-bold mb-0">Customer</h3>
      </div>
      <p class="text-muted mb-3">Browse nearby verified shops, add to cart, checkout, earn reward points.</p>
      <a class="btn btn-success" href="<?= BASE_URL ?>/signup.php?role=customer"><i class="bi bi-person-plus me-1"></i> Sign up as Customer</a>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="app-role-card p-4 h-100">
      <div class="d-flex align-items-center gap-2 mb-2">
        <div class="app-avatar" style="width:44px;height:44px"><i class="bi bi-truck"></i></div>
        <h3 class="h5 fw-bold mb-0">Delivery Rider</h3>
      </div>
      <p class="text-muted mb-3">View assigned deliveries, update status (picked up / in transit / delivered), upload proof.</p>
      <div class="d-grid gap-2">
        <a class="btn btn-success" href="<?= BASE_URL ?>/delivery_rider/signup.php"><i class="bi bi-person-plus me-1"></i> Rider Sign up</a>
        <a class="btn btn-outline-success" href="<?= BASE_URL ?>/delivery_rider/login.php"><i class="bi bi-box-arrow-in-right me-1"></i> Rider Login</a>
      </div>
    </div>
  </div>
</div>

<div class="d-flex flex-wrap gap-2 mt-4">
  <a class="btn btn-outline-success" href="<?= BASE_URL ?>/login.php"><i class="bi bi-box-arrow-in-right me-1"></i> Already have an account? Login</a>
  <a class="btn btn-outline-success" href="<?= BASE_URL ?>/admin/login.php"><i class="bi bi-shield-lock me-1"></i> Admin Login</a>
</div>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
