@extends('layouts.admin')

@section('title', 'Edit Promotion')

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
        <h2 class="text-slate-800 font-semibold text-base mb-1">Edit Promotion</h2>
        <p class="text-slate-400 text-xs mb-6">ID #{{ $promotion->id }}</p>

        <form method="POST" action="{{ route('admin.promotions.update', $promotion) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf @method('PUT')

            {{-- Title --}}
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Title <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title" value="{{ old('title', $promotion->title) }}" required
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
                           focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">{{ old('description', $promotion->description) }}</textarea>
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
                        <option value="percentage" {{ old('discount_type', $promotion->discount_type) == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                        <option value="fixed" {{ old('discount_type', $promotion->discount_type) == 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                    </select>
                    @error('discount_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="discount_value" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Discount Value <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="discount_value" name="discount_value"
                        value="{{ old('discount_value', $promotion->discount_value) }}" required min="0" step="0.01"
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
                    <input type="text" id="coupon_code" name="coupon_code" value="{{ old('coupon_code', $promotion->coupon_code) }}"
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
                            <option value="{{ $p->id }}" {{ old('product_id', $promotion->product_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('product_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Period --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="starts_at" class="block text-sm font-medium text-slate-700 mb-1.5">Start Date</label>
                    <input type="datetime-local" id="starts_at" name="starts_at"
                        value="{{ old('starts_at', $promotion->starts_at ? \Carbon\Carbon::parse($promotion->starts_at)->format('Y-m-d\TH:i') : '') }}"
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                               @error('starts_at') border-red-400 bg-red-50 @else border-slate-300 @enderror" />
                    @error('starts_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="ends_at" class="block text-sm font-medium text-slate-700 mb-1.5">End Date</label>
                    <input type="datetime-local" id="ends_at" name="ends_at"
                        value="{{ old('ends_at', $promotion->ends_at ? \Carbon\Carbon::parse($promotion->ends_at)->format('Y-m-d\TH:i') : '') }}"
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                               @error('ends_at') border-red-400 bg-red-50 @else border-slate-300 @enderror" />
                    @error('ends_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Assign to Users --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Assigned Customers</label>
                <div class="border border-slate-300 rounded-lg max-h-48 overflow-y-auto p-2 space-y-1">
                    @forelse($users as $user)
                        <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                {{ in_array($user->id, old('user_ids', $assignedUserIds)) ? 'checked' : '' }}
                                class="w-4 h-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500" />
                            <span class="text-sm text-slate-700">{{ $user->name }}</span>
                            <span class="text-xs text-slate-400 ml-auto">{{ $user->email }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-slate-400 text-center py-4">No customers found.</p>
                    @endforelse
                </div>
                @error('user_ids') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Image --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Image</label>
                @if ($promotion->image)
                    <div class="flex items-center gap-4 mb-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <img src="{{ Storage::url($promotion->image) }}" alt="{{ $promotion->title }}" id="image-preview"
                            class="w-16 h-16 object-cover rounded-lg border border-slate-200" />
                        <div>
                            <p class="text-xs font-medium text-slate-600">Current image</p>
                            <p class="text-xs text-slate-400 mt-0.5">Upload a new image below to replace it</p>
                        </div>
                    </div>
                @else
                    <img id="image-preview" src="" alt="Preview" class="hidden w-16 h-16 object-cover rounded-lg border border-slate-200 mb-3" />
                @endif
                <label for="image"
                    class="flex items-center justify-center w-full h-24 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-brand-400 hover:bg-brand-50/30 transition-colors">
                    <div class="text-center">
                        <p class="text-slate-400 text-sm">Click to upload a new image</p>
                        <p class="text-slate-300 text-xs mt-0.5">JPG, PNG, WEBP up to 2MB</p>
                    </div>
                    <input type="file" id="image" name="image" accept="image/*" class="hidden" onchange="previewImage(this)" />
                </label>
                @error('image') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Active --}}
            <div class="flex items-center gap-2">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                    {{ old('is_active', $promotion->is_active) ? 'checked' : '' }}
                    class="w-4 h-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500" />
                <label for="is_active" class="text-sm font-medium text-slate-700">Active</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    Save Changes
                </button>
                <a href="{{ route('admin.promotions.index') }}"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    {{-- Danger Zone --}}
    <div class="bg-white rounded-xl border border-red-200 p-6 mt-5">
        <h3 class="text-red-600 font-semibold text-sm mb-1">Danger Zone</h3>
        <p class="text-slate-500 text-xs mb-4">This will permanently delete the promotion and unassign it from all users.</p>
        <button type="button"
            onclick="openDeleteModal('{{ route('admin.promotions.destroy', $promotion) }}', '{{ $promotion->title }}')"
            class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
            Delete Promotion
        </button>
    </div>

</div>

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
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('image-preview').src = e.target.result;
                document.getElementById('image-preview').classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
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
