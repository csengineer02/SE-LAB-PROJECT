<!DOCTYPE html>
<html>
<head>
    <title>Product Search</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#eaf7ef">

<div class="container py-4">

    <h3 class="mb-4">
        Search Results for:
        "{{ $search }}"
    </h3>

    @if(count($products) == 0)

        <div class="alert alert-warning">

            No products found.

        </div>

    @else

        <div class="row">

            @foreach($products as $product)

            <div class="col-md-4 mb-3">

                <div class="card h-100">

                    <div class="card-body">

                        <h5>
                            {{ $product->name }}
                        </h5>

                        <p class="text-success fw-bold">

                            ৳{{ $product->selling_price }}

                        </p>

                        <p>

                            Shop:
                            {{ $product->shop_name }}

                        </p>

                        <p>

                            Stock:
                            {{ $product->current_stock }}

                        </p>

                        <a
                            href="/customer/shop/{{ $product->shop_id }}"
                            class="btn btn-success">

                            Visit Shop

                        </a>

                    </div>

                </div>

            </div>

            @endforeach

        </div>

    @endif

</div>

</body>
</html>