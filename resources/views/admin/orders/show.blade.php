@extends('layouts.admin')

@section('title', 'Order #'.$order->id)

@section('content')

<div class="max-w-3xl">

    <a href="{{ route('admin.orders.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-6 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Orders
    </a>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-slate-800 font-bold text-xl">Order #{{ $order->id }}</h2>
            <p class="text-slate-400 text-sm mt-0.5">Placed {{ $order->created_at->format('d M Y, H:i') }}</p>
        </div>
        <span @class([
            'text-sm font-semibold px-3 py-1.5 rounded-full',
            'bg-amber-100 text-amber-700'      => $order->status === 'pending',
            'bg-emerald-100 text-emerald-700'  => $order->status === 'completed',
            'bg-red-100 text-red-700'          => $order->status === 'cancelled',
        ])>
            {{ ucfirst($order->status) }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">

        {{-- Customer Info --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 col-span-1 min-w-0">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Customer</p>
            <p class="font-semibold text-slate-800 truncate" title="{{ $order->user->name ?? '' }}">{{ $order->user->name ?? '—' }}</p>
            <p class="text-slate-500 text-sm mt-1 truncate" title="{{ $order->user->email ?? '' }}">{{ $order->user->email ?? '—' }}</p>
            <p class="text-slate-400 text-xs mt-3 truncate">Customer since {{ $order->user->created_at->format('M Y') ?? '' }}</p>
        </div>

        {{-- Order Summary --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 col-span-2">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Summary</p>
            <div class="flex justify-between text-sm text-slate-600 mb-2">
                <span>Items total</span>
                <span>{{ $order->items->count() }} item(s)</span>
            </div>
            <div class="flex justify-between text-sm font-bold text-slate-800 pt-2 border-t border-slate-100">
                <span>Total Amount</span>
                <span>${{ number_format($order->total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Order Items --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <p class="font-semibold text-slate-700 text-sm">Order Items</p>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Product</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Unit Price</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Qty</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($order->items as $item)
                <tr>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @if($item->product && $item->product->image)
                                <img src="{{ Storage::url($item->product->image) }}"
                                     class="w-10 h-10 object-cover rounded-lg border border-slate-200 shrink-0"/>
                            @else
                                <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                    </svg>
                                </div>
                            @endif
                            <span class="font-medium text-slate-800">
                                {{ $item->product->name ?? 'Product deleted' }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-600">${{ number_format($item->price, 2) }}</td>
                    <td class="px-6 py-4 text-slate-600">× {{ $item->quantity }}</td>
                    <td class="px-6 py-4 text-right font-semibold text-slate-800">
                        ${{ number_format($item->price * $item->quantity, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-slate-50 border-t border-slate-200">
                    <td colspan="3" class="px-6 py-4 text-right font-semibold text-slate-700">Total</td>
                    <td class="px-6 py-4 text-right font-bold text-slate-900 text-base">
                        ${{ number_format($order->total, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>
@endsection