<!-- resources/views/customer/shops.blade.php -->

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Browse Shops - Grocer 360</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root{
    --primary:#16a34a;
}

body{
    background:#eaf7ef;
    font-family:Inter,system-ui,sans-serif;
}

.topnav{
    background:#fff;
    border-bottom:1px solid #e5e7eb;
    padding:12px 24px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    position:sticky;
    top:0;
    z-index:50;
}

.brand span.g{
    color:var(--primary);
    font-weight:800;
}

.brand span.n{
    font-weight:800;
}

.shop-card{
    border-radius:18px;
    border:1px solid #e5e7eb;
    background:#fff;
    box-shadow:0 6px 18px rgba(15,23,42,.07);
    transition:.2s;
    overflow:hidden;
    height:100%;
}

.shop-card:hover{
    transform:translateY(-4px);
    box-shadow:0 14px 30px rgba(15,23,42,.12);
    border-color:rgba(22,163,74,.25);
}

.shop-banner{
    height:120px;
    background:linear-gradient(
        135deg,
        rgba(34,197,94,.20),
        rgba(255,255,255,.90)
    );
    display:flex;
    justify-content:center;
    align-items:center;
}

.shop-body{
    padding:16px;
}

.shop-type{
    font-size:11px;
    background:rgba(34,197,94,.14);
    color:#16a34a;
    border-radius:999px;
    padding:3px 10px;
    font-weight:600;
}

.search-card{
    border:none;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(15,23,42,.07);
}

.btn-primary{
    background:var(--primary);
    border-color:var(--primary);
}

.btn-primary:hover{
    background:#15803d;
    border-color:#15803d;
}
</style>

</head>
<body>

<nav class="topnav">

```
<a href="/" class="text-decoration-none brand">
    <span class="g">Grocer</span>
    <span class="n">360</span>
</a>

<div class="d-flex align-items-center gap-3">

    <a href="/customer/dashboard"
       class="btn btn-outline-success btn-sm">

        <i class="bi bi-speedometer2 me-1"></i>
        Dashboard

    </a>

    <a href="/customer/cart"
       class="btn btn-outline-success btn-sm">

        <i class="bi bi-cart3 me-1"></i>
        Cart

    </a>

    <span class="text-muted small">
        Hi, {{ session('user_name') }}
    </span>

    <form method="POST"
          action="/logout"
          class="mb-0">

        @csrf

        <button class="btn btn-outline-danger btn-sm">
            <i class="bi bi-box-arrow-right"></i>
        </button>

    </form>

</div>
```

</nav>

<div class="container py-4">

