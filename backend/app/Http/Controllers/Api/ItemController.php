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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ItemController extends Controller
{
    /**
     * Display a paginated listing of items, optionally filtered.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        try {
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
        } catch (Throwable $e) {
            $this->logDbError('index', $e);
            throw $e;
        }
    }

    /**
     * Store a newly created item.
     */
    public function store(StoreItemRequest $request): JsonResponse
    {
        try {
            $item = Item::create($request->validated());

            Log::info('item.created', [
                'user_id' => $request->user()?->id ?? 'guest',
                'action' => 'create',
                'resource' => 'item',
                'resource_id' => $item->id,
                'request_id' => (string) ($request->header('X-Request-Id') ?: Str::uuid()),
            ]);

            return (new ItemResource($item))
                ->additional(['message' => 'Item berhasil ditambahkan.'])
                ->response()
                ->setStatusCode(201);
        } catch (Throwable $e) {
            $this->logDbError('create', $e);
            throw $e;
        }
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
        try {
            $item->update($request->validated());

            return (new ItemResource($item))
                ->additional(['message' => 'Item berhasil diperbarui.']);
        } catch (Throwable $e) {
            $this->logDbError('update', $e, $item->id);
            throw $e;
        }
    }

    /**
     * Remove the specified item.
     */
    public function destroy(Item $item): JsonResponse
    {
        try {
            $itemId = $item->id;
            $item->delete();

            return response()->json([
                'message' => 'Item berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            $this->logDbError('delete', $e, $item->id);
            throw $e;
        }
    }

    /**
     * Log sanitized database or unexpected item error.
     */
    protected function logDbError(string $action, Throwable $e, ?int $resourceId = null): void
    {
        Log::error('item.db_error', [
            'user_id' => request()->user()?->id ?? 'guest',
            'action' => $action,
            'resource' => 'item',
            'resource_id' => $resourceId,
            'exception' => get_class($e),
            'error_code' => (string) ($e->getCode() ?: 500),
            'reason' => 'database_error: ' . class_basename($e),
            'request_id' => (string) (request()->header('X-Request-Id') ?: Str::uuid()),
        ]);
    }
}
