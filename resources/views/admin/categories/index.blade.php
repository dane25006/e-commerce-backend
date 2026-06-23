@extends('layouts.admin')

@section('title', 'Categories')

@section('content')

    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-slate-500 text-sm">{{ $categories->total() }} categories total</p>
        </div>
        <a href="{{ route('admin.categories.create') }}"
            class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New Category
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">#</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Name</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Slug</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Products
                    </th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Created
                    </th>
                    <th class="text-right px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($categories as $category)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 text-slate-400">{{ $category->id }}</td>
                        <td class="px-6 py-4 font-medium text-slate-800">{{ $category->name }}</td>
                        <td class="px-6 py-4">
                            <code
                                class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-xs">{{ $category->slug }}</code>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $category->products_count ?? $category->products->count() }}
                        </td>
                        <td class="px-6 py-4 text-slate-400 text-xs">{{ $category->created_at->format('d M Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.categories.edit', $category) }}"
                                    class="text-xs font-medium text-brand-600 hover:text-brand-800 px-3 py-1.5 rounded-lg hover:bg-brand-50 transition-colors">
                                    Edit
                                </a>
                                {{-- Remove the <form> wrapper, just keep the button --}}
                                <button type="button"
                                    onclick="openDeleteModal('{{ route('admin.categories.destroy', $category->id) }}', '{{ addslashes($category->name) }}')"
                                    class="btn-danger-soft">
                                    Delete
                                </button>
                            </div>  
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <p class="text-slate-400 text-sm">No categories yet.</p>
                            <a href="{{ route('admin.categories.create') }}"
                                class="text-brand-500 text-sm hover:underline mt-1 inline-block">
                                Create your first category
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($categories->hasPages())
        <div class="mt-5">
            {{ $categories->links() }}
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
            <h3 class="modal-title">Delete Category?</h3>
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
