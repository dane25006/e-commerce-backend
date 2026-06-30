@extends('layouts.admin')

@section('title', 'Orders')


@section('content')

<div class="flex items-center justify-between mb-6">
    <p class="text-slate-500 text-sm">{{ $orders->total() }} orders total</p>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Order</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Customer</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Items</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Total</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
                <th class="text-right px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Details</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($orders as $order)
            <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-4 font-mono text-slate-600 font-medium">#{{ $order->id }}</td>
                <td class="px-6 py-4">
                    <p class="font-medium text-slate-800">{{ $order->user->name ?? '—' }}</p>
                    <p class="text-slate-400 text-xs">{{ $order->user->email ?? '' }}</p>
                </td>
                <td class="px-6 py-4 text-slate-600">{{ $order->items_count ?? $order->items->count() }} items</td>
                <td class="px-6 py-4 font-semibold text-slate-700">${{ number_format($order->total, 2) }}</td>
                <td class="px-6 py-4">
                    <span @class([
                        'text-xs font-medium px-2.5 py-1 rounded-full',
                        'bg-amber-50 text-amber-700'      => $order->status === 'pending',
                        'bg-emerald-50 text-emerald-700'  => $order->status === 'completed',
                        'bg-red-50 text-red-700'          => $order->status === 'cancelled',
                    ])>
                        {{ ucfirst($order->status) }}
                    </span>
                </td>
                <td class="px-6 py-4 text-slate-400 text-xs">{{ $order->created_at->format('d M Y, H:i') }}</td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ route('admin.orders.show', $order) }}"
                       class="text-xs font-medium text-brand-600 hover:text-brand-800 px-3 py-1.5 rounded-lg hover:bg-brand-50 transition-colors">
                        View
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-16 text-center">
                    <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-slate-400 text-sm">No orders yet.</p>
                    <p class="text-slate-300 text-xs mt-1">Orders will appear here once customers check out.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($orders->hasPages())
<div class="mt-5">
    {{ $orders->links() }}
</div>
@endif

@endsection