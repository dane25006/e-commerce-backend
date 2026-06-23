@extends('layouts.admin')

@section('title', 'New Category')

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
        <h2 class="text-slate-800 font-semibold text-base mb-6">Create Category</h2>

        <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Category Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text" id="name" name="name"
                    value="{{ old('name') }}"
                    required
                    placeholder="e.g. Electronics"
                    class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                           @error('name') border-red-400 bg-red-50 @else border-slate-300 @enderror"
                />
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    Create Category
                </button>
                <a href="{{ route('admin.categories.index') }}"
                   class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection