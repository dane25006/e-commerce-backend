@extends('layouts.admin')

@section('title', 'New Product')

@section('content')

<div class="max-w-2xl mx-auto">

    <a href="{{ route('admin.products.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-6 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Products
    </a>

    <div class="bg-white rounded-xl border border-slate-200 p-8">
        <h2 class="text-slate-800 font-semibold text-base mb-6">Create Product</h2>

        <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Product Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text" id="name" name="name"
                    value="{{ old('name') }}" required
                    placeholder="e.g. Wireless Headphones"
                    class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                           @error('name') border-red-400 bg-red-50 @else border-slate-300 @enderror"
                />
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Category --}}
            <div>
                <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Category <span class="text-red-500">*</span>
                </label>
                <select
                    id="category_id" name="category_id" required
                    class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                           @error('category_id') border-red-400 bg-red-50 @else border-slate-300 @enderror"
                >
                    <option value="">— Select a category —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Description
                </label>
                <textarea
                    id="description" name="description" rows="3"
                    placeholder="Short product description…"
                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm resize-none
                           focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                           @error('description') border-red-400 bg-red-50 @enderror"
                >{{ old('description') }}</textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Price + Sale Price + Stock --}}
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label for="price" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Price (USD) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">$</span>
                        <input
                            type="number" id="price" name="price"
                            value="{{ old('price') }}" required min="0" step="0.01"
                            placeholder="0.00"
                            class="w-full pl-8 pr-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                                   @error('price') border-red-400 bg-red-50 @else border-slate-300 @enderror"
                        />
                    </div>
                    @error('price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="sale_price" class="block text-sm font-medium text-slate-700 mb-1.5">Sale Price</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">$</span>
                        <input
                            type="number" id="sale_price" name="sale_price"
                            value="{{ old('sale_price') }}" min="0" step="0.01" placeholder="0.00"
                            class="w-full pl-8 pr-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                                   @error('sale_price') border-red-400 bg-red-50 @else border-slate-300 @enderror"
                        />
                    </div>
                    @error('sale_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="stock" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Stock Quantity <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number" id="stock" name="stock"
                        value="{{ old('stock', 0) }}" required min="0"
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                               @error('stock') border-red-400 bg-red-50 @else border-slate-300 @enderror"
                    />
                    @error('stock') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Perfume Details --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="brand" class="block text-sm font-medium text-slate-700 mb-1.5">Brand</label>
                    <input
                        type="text" id="brand" name="brand" value="{{ old('brand') }}"
                        placeholder="e.g. Channel, Dior"
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                               @error('brand') border-red-400 bg-red-50 @else border-slate-300 @enderror"
                    />
                    @error('brand') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="gender" class="block text-sm font-medium text-slate-700 mb-1.5">Gender</label>
                    <select id="gender" name="gender"
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                               @error('gender') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                        <option value="">— Select —</option>
                        <option value="Men" {{ old('gender') == 'Men' ? 'selected' : '' }}>Men</option>
                        <option value="Women" {{ old('gender') == 'Women' ? 'selected' : '' }}>Women</option>
                        <option value="Unisex" {{ old('gender') == 'Unisex' ? 'selected' : '' }}>Unisex</option>
                    </select>
                    @error('gender') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-slate-700 mb-1.5">Type</label>
                    <input
                        type="text" id="type" name="type" value="{{ old('type') }}"
                        placeholder="e.g. Eau de Parfum, Eau de Toilette"
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                               @error('type') border-red-400 bg-red-50 @else border-slate-300 @enderror"
                    />
                    @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="department" class="block text-sm font-medium text-slate-700 mb-1.5">Department</label>
                    <input
                        type="text" id="department" name="department" value="{{ old('department') }}"
                        placeholder="e.g. Luxury, Everyday"
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                               @error('department') border-red-400 bg-red-50 @else border-slate-300 @enderror"
                    />
                    @error('department') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Is New --}}
            <div class="flex items-center gap-2">
                <input
                    type="checkbox" id="is_new" name="is_new" value="1"
                    {{ old('is_new') ? 'checked' : '' }}
                    class="w-4 h-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500"
                />
                <label for="is_new" class="text-sm font-medium text-slate-700">Mark as New Arrival</label>
            </div>

            {{-- Main Image Upload --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Main Image</label>
                <label for="image"
                       class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-slate-300
                              rounded-xl cursor-pointer hover:border-brand-400 hover:bg-brand-50/30 transition-colors">
                    <div id="upload-placeholder" class="text-center">
                        <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-slate-400 text-sm">Click to upload main image</p>
                        <p class="text-slate-300 text-xs mt-1">JPG, PNG, WEBP up to 5MB</p>
                    </div>
                    <img id="image-preview" src="" alt="Preview" class="hidden h-28 object-contain rounded-lg"/>
                    <input type="file" id="image" name="image" accept="image/*" class="hidden"
                           onchange="previewImage(this)"/>
                </label>
                @error('image') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Gallery Images Upload --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Gallery Images</label>
                <label for="gallery"
                       class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-slate-300
                              rounded-xl cursor-pointer hover:border-brand-400 hover:bg-brand-50/30 transition-colors">
                    <div class="text-center">
                        <svg class="w-6 h-6 text-slate-300 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-slate-400 text-sm">Click to upload gallery images</p>
                        <p class="text-slate-300 text-xs mt-0.5">You can select multiple images</p>
                    </div>
                    <input type="file" id="gallery" name="gallery[]" accept="image/*" class="hidden" multiple
                           onchange="previewGallery(this)"/>
                </label>
                <div id="gallery-previews" class="flex gap-2 mt-2 flex-wrap"></div>
                @error('gallery.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    Create Product
                </button>
                <a href="{{ route('admin.products.index') }}"
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
function previewGallery(input) {
    const container = document.getElementById('gallery-previews');
    container.innerHTML = '';
    if (input.files) {
        for (const file of input.files) {
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-16 h-16 object-cover rounded-lg border border-slate-200';
                container.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
    }
}
</script>

@endsection