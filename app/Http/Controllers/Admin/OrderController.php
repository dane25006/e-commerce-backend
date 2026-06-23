<?php


// ─────────────────────────────────────────────────────────────
// FILE: app/Http/Controllers/Admin/OrderController.php
// ─────────────────────────────────────────────────────────────
namespace App\Http\Controllers\Admin;
 
use App\Http\Controllers\Controller;
use App\Models\Order;
 
class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user')
            ->withCount('items')
            ->latest()
            ->paginate(20);
        return view('admin.orders.index', compact('orders'));
    }
 
    public function show(Order $order)
    {
        $order->load('items.product', 'user');
        return view('admin.orders.show', compact('order'));
    }
}
 
