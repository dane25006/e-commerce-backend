<?php
 
// ─────────────────────────────────────────────────────────────
// FILE: app/Http/Controllers/Admin/CategoryController.php
// ─────────────────────────────────────────────────────────────
namespace App\Http\Controllers\Admin;
 
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
 
class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->latest()->paginate(15);
        return view('admin.categories.index', compact('categories'));
    }
 
    public function create()
    {
        return view('admin.categories.create');
    }
 
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);
 
        $data['slug'] = Str::slug($data['name']);
 
        // Ensure slug is unique
        $originalSlug = $data['slug'];
        $count = 1;
        while (Category::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $count++;
        }
 
        Category::create($data);
 
        return redirect()->route('admin.categories.index')
                         ->with('success', 'Category "' . $data['name'] . '" created.');
    }
 
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }
 
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id],
        ]);
 
        $data['slug'] = Str::slug($data['name']);
 
        // Ensure slug unique (excluding self)
        $originalSlug = $data['slug'];
        $count = 1;
        while (Category::where('slug', $data['slug'])->where('id', '!=', $category->id)->exists()) {
            $data['slug'] = $originalSlug . '-' . $count++;
        }
 
        $category->update($data);
 
        return redirect()->route('admin.categories.index')
                         ->with('success', 'Category updated.');
    }
 
    public function destroy(Category $category)
    {
        $name = $category->name;
        $category->delete();
        return redirect()->route('admin.categories.index')
                         ->with('success', 'Category "' . $name . '" deleted.');
    }
}
 