<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ItemController extends Controller
{
    /**
     * Display a paginated listing of items, optionally filtered.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $items = Item::query()
            ->when($request->query('q'), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($request->query('condition'), fn ($query, $condition) => $query->where('condition', $condition))
            ->latest('id')
            ->paginate(max(1, min((int) $request->query('per_page', 10), 100)))
            ->withQueryString();

        return ItemResource::collection($items);
    }

    /**
     * Store a newly created item.
     */
    public function store(StoreItemRequest $request): JsonResponse
    {
        $item = Item::create($request->validated());

        return (new ItemResource($item))
            ->additional(['message' => 'Item berhasil ditambahkan.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified item.
     */
    public function show(Item $item): ItemResource
    {
        return new ItemResource($item);
    }

    /**
     * Update the specified item.
     */
    public function update(UpdateItemRequest $request, Item $item): ItemResource
    {
        $item->update($request->validated());

        return (new ItemResource($item))
            ->additional(['message' => 'Item berhasil diperbarui.']);
    }

    /**
     * Remove the specified item.
     */
    public function destroy(Item $item): JsonResponse
    {
        $item->delete();

        return response()->json([
            'message' => 'Item berhasil dihapus.',
        ]);
    }
}
