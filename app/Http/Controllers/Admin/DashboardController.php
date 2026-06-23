<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Product, Category, Order, User};
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'products'   => Product::count(),
            'categories' => Category::count(),
            'orders'     => Order::count(),
            'users'      => User::where('role', 'customer')->count(),
            'revenue'    => Order::where('status', 'completed')->sum('total_amount'),
        ];

        $recentOrders = Order::with('user')
            ->latest()
            ->take(5)
            ->get();

        // Top 6 selling products
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(6)
            ->get();

        // New customers last 7 days
        $newCustomers = User::where('role', 'customer')
            ->where('created_at', '>=', now()->subDays(6))
            ->get()
            ->groupBy(fn($u) => $u->created_at->format('D'))
            ->map(fn($group) => $group->count());

        // Fill missing days with 0
        $days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('D');
            $days[$day] = $newCustomers[$day] ?? 0;
        }

        return view('admin.dashboard', compact('stats', 'recentOrders', 'topProducts', 'days'));
    }
}