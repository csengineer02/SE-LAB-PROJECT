<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Grocer 360</title>

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

        .page-title {
            font-weight: 800;
        }

        .status-badge {
            border-radius: 999px;
            padding: 6px 12px;
            font-size: .78rem;
            font-weight: 700;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-default {
            background: #e5e7eb;
            color: #374151;
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

            <a href="{{ route('customer.cart') }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-cart me-1"></i> My Cart
            </a>

            <a href="{{ route('logout') }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title mb-1">My Orders</h2>
            <p class="text-muted mb-0">Track your grocery order history.</p>
        </div>

        <a href="{{ route('customer.shops') }}" class="btn btn-primary">
            <i class="bi bi-shop me-1"></i> Browse Shops
        </a>
    </div>

    <div class="card">
        <div class="card-body p-4">

            @if(isset($orders) && count($orders) > 0)

                <div class="table-responsive">
                    <table class="table align-middle">

    <thead>
    <tr>
        <th>Order ID</th>
        <th>Total</th>
        <th>Delivery Type</th>
        <th>Delivery Charge</th>
        <th>ETA</th>
        <th>Payment Method</th>
        <th>Payment Status</th>
        <th>Order Status</th>
        <th>Paid At</th>
    </tr>
    </thead>

    <tbody>

    @foreach($orders as $order)

    @php

        $paymentStatus = strtolower(
            $order->payment_status ?? 'pending'
        );

        if ($paymentStatus === 'paid') {

            $statusClass = 'status-paid';

        } elseif (
            $paymentStatus === 'cancelled' ||
            $paymentStatus === 'failed'
        ) {

            $statusClass = 'status-cancelled';

        } elseif ($paymentStatus === 'pending') {

            $statusClass = 'status-pending';

        } else {

            $statusClass = 'status-default';

        }

    @endphp

    <tr>

        <td class="fw-bold">
            #{{ $order->order_id }}
        </td>

        <td>
            ৳{{ number_format($order->total_amount ?? 0, 2) }}
        </td>

        <td>

            @if(($order->delivery_type ?? '') == 'Fast')

                <span class="badge bg-danger">
                    ⚡ Fast
                </span>

            @else

                <span class="badge bg-success">
                    🚚 Standard
                </span>

            @endif

        </td>

        <td>
            ৳{{ $order->delivery_charge ?? 0 }}
        </td>

        <td>
            {{ $order->estimated_delivery_time ?? 0 }}
            min
        </td>

        <td>
            {{ strtoupper($order->payment_method ?? 'N/A') }}
        </td>

        <td>

            <span class="status-badge {{ $statusClass }}">
                {{ strtoupper($order->payment_status ?? 'PENDING') }}
            </span>

        </td>

        <td>

    @if(($order->status ?? '') == 'completed')

        <span class="badge bg-success">
            Delivered
        </span>

    @elseif(($order->status ?? '') == 'delivering')

        <span class="badge bg-primary">
            Out For Delivery
        </span>

    @elseif(($order->status ?? '') == 'picked_up')

        <span class="badge bg-info">
            Picked Up
        </span>

    @elseif(($order->status ?? '') == 'assigned')

        <span class="badge bg-warning text-dark">
            Rider Assigned
        </span>

    @elseif(($order->status ?? '') == 'cancelled')

        <span class="badge bg-danger">
            Cancelled
        </span>

    @else

        <span class="badge bg-secondary">
            Pending
        </span>

    @endif

    @if(($order->status ?? '') == 'pending')

        <form method="POST"
              action="{{ route('customer.order.cancel',$order->order_id) }}"
              class="mt-2">

            @csrf

            <button type="submit"
                    class="btn btn-sm btn-danger">

                Cancel Order

            </button>

        </form>

    @endif

</td>

        <td>
            {{ $order->paid_at ?? 'N/A' }}
        </td>

    </tr>

    @endforeach

    </tbody>

</table>
                </div>

            @else

                <div class="text-center py-5">
                    <div style="font-size: 54px;">📦</div>
                    <h4 class="fw-bold mt-3">No orders yet</h4>
                    <p class="text-muted">You have not placed any grocery orders yet.</p>

                    <a href="{{ route('customer.shops') }}" class="btn btn-primary mt-2">
                        <i class="bi bi-shop me-1"></i> Start Shopping
                    </a>
                </div>

            @endif

        </div>
    </div>

</div>

</body>
</html>