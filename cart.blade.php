<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Grocer 360</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #16a34a;
        }

        body {
            background: #eaf7ef;
            font-family: Inter, system-ui, sans-serif;
        }

        .navbar-brand span.g {
            color: var(--primary);
            font-weight: 800;
        }

        .navbar-brand span.n {
            font-weight: 800;
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: #12803a;
            border-color: #12803a;
        }

        .btn-outline-success {
            border-color: var(--primary);
            color: var(--primary);
        }

        .card {
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 25px rgba(15,23,42,.08);
        }

        .cart-title {
            font-weight: 800;
        }

        .qty-box {
            min-width: 45px;
            text-align: center;
            font-weight: 700;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ route('customer.dashboard') }}">
            <span class="g">Grocer</span> <span class="n">360</span>
        </a>

        <div class="ms-auto d-flex gap-2 align-items-center">
            <a href="{{ route('customer.shops') }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-shop me-1"></i> Shops
            </a>

            <a href="{{ route('customer.orders') }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-receipt me-1"></i> My Orders
            </a>

            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</nav>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="cart-title mb-1">My Cart</h2>
            <p class="text-muted mb-0">Review your selected grocery items before checkout.</p>
        </div>

        <a href="{{ route('customer.shops') }}" class="btn btn-outline-success">
            <i class="bi bi-arrow-left me-1"></i> Continue Shopping
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body p-4">

            @if(isset($cartItems) && count($cartItems) > 0)

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th class="text-end">Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($cartItems as $item)
                                <tr>
                                    <td>
                                        <div class="fw-bold">
                                            {{ $item->p_name ?? $item->name ?? 'Product' }}
                                        </div>
                                    </td>

                                    <td>
                                        ৳{{ number_format($item->price ?? $item->selling_price ?? 0, 2) }}
                                    </td>

                                    <td>

<div class="d-flex align-items-center gap-2">

    <form method="POST"
          action="{{ route('customer.cart.decrease',$item->id) }}">
        @csrf

        <button class="btn btn-sm btn-outline-danger">
            -
        </button>
    </form>

    <span class="fw-bold">
        {{ $item->quantity }}
    </span>

    <form method="POST"
          action="{{ route('customer.cart.increase',$item->id) }}">
        @csrf

        <button class="btn btn-sm btn-outline-success">
            +
        </button>
    </form>

</div>

</td>

                                    <td class="text-end fw-bold">
                                        ৳{{ number_format(($item->price ?? $item->selling_price ?? 0) * ($item->quantity ?? 1), 2) }}
                                    </td>
                                    <td>

<form method="POST"
      action="{{ route('customer.cart.remove',$item->id) }}">

    @csrf

    <button class="btn btn-sm btn-danger">
        <i class="bi bi-trash"></i>
        Remove
    </button>

</form>

</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Total</h5>
                    <h4 class="mb-0 text-success fw-bold">
                        ৳{{ number_format($total ?? 0, 2) }}
                    </h4>
                </div>
                <hr>




                <form method="POST"
      action="{{ route('customer.checkout') }}">

    @csrf

    <input type="hidden"
           name="delivery_type"
           id="deliveryType"
           value="standard">

    <h5 class="mb-3 mt-4">
        Payment Method
    </h5>

    <div class="form-check mb-2">
        <input class="form-check-input"
               type="radio"
               name="payment_method"
               value="Cash on Delivery"
               checked>

        <label class="form-check-label">
            💵 Cash On Delivery
        </label>
    </div>

    <div class="form-check mb-2">
        <input class="form-check-input"
               type="radio"
               name="payment_method"
               value="Bkash">

        <label class="form-check-label">
            bKash
        </label>
    </div>

    <div class="form-check mb-2">
        <input class="form-check-input"
               type="radio"
               name="payment_method"
               value="Nagad">

        <label class="form-check-label">
            Nagad
        </label>
    </div>

    <div class="form-check mb-3">
        <input class="form-check-input"
               type="radio"
               name="payment_method"
               value="Card">

        <label class="form-check-label">
            💳 Debit / Credit Card
        </label>
    </div>

    <div class="mb-3">
        <label class="form-label">
            bKash / Nagad Number
        </label>

        <input type="text"
               name="bkash_nagad_number"
               class="form-control"
               placeholder="01XXXXXXXXX">
    </div>

    <div class="mb-3">
        <label class="form-label">
            Transaction ID
        </label>

        <input type="text"
               name="transaction_id"
               class="form-control"
               placeholder="TXN123456">
    </div>

    <div class="text-end">
        <button type="submit"
                class="btn btn-primary px-4">

            <i class="bi bi-credit-card me-1"></i>
            Checkout

        </button>
    </div>

</form>

    

    


                </div>

            @else

                <div class="text-center py-5">
                    <div style="font-size: 54px;">🛒</div>
                    <h4 class="fw-bold mt-3">Your cart is empty</h4>
                    <p class="text-muted">Browse shops and add products to your cart.</p>

                    <a href="{{ route('customer.shops') }}" class="btn btn-primary mt-2">
                        <i class="bi bi-shop me-1"></i> Browse Shops
                    </a>
                </div>

            @endif

        </div>
    </div>

</div>
<script>




</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const numberField =
        document.querySelector(
            'input[name="bkash_nagad_number"]'
        );

    const txnField =
        document.querySelector(
            'input[name="transaction_id"]'
        );

    const paymentMethods =
        document.querySelectorAll(
            'input[name="payment_method"]'
        );

    function toggleFields()
    {
        let selected =
            document.querySelector(
                'input[name="payment_method"]:checked'
            ).value;

        if(selected === 'Bkash' ||
           selected === 'Nagad')
        {
            numberField.disabled = false;
            txnField.disabled = false;
        }
        else
        {
            numberField.disabled = true;
            txnField.disabled = true;

            numberField.value = '';
            txnField.value = '';
        }
    }

    paymentMethods.forEach(radio => {
        radio.addEventListener(
            'change',
            toggleFields
        );
    });

    toggleFields();
});
</script>
</body>
</html>