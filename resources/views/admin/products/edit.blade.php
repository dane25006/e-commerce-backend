@extends('layouts.admin')

@section('title', 'Edit Product')

@section('content')

    <div class="max-w-2xl mx-auto">

        <a href="{{ route('admin.products.index') }}"
            class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-6 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Products
        </a>

        <div class="bg-white rounded-xl border border-slate-200 p-8">
            <h2 class="text-slate-800 font-semibold text-base mb-1">Edit Product</h2>
            <p class="text-slate-400 text-xs mb-6">ID #{{ $product->id }}</p>

            <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data"
                class="space-y-5">
                @csrf @method('PUT')

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Product Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" required
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                           @error('name') border-red-400 bg-red-50 @else border-slate-300 @enderror" />
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Category --}}
                <div>
                    <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <select id="category_id" name="category_id" required
                        class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                           @error('category_id') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                        <option value="">— Select a category —</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}"
                                {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 mb-1.5">Description</label>
                    <textarea id="description" name="description" rows="3"
                        class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm resize-none
                           focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">{{ old('description', $product->description) }}</textarea>
                </div>

                {{-- Price + Sale Price + Stock --}}
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Price (USD) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">$</span>
                            <input type="number" id="price" name="price" value="{{ old('price', $product->price) }}"
                                required min="0" step="0.01"
                                class="w-full pl-8 pr-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                                   @error('price') border-red-400 bg-red-50 @else border-slate-300 @enderror" />
                        </div>
                        @error('price')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="sale_price" class="block text-sm font-medium text-slate-700 mb-1.5">Sale Price</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">$</span>
                            <input type="number" id="sale_price" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}"
                                min="0" step="0.01" placeholder="0.00"
                                class="w-full pl-8 pr-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                                   @error('sale_price') border-red-400 bg-red-50 @else border-slate-300 @enderror" />
                        </div>
                        @error('sale_price')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="stock" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Stock Quantity <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="stock" name="stock" value="{{ old('stock', $product->stock) }}"
                            required min="0"
                            class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                               @error('stock') border-red-400 bg-red-50 @else border-slate-300 @enderror" />
                        @error('stock')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Perfume Details --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="brand" class="block text-sm font-medium text-slate-700 mb-1.5">Brand</label>
                        <input type="text" id="brand" name="brand" value="{{ old('brand', $product->brand) }}"
                            placeholder="e.g. Channel, Dior"
                            class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                                   @error('brand') border-red-400 bg-red-50 @else border-slate-300 @enderror" />
                        @error('brand')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="gender" class="block text-sm font-medium text-slate-700 mb-1.5">Gender</label>
                        <select id="gender" name="gender"
                            class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                                   @error('gender') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                            <option value="">— Select —</option>
                            <option value="Men" {{ old('gender', $product->gender) == 'Men' ? 'selected' : '' }}>Men</option>
                            <option value="Women" {{ old('gender', $product->gender) == 'Women' ? 'selected' : '' }}>Women</option>
                            <option value="Unisex" {{ old('gender', $product->gender) == 'Unisex' ? 'selected' : '' }}>Unisex</option>
                        </select>
                        @error('gender')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-slate-700 mb-1.5">Type</label>
                        <input type="text" id="type" name="type" value="{{ old('type', $product->type) }}"
                            placeholder="e.g. Eau de Parfum, Eau de Toilette"
                            class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                                   @error('type') border-red-400 bg-red-50 @else border-slate-300 @enderror" />
                        @error('type')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="department" class="block text-sm font-medium text-slate-700 mb-1.5">Department</label>
                        <input type="text" id="department" name="department" value="{{ old('department', $product->department) }}"
                            placeholder="e.g. Luxury, Everyday"
                            class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                                   @error('department') border-red-400 bg-red-50 @else border-slate-300 @enderror" />
                        @error('department')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Is New --}}
                <div class="flex items-center gap-2">
                    <input
                        type="checkbox" id="is_new" name="is_new" value="1"
                        {{ old('is_new', $product->is_new) ? 'checked' : '' }}
                        class="w-4 h-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500"
                    />
                    <label for="is_new" class="text-sm font-medium text-slate-700">Mark as New Arrival</label>
                </div>

                {{-- Main Image --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Main Image</label>
                    @if ($product->image)
                        <div class="flex items-center gap-4 mb-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" id="image-preview"
                                class="w-16 h-16 object-cover rounded-lg border border-slate-200" />
                            <div>
                                <p class="text-xs font-medium text-slate-600">Current main image</p>
                                <p class="text-xs text-slate-400 mt-0.5">Upload a new image below to replace it</p>
                            </div>
                        </div>
                    @else
                        <img id="image-preview" src="" alt="Preview"
                            class="hidden w-16 h-16 object-cover rounded-lg border border-slate-200 mb-3" />
                    @endif
                    <label for="image"
                        class="flex items-center justify-center w-full h-24 border-2 border-dashed border-slate-300
                              rounded-xl cursor-pointer hover:border-brand-400 hover:bg-brand-50/30 transition-colors">
                        <div class="text-center">
                            <p class="text-slate-400 text-sm">Click to upload a new image</p>
                            <p class="text-slate-300 text-xs mt-0.5">JPG, PNG, WEBP up to 2MB</p>
                        </div>
                        <input type="file" id="image" name="image" accept="image/*" class="hidden"
                            onchange="previewImage(this)" />
                    </label>
                    @error('image')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Gallery Images --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Gallery Images</label>
                    @if ($product->images->count())
                        <div class="flex gap-2 flex-wrap mb-3">
                            @foreach ($product->images as $img)
                                <div class="relative group">
                                    <img src="{{ Storage::url($img->image) }}" alt="Gallery"
                                        class="w-16 h-16 object-cover rounded-lg border border-slate-200" />
                                    <label class="absolute inset-0 bg-black/40 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition cursor-pointer">
                                        <input type="checkbox" name="delete_images[]" value="{{ $img->id }}"
                                            class="w-4 h-4 rounded border-white text-red-500 focus:ring-red-500" />
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-slate-400 mb-2">Hover and check to delete gallery images</p>
                    @endif
                    <label for="gallery"
                        class="flex items-center justify-center w-full h-24 border-2 border-dashed border-slate-300
                              rounded-xl cursor-pointer hover:border-brand-400 hover:bg-brand-50/30 transition-colors">
                        <div class="text-center">
                            <p class="text-slate-400 text-sm">Click to add more gallery images</p>
                            <p class="text-slate-300 text-xs mt-0.5">You can select multiple images</p>
                        </div>
                        <input type="file" id="gallery" name="gallery[]" accept="image/*" class="hidden" multiple
                            onchange="previewGallery(this)" />
                    </label>
                    <div id="gallery-previews" class="flex gap-2 mt-2 flex-wrap"></div>
                    @error('gallery.*')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                        Save Changes
                    </button>
                    <a href="{{ route('admin.products.index') }}"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        {{-- Danger Zone --}}
        <div class="bg-white rounded-xl border border-red-200 p-6 mt-5">
            <h3 class="text-red-600 font-semibold text-sm mb-1">Danger Zone</h3>
            <p class="text-slate-500 text-xs mb-4">This will permanently delete the product and its image.</p>
            <button type="button"
                onclick="openDeleteModal('{{ route('admin.products.destroy', $product) }}', '{{ $product->name }}')"
                class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                Delete Product
            </button>
        </div>

    </div>

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

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    const preview = document.getElementById('image-preview');
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        function previewGallery(input) {
            const container = document.getElementById('gallery-previews');
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
