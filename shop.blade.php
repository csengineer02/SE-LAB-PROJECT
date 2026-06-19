<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $shop->name }} - Grocer 360</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root { --primary: #16a34a; }
  body { background: #eaf7ef; font-family: Inter, system-ui, sans-serif; }
  .topnav { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 50; }
  .brand span.g { color: var(--primary); font-weight: 800; }
  .brand span.n { font-weight: 800; }
  .shop-hero { background: linear-gradient(135deg, rgba(34,197,94,.2), rgba(255,255,255,.7)); border-radius: 20px; padding: 30px; border: 1px solid #e5e7eb; margin-bottom: 24px; }
  .product-card { border-radius: 16px; border: 1px solid #e5e7eb; background: #fff; box-shadow: 0 4px 12px rgba(15,23,42,.06); padding: 16px; height: 100%; }
.product-img {
  height: 180px;
  background: #f8fafc;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  margin-bottom: 12px;
  font-size: 36px;
}  .btn-primary { background: var(--primary); border-color: var(--primary); }
  .btn-primary:hover { background: #12803a; border-color: #12803a; }
  .btn-outline-success { border-color: var(--primary); color: var(--primary); }
  .price { color: var(--primary); font-weight: 800; font-size: 1.1rem; }
</style>
</head>
<body>

<nav class="topnav">
  <a href="/" class="text-decoration-none brand">
    <span class="g">Grocer</span> <span class="n">360</span>
  </a>

  <div class="d-flex align-items-center gap-3">
    <a href="/customer/shops" class="btn btn-outline-success btn-sm">
      <i class="bi bi-arrow-left me-1"></i>All Shops
    </a>

    <a href="/customer/cart" class="btn btn-outline-success btn-sm">
      <i class="bi bi-cart3 me-1"></i>Cart
    </a>

    <form method="POST" action="/logout" class="mb-0">
      @csrf
      <button type="submit" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-box-arrow-right"></i>
      </button>
    </form>
  </div>
</nav>

<div class="container py-4">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Shop Hero -->
  <div class="shop-hero">
    <div class="row align-items-center">
      <div class="col-auto" style="font-size:56px">
        @if($shop->type == 'Pharmacy') 💊
        @elseif($shop->type == 'Grocery') 🥬
        @elseif($shop->type == 'Super Shop') 🛒
        @else 🏪
        @endif
      </div>

      <div class="col">
        <h3 class="fw-bold mb-1">{{ $shop->name }}</h3>

        @if($shop->outlet_name)
          <div class="text-muted mb-1">{{ $shop->outlet_name }}</div>
        @endif

        <div class="d-flex flex-wrap gap-3 text-muted small">
          @if($shop->type)
            <span><i class="bi bi-tag me-1"></i>{{ $shop->type }}</span>
          @endif

          @if($shop->area)
            <span><i class="bi bi-geo-alt me-1"></i>{{ $shop->area }}</span>
          @endif

          <span><i class="bi bi-map me-1"></i>{{ $shop->address }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Products -->
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="fw-bold mb-0">
      Products <span class="text-muted small fw-normal">({{ count($products) }} items)</span>
    </h5>
  </div>

  @if(count($products) == 0)
    <div class="text-center py-5">
      <div style="font-size:48px">📦</div>
      <h6 class="fw-bold mt-3">No products yet</h6>
      <p class="text-muted small">This shop hasn't added any products yet.</p>
    </div>
  @else
    <div class="row g-3">
      @foreach($products as $product)
        <div class="col-6 col-md-4 col-lg-3">
          <div class="product-card">

            <div class="product-img p-0 overflow-hidden">

@if($product->image_url)

    <img
        src="{{ $product->image_url }}"
        alt="{{ $product->name }}"
        style="
            width:100%;
            height:100%;
            object-fit:cover;
            border-radius:12px;
        ">

@else

    @php
        $emoji = '🛒';
        $name = strtolower($product->name);

        if(str_contains($name,'tomato')) $emoji = '🍅';
        elseif(str_contains($name,'potato')) $emoji = '🥔';
        elseif(str_contains($name,'onion')) $emoji = '🧅';
        elseif(str_contains($name,'carrot')) $emoji = '🥕';
        elseif(str_contains($name,'mango')) $emoji = '🥭';
        elseif(str_contains($name,'banana')) $emoji = '🍌';
        elseif(str_contains($name,'apple')) $emoji = '🍎';
        elseif(str_contains($name,'milk')) $emoji = '🥛';
        elseif(str_contains($name,'egg')) $emoji = '🥚';
        elseif(str_contains($name,'chicken')) $emoji = '🍗';
        elseif(str_contains($name,'fish')) $emoji = '🐟';
        elseif(str_contains($name,'rice')) $emoji = '🍚';
        elseif(str_contains($name,'bread')) $emoji = '🍞';
    @endphp

    {{ $emoji }}

@endif

</div>

            <h6 class="fw-bold mb-1" style="font-size:14px">
              {{ $product->name }}
            </h6>

            @if($product->unit)
              <div class="text-muted small mb-2">{{ $product->unit }}</div>
            @endif

            <div class="d-flex align-items-center justify-content-between mt-2">
              <span class="price">৳{{ number_format($product->selling_price, 2) }}</span>
              <span class="text-muted small">Stock: {{ $product->current_stock }}</span>
            </div>

            <form method="POST" action="{{ route('customer.cart.add') }}" class="mt-2">
              @csrf

              <input type="hidden" name="product_id" value="{{ $product->id }}">
              <input type="hidden" name="shop_id" value="{{ $shop->id }}">
              <input type="hidden" name="quantity" value="1">

              <button type="submit" class="btn btn-primary btn-sm w-100">
                <i class="bi bi-cart-plus me-1"></i>Add to Cart
              </button>
            </form>

          </div>
        </div>
      @endforeach
    </div>
  @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<hr>

<h5>Rate This Shop</h5>

<form method="POST"
      action="{{ route('customer.shop.review') }}">

    @csrf

    <input type="hidden"
           name="shop_id"
           value="{{ $shop->id }}">

    <select name="rating"
            class="form-select mb-3">

        <option value="5">⭐⭐⭐⭐⭐ 5</option>
        <option value="4">⭐⭐⭐⭐ 4</option>
        <option value="3">⭐⭐⭐ 3</option>
        <option value="2">⭐⭐ 2</option>
        <option value="1">⭐ 1</option>

    </select>

    <textarea
        name="review"
        class="form-control mb-3"
        placeholder="Write review"></textarea>

    <button class="btn btn-success">
        Submit Review
    </button>

</form>
</body>
</html>