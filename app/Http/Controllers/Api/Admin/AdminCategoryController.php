<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class AdminCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::withCount([
            'predictions as active_predictions_count' => fn ($query) => $query->where('status', 'active'),
        ])->paginate(15);

        return $this->paginatedResponse('Categories retrieved.', CategoryResource::collection($categories), $categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['icon'] = $request->file('icon')->store('categories/icons', 'public');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create($data);

        return $this->successResponse('Category created.', new CategoryResource($category), 201);
    }

    public function show(int $id): JsonResponse
    {
        $category = Category::withCount([
            'predictions as active_predictions_count' => fn ($query) => $query->where('status', 'active'),
        ])->findOrFail($id);

        return $this->successResponse('Category retrieved.', new CategoryResource($category));
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $data = $request->validated();

        if ($request->hasFile('icon')) {
            Storage::disk('public')->delete($category->icon);
            $data['icon'] = $request->file('icon')->store('categories/icons', 'public');
        } else {
            unset($data['icon']);
        }

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        } else {
            unset($data['image']);
        }

        $category->update($data);

        return $this->successResponse('Category updated.', new CategoryResource($category));
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $category->is_active = ! $category->is_active;
        $category->save();

        $message = $category->is_active ? 'Category activated.' : 'Category deactivated.';

        return $this->successResponse($message, new CategoryResource($category));
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        Storage::disk('public')->delete($category->icon);

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return $this->successResponse('Category deleted.');
    }
}
