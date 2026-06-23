@extends('layouts.admin')

@section('title', 'Edit Category')

@section('content')

<div class="max-w-xl mx-auto">

    <a href="{{ route('admin.categories.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-6 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Categories
    </a>

    <div class="bg-white rounded-xl border border-slate-200 p-8">
        <h2 class="text-slate-800 font-semibold text-base mb-1">Edit Category</h2>
        <p class="text-slate-400 text-xs mb-6">ID #{{ $category->id }}</p>

        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Category Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text" id="name" name="name"
                    value="{{ old('name', $category->name) }}"
                    required
                    class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                           @error('name') border-red-400 bg-red-50 @else border-slate-300 @enderror"
                />
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Current Slug</label>
                <code class="block bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-sm text-slate-500">
                    {{ $category->slug }}
                </code>
                <p class="text-slate-400 text-xs mt-1">Slug updates automatically when you save.</p>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    Save Changes
                </button>
                <a href="{{ route('admin.categories.index') }}"
                   class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    {{-- Danger Zone --}}
    <div class="bg-white rounded-xl border border-red-200 p-6 mt-5">
        <h3 class="text-red-600 font-semibold text-sm mb-1">Danger Zone</h3>
        <p class="text-slate-500 text-xs mb-4">Deleting this category will also delete all products inside it.</p>
        <button type="button"
            onclick="openDeleteModal('{{ route('admin.categories.destroy', $category) }}', '{{ $category->name }}')"
            class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
            Delete Category
        </button>
    </div>

</div>

{{-- Delete Modal --}}
<div id="delete-modal" class="modal-overlay" onclick="closeDeleteModal()">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="modal-icon">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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