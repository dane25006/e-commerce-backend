<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Promotion, User, Product};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PromotionController extends Controller
{
    public function index()
    {
        $promotions = Promotion::withCount('users')->latest()->paginate(15);
        return view('admin.promotions.index', compact('promotions'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get(['id', 'name']);
        $users = User::where('role', 'customer')->orderBy('name')->get(['id', 'name', 'email']);
        return view('admin.promotions.create', compact('products', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value'=> ['required', 'numeric', 'min:0'],
            'coupon_code'   => ['nullable', 'string', 'max:50', 'unique:promotions,coupon_code'],
            'product_id'    => ['nullable', 'exists:products,id'],
            'image'         => ['nullable', 'image', 'max:2048'],
            'is_active'     => ['nullable', 'boolean'],
            'starts_at'     => ['nullable', 'date'],
            'ends_at'       => ['nullable', 'date', 'after_or_equal:starts_at'],
            'user_ids'      => ['nullable', 'array'],
            'user_ids.*'    => ['exists:users,id'],
        ]);

        $data['slug'] = Str::slug($data['title']);
        $originalSlug = $data['slug'];
        $count = 1;
        while (Promotion::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $count++;
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('promotions', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        $promotion = Promotion::create($data);

        if ($request->filled('user_ids')) {
            $promotion->users()->attach($request->user_ids, ['assigned_at' => now()]);
        }

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Promotion "' . $data['title'] . '" created.');
    }

    public function edit(Promotion $promotion)
    {
        $products = Product::orderBy('name')->get(['id', 'name']);
        $users = User::where('role', 'customer')->orderBy('name')->get(['id', 'name', 'email']);
        $assignedUserIds = $promotion->users()->pluck('users.id')->toArray();

        return view('admin.promotions.edit', compact('promotion', 'products', 'users', 'assignedUserIds'));
    }

    public function update(Request $request, Promotion $promotion)
    {
        $data = $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value'=> ['required', 'numeric', 'min:0'],
            'coupon_code'   => ['nullable', 'string', 'max:50', 'unique:promotions,coupon_code,' . $promotion->id],
            'product_id'    => ['nullable', 'exists:products,id'],
            'image'         => ['nullable', 'image', 'max:2048'],
            'is_active'     => ['nullable', 'boolean'],
            'starts_at'     => ['nullable', 'date'],
            'ends_at'       => ['nullable', 'date', 'after_or_equal:starts_at'],
            'user_ids'      => ['nullable', 'array'],
            'user_ids.*'    => ['exists:users,id'],
        ]);

        $data['slug'] = Str::slug($data['title']);
        $originalSlug = $data['slug'];
        $count = 1;
        while (Promotion::where('slug', $data['slug'])->where('id', '!=', $promotion->id)->exists()) {
            $data['slug'] = $originalSlug . '-' . $count++;
        }

        if ($request->hasFile('image')) {
            if ($promotion->image) {
                Storage::disk('public')->delete($promotion->image);
            }
            $data['image'] = $request->file('image')->store('promotions', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        $promotion->update($data);

        // Sync assigned users
        $promotion->users()->sync($request->filled('user_ids')
            ? collect($request->user_ids)->mapWithKeys(fn($id) => [$id => ['assigned_at' => now()]])
            : []);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Promotion updated.');
    }

    public function destroy(Promotion $promotion)
    {
        $name = $promotion->title;
        if ($promotion->image) {
            Storage::disk('public')->delete($promotion->image);
        }
        $promotion->users()->detach();
        $promotion->delete();

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Promotion "' . $name . '" deleted.');
    }
}