```
<!-- Search & Filter -->

<div class="row g-2">

    <!-- Search -->
    <div class="col-md-3">

        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search shop name..."
            value="{{ request('search') }}">

    </div>

    <!-- Type -->
    <div class="col-md-2">

        <select name="type" class="form-select">

            <option value="">All Shop Types</option>

            <option value="Grocery"
                {{ request('type') == 'Grocery' ? 'selected' : '' }}>
                Grocery
            </option>

            <option value="Super Shop"
                {{ request('type') == 'Super Shop' ? 'selected' : '' }}>
                Super Shop
            </option>

            <option value="Pharmacy"
                {{ request('type') == 'Pharmacy' ? 'selected' : '' }}>
                Pharmacy
            </option>

        </select>

    </div>

    <!-- Area -->
    <div class="col-md-2">

        <select name="area" class="form-select">

            <option value="">
                All Areas
            </option>

            @foreach($areas as $area)

                <option
                    value="{{ $area }}"
                    {{ request('area') == $area ? 'selected' : '' }}>

                    {{ $area }}

                </option>

            @endforeach

        </select>

    </div>

    <!-- Rating -->
    <div class="col-md-2">

        <select
            name="rating"
            class="form-select">

            <option value="">
                Any Rating
            </option>

            <option value="4">
                4★+
            </option>

            <option value="4.5">
                4.5★+
            </option>

        </select>

    </div>

    <!-- Delivery -->
    <div class="col-md-1">

        <select
            name="delivery"
            class="form-select">

            <option value="">
                🚚
            </option>

            <option value="fast">
                ⚡
            </option>

            <option value="cheap">
                ৳
            </option>

        </select>

    </div>

    <!-- Search -->
    <div class="col-md-1">

        <button
            class="btn btn-success w-100">

            <i class="bi bi-search"></i>

        </button>

    </div>

    <!-- Reset -->
    <div class="col-md-1">

        <a href="/customer/shops"
           class="btn btn-outline-secondary w-100">

            X

        </a>

    </div>

</div>
        </form>

    </div>

</div>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">

        <form method="GET"
              action="{{ route('customer.products.search') }}">

            <div class="row">

                <div class="col-md-10">

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search products across all shops..."
                        required>

                </div>

                <div class="col-md-2">

                    <button class="btn btn-success w-100">

                        <i class="bi bi-search"></i>
                        Search

                    </button>

                </div>

            </div>

        </form>

    </div>
</div>
<!-- Header -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h4 class="fw-bold mb-1">
            Browse Shops
        </h4>

        <div class="text-muted small">
            {{ count($shops) }} verified shops available
        </div>

    </div>

</div>

@if(count($shops) == 0)

    <div class="text-center py-5">

        <div style="font-size:60px">
            🏪
        </div>

        <h5 class="fw-bold mt-3">
            No shops available yet
        </h5>

        <p class="text-muted">
            Check back later when shops get verified by admin.
        </p>

        <a href="/customer/dashboard"
           class="btn btn-primary">

            Back to Dashboard

        </a>

    </div>

@else

    <div class="row g-3">

        @foreach($shops as $shop)

        <div class="col-md-6 col-lg-4">

            <div class="shop-card">

                <div class="shop-banner d-flex justify-content-center align-items-center">

    @if($shop->logo)

        <img
            src="{{ asset('uploads/shop-logos/'.$shop->logo) }}"
            alt="Shop Logo"
            style="
                width:90px;
                height:90px;
                border-radius:50%;
                object-fit:cover;
                border:3px solid #fff;
                box-shadow:0 2px 10px rgba(0,0,0,.15);
            ">

    @else

        @if($shop->type == 'Pharmacy')
            💊
        @elseif($shop->type == 'Grocery')
            🥬
        @elseif($shop->type == 'Super Shop')
            🛒
        @else
            🏪
        @endif

    @endif

</div>

                <div class="shop-body">

                    <div class="d-flex justify-content-between align-items-start mb-2">

                        <h6 class="fw-bold mb-0">
                            {{ $shop->name }}
                        </h6>

                        <span class="shop-type">
                            {{ $shop->type ?? 'Shop' }}
                        </span>

                    </div>
                     <div class="small text-warning mb-2">
    ⭐ {{ $shop->rating }}
</div>
@if($shop->category)

<div class="badge bg-success mb-2">

    {{ $shop->category }}

</div>

@endif

<div class="d-flex justify-content-between mb-3">

    <span class="badge bg-light text-dark">
        🚚 {{ $shop->standard_delivery_time }} min
    </span>

    <span class="badge bg-danger">
        ⚡ {{ $shop->fast_delivery_time }} min
    </span>

</div>

<div class="small text-muted mb-3">

    🚚 Standard:
    ৳{{ $shop->standard_delivery_charge }}

    <br>

    ⚡ Fast:
    ৳{{ $shop->fast_delivery_charge }}

</div>

                    @if($shop->area)

                    <div class="text-muted small mb-2">
                        <i class="bi bi-geo-alt me-1"></i>
                        {{ $shop->area }}
                    </div>

                    @endif

                    <div class="text-muted small mb-3">

                        <i class="bi bi-map me-1"></i>
                        {{ $shop->address }}

                    </div>

                    <a href="/customer/shop/{{ $shop->id }}"
                       class="btn btn-primary btn-sm w-100">

                        <i class="bi bi-shop me-1"></i>
                        Visit Shop

                    </a>

                </div>

            </div>

        </div>

        @endforeach

    </div>

@endif
```

</div>

</body>
</html>
