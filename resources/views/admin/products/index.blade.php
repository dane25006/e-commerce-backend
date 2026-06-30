@extends('layouts.admin')

@section('title', 'Products')


@section('content')

    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-slate-500 text-sm">{{ $products->total() }} products total</p>
        </div>
        <a href="{{ route('admin.products.create') }}"
            class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New Product
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Product
                    </th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Category
                    </th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Brand
                    </th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Gender
                    </th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Price
                    </th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Stock
                    </th>
                    <th class="text-right px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($products as $product)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if ($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                                        class="w-10 h-10 rounded-lg object-cover border border-slate-200 shrink-0" />
                                @else
                                    <div
                                        class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-medium text-slate-800">{{ $product->name }}</p>
                                    <p class="text-slate-400 text-xs">{{ Str::limit($product->description, 40) }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-blue-50 text-blue-700 text-xs font-medium px-2.5 py-1 rounded-full">
                                {{ $product->category->name ?? '—' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $product->brand ?? '—' }}</td>
                        <td class="px-6 py-4">
                            @if($product->gender)
                                <span class="text-xs font-medium px-2.5 py-1 rounded-full
                                    {{ $product->gender === 'Women' ? 'bg-pink-50 text-pink-700' : ($product->gender === 'Men' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700') }}">
                                    {{ $product->gender }}
                                </span>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-semibold text-slate-700">
                            @if($product->sale_price)
                                <span class="text-red-500">${{ number_format($product->sale_price, 2) }}</span>
                                <span class="text-xs text-slate-400 line-through ml-1">${{ number_format($product->price, 2) }}</span>
                            @else
                                ${{ number_format($product->price, 2) }}
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span @class([
                                'text-xs font-medium px-2.5 py-1 rounded-full',
                                'bg-emerald-50 text-emerald-700' => $product->stock > 10,
                                'bg-amber-50 text-amber-700' =>
                                    $product->stock > 0 && $product->stock <= 10,
                                'bg-red-50 text-red-700' => $product->stock === 0,
                            ])>
                                {{ $product->stock > 0 ? $product->stock . ' in stock' : 'Out of stock' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.products.edit', $product) }}"
                                    class="text-xs font-medium text-brand-600 hover:text-brand-800 px-3 py-1.5 rounded-lg hover:bg-brand-50 transition-colors">
                                    Edit
                                </a>
                                <button type="button"
                                    onclick="openDeleteModal('{{ route('admin.products.destroy', $product) }}', '{{ $product->name }}')"
                                    class="text-xs font-medium text-red-600 hover:text-red-800 px-3 py-1.5 rounded-lg hover:bg-red-50 transition-colors">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
                            </svg>
                            <p class="text-slate-400 text-sm">No products yet.</p>
                            <a href="{{ route('admin.products.create') }}"
                                class="text-brand-500 text-sm hover:underline mt-1 inline-block">
                                Add your first product
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($products->hasPages())
        <div class="mt-5">
            {{ $products->links() }}
        </div>
    @endif

    {{-- Delete Modal --}}
    <div id="delete-modal" class="modal-overlay" onclick="closeDeleteModal()">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-icon">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="modal-title">Delete Product?</h3>
            <p class="modal-desc">
                You are about to delete
                <span id="delete-name" class="font-semibold text-slate-700"></span>.
                <br>This action cannot be undone.
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
@endsection
