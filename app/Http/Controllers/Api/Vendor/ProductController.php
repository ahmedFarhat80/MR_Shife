<?php

namespace App\Http\Controllers\Api\Vendor;

use App\DTO\ProductDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ApiResponseService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ApiResponseService $apiResponse,
        private ProductService $productService
    ) {}

    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $filters = $request->only(['category_id', 'is_available', 'is_featured', 'search']);
            $paginated = $request->boolean('paginated', true);
            $perPage = $request->integer('per_page', 15);

            $products = $this->productService->getProducts($serviceProviderId, $filters, $paginated, $perPage);

            if ($paginated) {
                return $this->apiResponse->success(
                    'Products retrieved successfully',
                    [
                        'items' => ProductResource::collection($products->items()),
                        'pagination' => [
                            'total' => $products->total(),
                            'per_page' => $products->perPage(),
                            'current_page' => $products->currentPage(),
                            'last_page' => $products->lastPage(),
                            'from' => $products->firstItem(),
                            'to' => $products->lastItem(),
                        ]
                    ]
                );
            }

            return $this->apiResponse->success(
                'Products retrieved successfully',
                ProductResource::collection($products)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve products: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created product.
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $productDTO = ProductDTO::fromRequest($request->validated(), $serviceProviderId);
            
            $product = $this->productService->createProduct($productDTO);
            
            return $this->apiResponse->success(
                'Product created successfully',
                new ProductResource($product->load('category')),
                [],
                201
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to create product: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $product = $this->productService->getProduct($id, $serviceProviderId);
            
            if (!$product) {
                return $this->apiResponse->error('Product not found', null, 404);
            }

            return $this->apiResponse->success(
                'Product retrieved successfully',
                new ProductResource($product->load('category'))
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve product: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $productDTO = ProductDTO::fromRequest($request->validated(), $serviceProviderId);
            
            $updated = $this->productService->updateProduct($id, $productDTO);
            
            if (!$updated) {
                return $this->apiResponse->error('Failed to update product');
            }

            $product = $this->productService->getProduct($id, $serviceProviderId);
            
            return $this->apiResponse->success(
                'Product updated successfully',
                new ProductResource($product->load('category'))
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to update product: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $deleted = $this->productService->deleteProduct($id, $serviceProviderId);
            
            if (!$deleted) {
                return $this->apiResponse->error('Failed to delete product');
            }

            return $this->apiResponse->success('Product deleted successfully');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to delete product: ' . $e->getMessage());
        }
    }

    /**
     * Update product availability.
     */
    public function updateAvailability(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'is_available' => 'required|boolean'
        ]);

        try {
            $serviceProviderId = $request->user()->id;
            $isAvailable = $request->boolean('is_available');
            
            $updated = $this->productService->updateAvailability($id, $serviceProviderId, $isAvailable);
            
            if (!$updated) {
                return $this->apiResponse->error('Failed to update product availability');
            }

            return $this->apiResponse->success(
                'Product availability updated successfully',
                ['is_available' => $isAvailable]
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to update availability: ' . $e->getMessage());
        }
    }

    /**
     * Update product stock.
     */
    public function updateStock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'stock_quantity' => 'required|integer|min:0'
        ]);

        try {
            $serviceProviderId = $request->user()->id;
            $quantity = $request->integer('stock_quantity');
            
            $updated = $this->productService->updateStock($id, $serviceProviderId, $quantity);
            
            if (!$updated) {
                return $this->apiResponse->error('Failed to update product stock');
            }

            return $this->apiResponse->success(
                'Product stock updated successfully',
                ['stock_quantity' => $quantity]
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to update stock: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update product availability.
     */
    public function bulkUpdateAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
            'is_available' => 'required|boolean'
        ]);

        try {
            $serviceProviderId = $request->user()->id;
            $productIds = $request->input('product_ids');
            $isAvailable = $request->boolean('is_available');
            
            $updatedCount = $this->productService->bulkUpdateAvailability($productIds, $serviceProviderId, $isAvailable);
            
            return $this->apiResponse->success(
                "Successfully updated {$updatedCount} products",
                [
                    'updated_count' => $updatedCount,
                    'is_available' => $isAvailable
                ]
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to bulk update availability: ' . $e->getMessage());
        }
    }

    /**
     * Get featured products.
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $products = $this->productService->getFeaturedProducts($serviceProviderId);

            return $this->apiResponse->success(
                'Featured products retrieved successfully',
                ProductResource::collection($products)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve featured products: ' . $e->getMessage());
        }
    }

    /**
     * Get low stock products.
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $threshold = $request->integer('threshold', 10);
            $products = $this->productService->getLowStockProducts($serviceProviderId, $threshold);

            return $this->apiResponse->success(
                'Low stock products retrieved successfully',
                ProductResource::collection($products)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve low stock products: ' . $e->getMessage());
        }
    }

    /**
     * Get out of stock products.
     */
    public function outOfStock(Request $request): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $products = $this->productService->getOutOfStockProducts($serviceProviderId);

            return $this->apiResponse->success(
                'Out of stock products retrieved successfully',
                ProductResource::collection($products)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve out of stock products: ' . $e->getMessage());
        }
    }
}
