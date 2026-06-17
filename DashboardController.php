<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    public function index()
    {
        // Pending shop owners
        $pendingOwners = DB::table('users')
            ->where('role', 'OWNER')
            ->where('employee_status', 'PENDING')
            ->get();

        // Pending shops
        $pendingShops = DB::table('shop as s')
            ->leftJoin('users as u', 'u.id', '=', 's.owner_user_id')
            ->where('s.status', 'pending')
            ->select('s.*', 'u.name as owner_name', 'u.email as owner_email')
            ->orderBy('s.created_at', 'desc')
            ->get();

        // Stats
        $stats = [
            'pending'  => DB::table('shop')->where('status', 'pending')->count()
                        + DB::table('users')->where('role','OWNER')->where('employee_status','PENDING')->count(),
            'verified' => DB::table('shop')->where('status', 'verified')->count(),
            'rejected' => DB::table('shop')->where('status', 'rejected')->count(),
        ];
        $pendingSubscriptions =
DB::table('shop_subscriptions as ss')
->join(
    'subscription_plans as sp',
    'sp.id',
    '=',
    'ss.plan_id'
)
->join(
    'users as u',
    'u.id',
    '=',
    'ss.shopownerid'
)
->where('ss.is_active',0)
->select(
    'ss.*',
    'sp.plan_name',
    'sp.price',
    'u.name as owner_name'
)
->get();

return view(
    'admin.dashboard',
    compact(
        'pendingOwners',
        'pendingShops',
        'stats',
        'pendingSubscriptions'
    )
);
        
    }

    public function approveOwner(Request $request)
    {
        $userId = (int) $request->input('user_id');
        $action = $request->input('action');

        if ($userId > 0 && in_array($action, ['approved', 'rejected'])) {
            $status = $action === 'approved' ? 'APPROVED' : 'REJECTED';
            DB::table('users')->where('id', $userId)->update(['employee_status' => $status]);
            Session::flash('success', 'Owner account ' . $action . '.');
        }

        return redirect('/admin/dashboard');
    }

    public function verifyShop(Request $request)
    {
        $shopId = (int) $request->input('shop_id');
        $action = $request->input('action');

        if ($shopId > 0 && in_array($action, ['verified', 'rejected'])) {
            DB::table('shop')->where('id', $shopId)->update(['status' => $action]);
            Session::flash('success', 'Shop ' . $action . '.');
        }

        return redirect('/admin/dashboard');
    }

    public function reports()
{
    $totalShops    = DB::table('shop')->where('status', 'verified')->count();
    $totalUsers    = DB::table('users')->count();
    $totalOrders   = DB::table('orders')->count();
    $totalProducts = DB::table('product')->count();

    $usersByRole = DB::table('users')
        ->select('role', DB::raw('count(*) as count'))
        ->groupBy('role')
        ->get();

    $shopsByStatus = DB::table('shop')
        ->select('status', DB::raw('count(*) as count'))
        ->groupBy('status')
        ->get();

    $recentShops = DB::table('shop as s')
        ->leftJoin('users as u', 'u.id', '=', 's.owner_user_id')
        ->select('s.*', 'u.name as owner_name')
        ->orderBy('s.created_at', 'desc')
        ->limit(10)
        ->get();

    $recentUsers = DB::table('users')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

    return view('admin.reports', compact(
        'totalShops', 'totalUsers', 'totalOrders', 'totalProducts',
        'usersByRole', 'shopsByStatus', 'recentShops', 'recentUsers'
    ));
}
public function subscriptions()
{
    $subscriptions = DB::table('shop_subscriptions as ss')
        ->join('users as u','u.id','=','ss.shopownerid')
        ->join('subscription_plans as sp','sp.id','=','ss.plan_id')
        ->select(
            'ss.*',
            'u.name as owner_name',
            'sp.plan_name',
            'sp.price'
        )
        ->orderBy('ss.id','desc')
        ->get();

    return view(
        'admin.subscriptions',
        compact('subscriptions')
    );
}

public function approveSubscription($id)
{
    DB::table('shop_subscriptions')
        ->where('id',$id)
        ->update([
            'is_active' => 1,
            'approved_at' => now()
        ]);

    return back()
        ->with(
            'success',
            'Subscription approved.'
        );
}
public function rejectSubscription($id)
{
    DB::table('shop_subscriptions')
        ->where('id', $id)
        ->delete();

    return back()->with(
        'success',
        'Subscription rejected.'
    );
}
}