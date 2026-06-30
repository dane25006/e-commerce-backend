@extends('layouts.admin')

@section('title', 'New Promotion')

@section('content')

<div class="max-w-2xl mx-auto">

    <a href="{{ route('admin.promotions.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-6 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Promotions
    </a>

    <div class="bg-white rounded-xl border border-slate-200 p-8">
        <h2 class="text-slate-800 font-semibold text-base mb-6">Create Promotion</h2>

        <form method="POST" action="{{ route('admin.promotions.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Title --}}
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Title <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required
                    placeholder="e.g. Summer Sale"
                    class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                           @error('title') border-red-400 bg-red-50 @else border-slate-300 @enderror" />
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 mb-1.5">Description</label>
                <textarea id="description" name="description" rows="3"
                    placeholder="Promotion description…"
                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm resize-none
                           focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                           @error('description') border-red-400 bg-red-50 @enderror"
                >{{ old('description') }}</textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Discount Type + Value --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="discount_type" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Discount Type <span class="text-red-500">*</span>
                    </label>
                    <select id="discount_type" name="discount_type" required
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                               @error('discount_type') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                        <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                        <option value="fixed" {{ old('discount_type') == 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                    </select>
                    @error('discount_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="discount_value" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Discount Value <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="discount_value" name="discount_value"
                        value="{{ old('discount_value') }}" required min="0" step="0.01"
                        placeholder="0.00"
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                               @error('discount_value') border-red-400 bg-red-50 @else border-slate-300 @enderror" />
                    @error('discount_value') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Coupon Code + Product --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="coupon_code" class="block text-sm font-medium text-slate-700 mb-1.5">Coupon Code</label>
                    <input type="text" id="coupon_code" name="coupon_code" value="{{ old('coupon_code') }}"
                        placeholder="e.g. SUMMER20"
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                               @error('coupon_code') border-red-400 bg-red-50 @else border-slate-300 @enderror" />
                    @error('coupon_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="product_id" class="block text-sm font-medium text-slate-700 mb-1.5">Product (optional)</label>
                    <select id="product_id" name="product_id"
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                               @error('product_id') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                        <option value="">— All Products —</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('product_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Period --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="starts_at" class="block text-sm font-medium text-slate-700 mb-1.5">Start Date</label>
                    <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at') }}"
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                               @error('starts_at') border-red-400 bg-red-50 @else border-slate-300 @enderror" />
                    @error('starts_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="ends_at" class="block text-sm font-medium text-slate-700 mb-1.5">End Date</label>
                    <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at') }}"
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                               @error('ends_at') border-red-400 bg-red-50 @else border-slate-300 @enderror" />
                    @error('ends_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Assign to Users --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Assign to Customers</label>
                <div class="border border-slate-300 rounded-lg max-h-48 overflow-y-auto p-2 space-y-1">
                    @forelse($users as $user)
                        <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                {{ in_array($user->id, old('user_ids', [])) ? 'checked' : '' }}
                                class="w-4 h-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500" />
                            <span class="text-sm text-slate-700">{{ $user->name }}</span>
                            <span class="text-xs text-slate-400 ml-auto">{{ $user->email }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-slate-400 text-center py-4">No customers found.</p>
                    @endforelse
                </div>
                @error('user_ids') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-slate-400 mt-1">Select customers who will receive this promotion.</p>
            </div>

            {{-- Image + Active --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Image</label>
                    <label for="image"
                        class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-brand-400 hover:bg-brand-50/30 transition-colors">
                        <div id="upload-placeholder" class="text-center">
                            <svg class="w-6 h-6 text-slate-300 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-slate-400 text-xs">Click to upload</p>
                        </div>
                        <img id="image-preview" src="" alt="Preview" class="hidden h-20 object-contain rounded-lg"/>
                        <input type="file" id="image" name="image" accept="image/*" class="hidden" onchange="previewImage(this)"/>
                    </label>
                    @error('image') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-start pt-7">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                            class="w-4 h-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500" />
                        <span class="text-sm font-medium text-slate-700">Active</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    Create Promotion
                </button>
                <a href="{{ route('admin.promotions.index') }}"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('upload-placeholder').classList.add('hidden');
            const preview = document.getElementById('image-preview');
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

@endsection
