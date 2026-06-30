@extends('layouts.admin')

@section('title', 'Promotions')

@section('content')

    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-slate-500 text-sm">{{ $promotions->total() }} promotions total</p>
        </div>
        <a href="{{ route('admin.promotions.create') }}"
            class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New Promotion
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Promotion</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Discount</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Users</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Period</th>
                    <th class="text-right px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($promotions as $promotion)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if ($promotion->image)
                                    <img src="{{ Storage::url($promotion->image) }}" alt="{{ $promotion->title }}"
                                        class="w-10 h-10 rounded-lg object-cover border border-slate-200 shrink-0" />
                                @else
                                    <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-medium text-slate-800">{{ $promotion->title }}</p>
                                    <p class="text-slate-400 text-xs">{{ Str::limit($promotion->description, 50) }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if ($promotion->discount_type === 'percentage')
                                <span class="font-semibold text-slate-700">{{ $promotion->discount_value }}%</span>
                            @else
                                <span class="font-semibold text-slate-700">${{ number_format($promotion->discount_value, 2) }}</span>
                            @endif
                            @if ($promotion->coupon_code)
                                <p class="text-xs text-slate-400 mt-0.5">Code: <span class="font-mono text-brand-600">{{ $promotion->coupon_code }}</span></p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span @class([
                                'text-xs font-medium px-2.5 py-1 rounded-full',
                                'bg-emerald-50 text-emerald-700' => $promotion->is_active,
                                'bg-red-50 text-red-700' => !$promotion->is_active,
                            ])>
                                {{ $promotion->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-600">{{ $promotion->users_count }} user{{ $promotion->users_count !== 1 ? 's' : '' }}</span>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500">
                            @if ($promotion->starts_at)
                                {{ \Carbon\Carbon::parse($promotion->starts_at)->format('M d, Y') }}
                            @else
                                Any
                            @endif
                            &rarr;
                            @if ($promotion->ends_at)
                                {{ \Carbon\Carbon::parse($promotion->ends_at)->format('M d, Y') }}
                            @else
                                Forever
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.promotions.edit', $promotion) }}"
                                    class="text-xs font-medium text-brand-600 hover:text-brand-800 px-3 py-1.5 rounded-lg hover:bg-brand-50 transition-colors">
                                    Edit
                                </a>
                                <button type="button"
                                    onclick="openDeleteModal('{{ route('admin.promotions.destroy', $promotion) }}', '{{ $promotion->title }}')"
                                    class="text-xs font-medium text-red-600 hover:text-red-800 px-3 py-1.5 rounded-lg hover:bg-red-50 transition-colors">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                            </svg>
                            <p class="text-slate-400 text-sm">No promotions yet.</p>
                            <a href="{{ route('admin.promotions.create') }}"
                                class="text-brand-500 text-sm hover:underline mt-1 inline-block">
                                Create your first promotion
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($promotions->hasPages())
        <div class="mt-5">
            {{ $promotions->links() }}
        </div>
    @endif

    {{-- Delete Modal --}}
    <div id="delete-modal" class="modal-overlay" onclick="closeDeleteModal()">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-icon">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="modal-title">Delete Promotion?</h3>
            <p class="modal-desc">
                You are about to delete
                <span id="delete-name" class="font-semibold text-slate-700"></span>.
                <br>This will also unassign it from all users. This action cannot be undone.
            </p>
            <div class="modal-actions">
                <button onclick="closeDeleteModal()" class="btn-cancel">Cancel</button>
                <form id="delete-form" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openDeleteModal(url, name) {
            document.getElementById('delete-name').textContent = name;
            document.getElementById('delete-form').action = url;
            document.getElementById('delete-modal').classList.add('open');
        }
        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.remove('open');
        }
    </script>

@endsection
