@extends('layouts.admin')

@section('title', 'Customers')


@section('content')

<div class="flex items-center justify-between mb-6">
    <p class="text-slate-500 text-sm">{{ $users->total() }} registered customers</p>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">#</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Name</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Email</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Orders</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Joined</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($users as $user)
            <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-4 text-slate-400">{{ $user->id }}</td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-xs font-bold shrink-0">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <span class="font-medium text-slate-800">{{ $user->name }}</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-slate-500">{{ $user->email }}</td>
                <td class="px-6 py-4 text-slate-600">{{ $user->orders_count ?? $user->orders->count() }}</td>
                <td class="px-6 py-4 text-slate-400 text-xs">{{ $user->created_at->format('d M Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-16 text-center">
                    <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-slate-400 text-sm">No customers yet.</p>
                    <p class="text-slate-300 text-xs mt-1">Customers will appear here once they register.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
<div class="mt-5">
    {{ $users->links() }}
</div>
@endif

@endsection