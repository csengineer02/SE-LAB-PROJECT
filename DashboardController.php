<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Session::get('user_id');

        // Get shop
        $shop = DB::table('shop')
            ->where('owner_user_id', $userId)
            ->orWhere('shopownerid', $userId)
            ->first();

        $stats = [
            'products'  => 0,
            'orders'    => 0,
            'employees' => 0,
            'low_stock' => 0,
        ];

        if ($shop) {
            $stats['products']  = DB::table('product')->where('shopid', $shop->id)->count();
            $stats['employees'] = DB::table('employee')->where('shopid', $shop->id)->count();
            $stats['low_stock'] = DB::table('product')
                ->where('shopid', $shop->id)
                ->whereRaw('current_stock <= min_stock_level')
                ->count();
        }
        $lowStockProducts = collect();
$expiringProducts = collect();

if ($shop) {

    $lowStockProducts = DB::table('product')
        ->where('shopid', $shop->id)
        ->whereRaw('current_stock <= min_stock_level')
        ->get();

    $expiringProducts = DB::table('product')
        ->where('shopid', $shop->id)
        ->whereNotNull('expirydate')
        ->whereDate(
            'expirydate',
            '<=',
            now()->addDays(30)
        )
        ->get();
}

return view(
    'owner.dashboard',
    compact(
        'shop',
        'stats',
        'lowStockProducts',
        'expiringProducts'
    )
);    }

    public function inventory()
    {
        $userId   = Session::get('user_id');
        $shop     = DB::table('shop')->where('owner_user_id', $userId)->orWhere('shopownerid', $userId)->first();
        $products = $shop ? DB::table('product')->where('shopid', $shop->id)->orderBy('name')->get() : collect();
        return view('owner.inventory', compact('shop', 'products'));
    }

    public function orders()
    {
        $userId = Session::get('user_id');
        $shop   = DB::table('shop')->where('owner_user_id', $userId)->orWhere('shopownerid', $userId)->first();
        $orders = collect();
        if ($shop) {
            $orders = DB::table('orders as o')
                ->leftJoin('payment as p', 'p.id', '=', 'o.paymentid')
                ->leftJoin('customer as c', 'c.id', '=', 'o.customerid')
                ->select('o.*', 'p.total_amount', 'p.payment_method', 'c.name as customer_name')
                ->orderBy('o.order_id', 'desc')
                ->limit(50)
                ->get();
        }
        return view('owner.orders', compact('shop', 'orders'));
    }

    public function reports()
    {
        $userId = Session::get('user_id');
        $shop   = DB::table('shop')->where('owner_user_id', $userId)->orWhere('shopownerid', $userId)->first();
        return view('owner.reports', compact('shop'));
    }

    public function employees()
    {
        $userId    = Session::get('user_id');
        $shop      = DB::table('shop')->where('owner_user_id', $userId)->orWhere('shopownerid', $userId)->first();
        $employees = $shop ? DB::table('employee')->where('shopid', $shop->id)->get() : collect();
        return view('owner.employees', compact('shop', 'employees'));
    }
    public function storeProduct(Request $request)
{
    $userId = Session::get('user_id');
    $shop   = DB::table('shop')
        ->where('owner_user_id', $userId)
        ->orWhere('shopownerid', $userId)
        ->first();

    if (!$shop || $shop->status !== 'verified') {
        return back()->with('error', 'Shop must be verified first.');
    }

    DB::table('product')->insert([
        'shopid'          => $shop->id,
        'name'            => trim($request->input('name')),
        'buying_price'    => $request->input('buying_price', 0),
        'selling_price'   => $request->input('selling_price', 0),
        'current_stock'   => $request->input('current_stock', 0),
        'unit'            => $request->input('unit', 'pcs'),
        'min_stock_level' => $request->input('min_stock_level', 5),
        'expirydate'      => $request->input('expirydate') ?: null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    return back()->with('success', 'Product added successfully!');
}

public function createEmployee()
{
    $userId = Session::get('user_id');

    $shop = DB::table('shop')
        ->where('owner_user_id', $userId)
        ->orWhere('shopownerid', $userId)
        ->first();

    return view('owner.add-employee', compact('shop'));
}

public function storeEmployee(Request $request)
{
    $request->validate([
        'name'     => 'required',
        'email'    => 'required|email|unique:users,email',
        'phone'    => 'required',
        'password' => 'required|min:4',
    ]);

    $userId = Session::get('user_id');

    $shop = DB::table('shop')
        ->where('owner_user_id', $userId)
        ->orWhere('shopownerid', $userId)
        ->first();

    if (!$shop) {
        return back()->with('error', 'Shop not found.');
    }

    // users table insert
    DB::table('users')->insert([
        'name'          => $request->name,
        'email'         => $request->email,
        'phone'         => $request->phone,
        'shop_id'       => $shop->id,
        'role'          => 'EMPLOYEE',
        'password_hash' => Hash::make($request->password),
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);

    // employee table insert
    DB::table('employee')->insert([
        'shopid'     => $shop->id,
        'name'       => $request->name,
        'email'      => $request->email,
        'phone'      => $request->phone,
        'password'   => bcrypt($request->password),
        'roleid'     => 2,
        'active'     => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return redirect('/owner/employees')
        ->with('success', 'Employee added successfully!');
}

public function subscription()
{
    $plans = DB::table('subscription_plans')
        ->where('is_active',1)
        ->get();

    return view(
        'owner.subscriptions',
        compact('plans')
    );
}
public function storeSubscription(Request $request)
{
    $userId = Session::get('user_id');

    // Active subscription check
    $activeSubscription = DB::table('shop_subscriptions')
        ->where('shopownerid', $userId)
        ->where('is_active', 1)
        ->first();

    if ($activeSubscription) {
        return back()->with(
            'error',
            'You already have an active subscription.'
        );
    }

    // Pending request check
    $pendingSubscription = DB::table('shop_subscriptions')
        ->where('shopownerid', $userId)
        ->where('is_active', 0)
        ->first();

    if ($pendingSubscription) {
        return back()->with(
            'error',
            'Your subscription request is already pending approval.'
        );
    }

    DB::table('shop_subscriptions')->insert([
        'shopownerid' => $userId,
        'plan_id' => $request->plan_id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'is_active' => 0,
        'requested_at' => now()
    ]);

    return back()->with(
        'success',
        'Subscription request sent successfully.'
    );
}
}