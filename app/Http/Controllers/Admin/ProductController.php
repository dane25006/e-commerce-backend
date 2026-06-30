<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Product, Category, ProductImage};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->latest()->paginate(15);
        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'sale_price'  => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock'       => ['required', 'integer', 'min:0'],
            'is_new'      => ['nullable', 'boolean'],
            'gender'      => ['nullable', 'string', 'max:50'],
            'brand'       => ['nullable', 'string', 'max:255'],
            'type'        => ['nullable', 'string', 'max:255'],
            'department'  => ['nullable', 'string', 'max:255'],
            'image'       => ['nullable', 'image', 'max:5120'],
            'gallery.*'   => ['nullable', 'image', 'max:5120'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $originalSlug = $data['slug'];
        $count = 1;
        while (Product::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $count++;
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $i => $file) {
                $path = $file->store('products', 'public');
                $product->images()->create([
                    'image' => $path,
                    'sort_order' => $i,
                ]);
            }
        }

        return redirect()->route('admin.products.index')
                         ->with('success', 'Product "' . $data['name'] . '" created.');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        $product->load('images');
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'sale_price'  => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock'       => ['required', 'integer', 'min:0'],
            'is_new'      => ['nullable', 'boolean'],
            'gender'      => ['nullable', 'string', 'max:50'],
            'brand'       => ['nullable', 'string', 'max:255'],
            'type'        => ['nullable', 'string', 'max:255'],
            'department'  => ['nullable', 'string', 'max:255'],
            'image'       => ['nullable', 'image', 'max:2048'],
            'gallery.*'   => ['nullable', 'image', 'max:2048'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => ['exists:product_images,id'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $originalSlug = $data['slug'];
        $count = 1;
        while (Product::where('slug', $data['slug'])->where('id', '!=', $product->id)->exists()) {
            $data['slug'] = $originalSlug . '-' . $count++;
        }

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        // Delete selected gallery images
        if ($request->filled('delete_images')) {
            foreach ($request->delete_images as $id) {
                $img = ProductImage::find($id);
                if ($img) {
                    Storage::disk('public')->delete($img->image);
                    $img->delete();
                }
            }
        }

        // Upload new gallery images
        if ($request->hasFile('gallery')) {
            $lastOrder = $product->images()->max('sort_order') ?? 0;
            foreach ($request->file('gallery') as $i => $file) {
                $path = $file->store('products', 'public');
                $product->images()->create([
                    'image' => $path,
                    'sort_order' => $lastOrder + $i + 1,
                ]);
            }
        }

        return redirect()->route('admin.products.index')
                         ->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $name = $product->name;

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        foreach ($product->images as $img) {
            Storage::disk('public')->delete($img->image);
        }

        $product->delete();

        return redirect()->route('admin.products.index')
                         ->with('success', 'Product "' . $name . '" deleted.');
    }
}
