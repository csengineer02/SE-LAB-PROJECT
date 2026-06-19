<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Dashboard - Grocer 360</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root { --primary: #16a34a; }
  body { background: #eaf7ef; font-family: Inter, system-ui, sans-serif; }
  .topnav { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 50; }
  .brand span.g { color: var(--primary); font-weight: 800; }
  .brand span.n { font-weight: 800; }
  .card { border-radius: 18px; border: 1px solid #e5e7eb; box-shadow: 0 6px 18px rgba(15,23,42,.07); background: #fff; }
  .stat-card { border-radius: 16px; padding: 20px; border: 1px solid #e5e7eb; background: #fff; }
  .stat-icon { width: 48px; height: 48px; border-radius: 14px; background: rgba(34,197,94,.14); display: grid; place-items: center; font-size: 22px; color: var(--primary); }
  .btn-primary { background: var(--primary); border-color: var(--primary); }
  .status-badge { padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
  .status-pending   { background: rgba(245,158,11,.15); color: #b45309; }
  .status-completed { background: rgba(34,197,94,.15); color: #16a34a; }
  .status-cancelled { background: rgba(220,38,38,.12); color: #dc2626; }
</style>
</head>
<body>

<nav class="topnav">
  <a href="/" class="text-decoration-none brand"><span class="g">Grocer</span> <span class="n">360</span></a>
  <div class="d-flex align-items-center gap-3">
    <a href="/customer/shops" class="btn btn-outline-success btn-sm"><i class="bi bi-shop me-1"></i>Shops</a>
    <a href="/customer/cart" class="btn btn-outline-success btn-sm position-relative">
      <i class="bi bi-cart3"></i>
      @if($cartCount > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:10px">{{ $cartCount }}</span>
      @endif
    </a>
    <span class="text-muted small">Hi, {{ session('user_name') }}</span>
    <form method="POST" action="/logout" class="mb-0">@csrf
      <button class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i></button>
    </form>
  </div>
</nav>

<div class="container py-4">

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
      <div class="stat-card">
        <div class="stat-icon mb-2"><i class="bi bi-receipt"></i></div>
        <div class="h4 fw-bold mb-0">{{ count($recentOrders) }}</div>
        <div class="text-muted small">Recent Orders</div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="stat-card">
        <div class="stat-icon mb-2"><i class="bi bi-cart3"></i></div>
        <div class="h4 fw-bold mb-0">{{ $cartCount }}</div>
        <div class="text-muted small">Cart Items</div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="stat-card">
        <div class="stat-icon mb-2"><i class="bi bi-star"></i></div>
        <div class="h4 fw-bold mb-0">{{ $rewardPoints }}</div>
        <div class="text-muted small">Reward Points</div>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="card p-4 mb-4">
    <h6 class="fw-bold mb-3">Quick Actions</h6>
    <div class="d-flex flex-wrap gap-2">
      <a href="/customer/shops"  class="btn btn-primary btn-sm"><i class="bi bi-shop me-1"></i>Browse Shops</a>
      <a href="/customer/cart"   class="btn btn-outline-success btn-sm"><i class="bi bi-cart3 me-1"></i>My Cart</a>
      <a href="/customer/orders" class="btn btn-outline-success btn-sm"><i class="bi bi-receipt me-1"></i>My Orders</a>
    </div>
  </div>

  @if(isset($ads) && count($ads) > 0)

<div class="card p-3 mb-4">

    <h5 class="fw-bold mb-3">
        Featured Advertisements
    </h5>

    <div class="row">

        @foreach($ads as $ad)

        <div class="col-md-6 mb-3">

            <div class="border rounded overflow-hidden">

                <img
                    src="{{ asset('uploads/advertisements/'.$ad->banner) }}"
                    class="img-fluid w-100"
                    style="height:250px;object-fit:cover;">

                <div class="p-3">

                    <h6 class="fw-bold">
                        {{ $ad->title }}
                    </h6>

                    @if($ad->description)

                    <small class="text-muted">
                        {{ $ad->description }}
                    </small>

                    @endif

                </div>

            </div>

        </div>

        @endforeach

    </div>

</div>

@endif
<!-- AI Recommendations -->

@if(isset($recommendedProducts) && count($recommendedProducts) > 0)

<div class="card mb-4">

    <div class="p-3 border-bottom">
        <h6 class="fw-bold mb-0">
            🤖 Recommended For You
        </h6>
    </div>

    <div class="card-body">

        <div class="row">

            @foreach($recommendedProducts as $product)

            <div class="col-md-4 mb-3">

                <div class="border rounded p-3 h-100">

                    <h6 class="fw-bold">
                        {{ $product->name }}
                    </h6>

                    <div class="text-success fw-bold mb-2">
                        ৳{{ number_format($product->selling_price,2) }}
                    </div>

                    <small class="text-muted">
                        Stock:
                        {{ $product->current_stock }}
                    </small>

                </div>

            </div>

            @endforeach

        </div>

    </div>

</div>

@endif
  <!-- Recent Orders -->
  <div class="card">
    <div class="p-3 border-bottom"><h6 class="fw-bold mb-0">Recent Orders</h6></div>
    @if(count($recentOrders) == 0)
      <div class="p-4 text-muted">No orders yet. <a href="/customer/shops">Browse shops</a> to start shopping!</div>
    @else
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th>Order #</th><th>Status</th><th>Amount</th><th>Payment</th></tr></thead>
        <tbody>
          @foreach($recentOrders as $order)
          <tr>
            <td class="fw-semibold">#{{ $order->order_id }}</td>
            <td><span class="status-badge status-{{ $order->status }}">{{ $order->status }}</span></td>
            <td>৳{{ number_format($order->total_amount ?? 0, 2) }}</td>
            <td class="text-muted small">{{ $order->payment_method ?? '—' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>

</div>
</body>
</html>