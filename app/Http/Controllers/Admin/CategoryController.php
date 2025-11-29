<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Lọc theo trạng thái (0: ẩn, 1: hiển thị)
        if ($request->filled('status') && $request->status !== '') {
            $query->where('category_is_display', $request->status);
        }
        // Nếu không có filter status, hiển thị tất cả

        // Tìm kiếm
        if ($request->filled('search')) {
            $query->where('category_name', 'like', '%' . $request->search . '%');
        }

        // Lọc theo loại
        if ($request->filled('type')) {
            $query->where('categorry_type', $request->type);
        }

        $categories = $query->orderBy('category_id', 'desc')->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => 'required|max:255|unique:categories,category_name',
            'category_type' => 'required|max:100',
            'category_img' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'category_name.required' => 'Tên danh mục không được để trống',
            'category_name.unique' => 'Tên danh mục đã tồn tại',
            'category_type.required' => 'Loại danh mục không được để trống',
            'category_img.required' => 'Ảnh danh mục không được để trống',
            'category_img.image' => 'File phải là ảnh',
        ]);

        // Xử lý upload ảnh
        $imageName = null;
        if ($request->hasFile('category_img')) {
            $image = $request->file('category_img');
            $imageName = $image->getClientOriginalName(); // Lưu tên file gốc
            
            $destinationPath = public_path('imgs/categories');
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            $image->move($destinationPath, $imageName);
        }

        Category::create([
            'category_name' => $validated['category_name'],
            'categorry_type' => $validated['category_type'],  // Lưu ý typo trong database
            'category_img' => $imageName,
            'category_added_date' => now(),
            'category_is_display' => $request->has('category_is_display') ? 1 : 0,
        ]);

        return redirect()->route('admin.categories.index')
                        ->with('success', 'Thêm danh mục thành công!');
    }

    /**
     * Show the form for editing the category.
     */
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the category.
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'category_name' => 'required|max:255|unique:categories,category_name,' . $id . ',category_id',
            'category_type' => 'required|max:100',
            'category_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'category_name.required' => 'Tên danh mục không được để trống',
            'category_name.unique' => 'Tên danh mục đã tồn tại',
            'category_type.required' => 'Loại danh mục không được để trống',
            'category_img.image' => 'File phải là ảnh',
        ]);

        // Xử lý upload ảnh mới
        $imageName = $category->category_img;
        if ($request->hasFile('category_img')) {
            // Xóa ảnh cũ
            if ($category->category_img) {
                $oldImagePath = public_path('imgs/categories/' . $category->category_img);
                if (File::exists($oldImagePath)) {
                    File::delete($oldImagePath);
                }
            }

            // Upload ảnh mới
            $image = $request->file('category_img');
            $imageName = $image->getClientOriginalName(); // Lưu tên file gốc
            
            $destinationPath = public_path('imgs/categories');
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            $image->move($destinationPath, $imageName);
        }

        $category->update([
            'category_name' => $validated['category_name'],
            'categorry_type' => $validated['category_type'],  // Lưu ý typo trong database
            'category_img' => $imageName,
            'category_is_display' => $request->has('category_is_display') ? 1 : 0,
        ]);

        return redirect()->route('admin.categories.index')
                        ->with('success', 'Cập nhật danh mục thành công!');
    }

    /**
     * Ẩn danh mục (soft delete).
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Ẩn danh mục: đặt category_is_display = 0
        $category->update([
            'category_is_display' => 0
        ]);

        return redirect()->route('admin.categories.index')
                        ->with('success', 'Đã ẩn danh mục!');
    }

    /**
     * Khôi phục danh mục đã ẩn.
     */
    public function restore($id)
    {
        $category = Category::findOrFail($id);

        // Hiển thị lại danh mục: đặt category_is_display = 1
        $category->update([
            'category_is_display' => 1
        ]);

        return redirect()->route('admin.categories.index')
                        ->with('success', 'Đã khôi phục danh mục!');
    }
}
