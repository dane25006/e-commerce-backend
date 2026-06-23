@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-5 mb-8">

        @php
            $cards = [
                [
                    'label' => 'Products',
                    'value' => $stats['products'],
                    'color' => 'bg-violet-50 text-violet-600',
                    'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10',
                ],
                [
                    'label' => 'Categories',
                    'value' => $stats['categories'],
                    'color' => 'bg-blue-50 text-blue-600',
                    'icon' =>
                        'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
                ],
                [
                    'label' => 'Orders',
                    'value' => $stats['orders'],
                    'color' => 'bg-amber-50 text-amber-600',
                    'icon' =>
                        'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                ],
                [
                    'label' => 'Customers',
                    'value' => $stats['users'],
                    'color' => 'bg-emerald-50 text-emerald-600',
                    'icon' =>
                        'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                ],
                [
                    'label' => 'Revenue',
                    'value' => '$' . number_format($stats['revenue'], 2),
                    'color' => 'bg-rose-50 text-rose-600',
                    'icon' =>
                        'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4">
                <div class="w-11 h-11 {{ $card['color'] }} rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800">{{ $card['value'] }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $card['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

        {{-- Top Selling Products Bar Chart --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-700 mb-1 text-sm">Top Selling Products</h3>
            <p class="text-xs text-slate-400 mb-4">By units sold — all time</p>
            <canvas id="topProductsChart" height="220"></canvas>
        </div>

        {{-- New Customers Line Chart --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-700 mb-1 text-sm">New Customers</h3>
            <p class="text-xs text-slate-400 mb-4">Last 7 days</p>
            <canvas id="newCustomersChart" height="220"></canvas>
        </div>

    </div>

    {{-- Quick Actions + Recent Orders --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-700 mb-4 text-sm">Quick Actions</h3>
            <div class="space-y-2">
                <a href="{{ route('admin.categories.create') }}" class="quick-action-link">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New Category
                </a>
                <a href="{{ route('admin.products.create') }}" class="quick-action-link">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New Product
                </a>
                <a href="{{ route('admin.orders.index') }}" class="quick-action-link">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    View All Orders
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-700 mb-4 text-sm">Recent Orders</h3>
            @if (isset($recentOrders) && $recentOrders->count())
                <div class="space-y-3">
                    @foreach ($recentOrders as $order)
                        <div class="flex items-center justify-between text-sm">
                            <div>
                                <p class="font-medium text-slate-700">#{{ $order->id }} — {{ $order->user->name }}</p>
                                <p class="text-xs text-slate-400">{{ $order->created_at->diffForHumans() }}</p>
                            </div>
                            <span @class([
                                'text-xs px-2 py-0.5 rounded-full font-medium',
                                'bg-amber-100 text-amber-700' => $order->status === 'pending',
                                'bg-emerald-100 text-emerald-700' => $order->status === 'completed',
                                'bg-red-100 text-red-700' => $order->status === 'cancelled',
                            ])>{{ ucfirst($order->status) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-slate-400 text-sm">No orders yet.</p>
            @endif
        </div>

    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // ── Top Selling Products ──────────────────────────────
        new Chart(document.getElementById('topProductsChart'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($topProducts->pluck('name')) !!},
                datasets: [{
                    label: 'Units Sold',
                    data: {!! json_encode($topProducts->pluck('total_sold')) !!},
                    backgroundColor: [
                        '#8b5cf6', '#6d28d9', '#a78bfa', '#7c3aed', '#c4b5fd', '#5b21b6'
                    ],
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.parsed.y} units sold`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: '#94a3b8',
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: '#f1f5f9'
                        },
                        border: {
                            display: false
                        },
                    },
                    x: {
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                size: 11
                            },
                            maxRotation: 30,
                        },
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        },
                    }
                }
            }
        });

        // ── New Customers Last 7 Days ─────────────────────────
        new Chart(document.getElementById('newCustomersChart'), {
            type: 'line',
            data: {
                labels: {!! json_encode($days->keys()) !!},
                datasets: [{
                    label: 'New Customers',
                    data: {!! json_encode($days->values()) !!},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.parsed.y} new customer(s)`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: '#94a3b8',
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: '#f1f5f9'
                        },
                        border: {
                            display: false
                        },
                    },
                    x: {
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        },
                    }
                }
            }
        });
    </script>
@endpush
