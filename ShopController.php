<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ShopController extends Controller
{
    public function create()
    {
        $userId = Session::get('user_id');
        $shop   = DB::table('shop')
            ->where('owner_user_id', $userId)
            ->orWhere('shopownerid', $userId)
            ->first();

        if ($shop) {
            return redirect('/owner/shop/verification');
        }

        return view('owner.create_shop');
    }

    public function store(Request $request)
    {
        $request->validate([
    'name' => 'required',
    'address' => 'required',
    'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

    'nid_pdf' => 'required|mimes:pdf|max:5120',
    'trade_license_pdf' => 'required|mimes:pdf|max:5120',
    'shop_document' => 'required|mimes:pdf|max:5120',
]);
        $userId  = Session::get('user_id');
        $name    = trim($request->input('name', ''));
        $type    = $request->input('type', 'Local Shop');
        $address = trim($request->input('address', ''));
        $area    = trim($request->input('area', ''));
        $outlet  = trim($request->input('outlet_name', ''));
        $tin     = trim($request->input('tin_number', ''));

        if (!$name || !$address) {
            return back()->with('error', 'Shop name and address are required.');
        }

        $nidPdf = null;
$tradeLicensePdf = null;
$shopDocument = null;

if ($request->hasFile('nid_pdf')) {
    $nidPdf = $request->file('nid_pdf')
        ->store('shop_documents', 'public');
}

if ($request->hasFile('trade_license_pdf')) {
    $tradeLicensePdf = $request->file('trade_license_pdf')
        ->store('shop_documents', 'public');
}

if ($request->hasFile('shop_document')) {
    $shopDocument = $request->file('shop_document')
        ->store('shop_documents', 'public');
}
$logo = null;

if ($request->hasFile('logo')) {

    $logo = time().'_'.$request->file('logo')->getClientOriginalName();

    $request->file('logo')->move(
        public_path('uploads/shop-logos'),
        $logo
    );
}

        $qr = bin2hex(random_bytes(8));

        DB::table('shop')->insert([
            'name'          => $name,
            'type'          => $type,
            'address'       => $address,
            'area'          => $area,
            'outlet_name'   => $outlet ?: null,
            'tin_number'    => $tin ?: null,
            'qr_code'       => $qr,
            'owner_user_id' => $userId,
            'shopownerid'   => $userId,
            'status'        => 'pending',
            'created_at'    => now(),
            'updated_at'    => now(),
            'logo' => $logo,
            'nid_pdf' => $nidPdf,
'trade_license_pdf' => $tradeLicensePdf,
'shop_document' => $shopDocument,
        ]);

        return redirect('/owner/shop/verification')
            ->with('success', 'Shop submitted for verification!');
    }

    public function verification()
    {
        $userId = Session::get('user_id');
        $shop   = DB::table('shop')
            ->where('owner_user_id', $userId)
            ->orWhere('shopownerid', $userId)
            ->first();

        return view('owner.verification', compact('shop'));
    }
}