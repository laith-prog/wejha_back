<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Community\Http\Controllers\CommunityController;
use Modules\Community\Http\Controllers\ListingController;
use Modules\Community\Http\Controllers\ListingCategoryController;
use Modules\Community\Http\Controllers\RealEstateController;
use Modules\Community\Http\Controllers\VehicleController;
use Modules\Community\Http\Controllers\ServiceController;
use Modules\Community\Http\Controllers\JobController;
use Modules\Community\Http\Controllers\BidController;
use Modules\Community\Http\Controllers\ListingImageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Basic Listing Routes
Route::prefix('listings')->group(function () {
    // Public routes
    Route::get('/', [ListingController::class, 'index']);
    Route::get('/search', [ListingController::class, 'search']);
    Route::get('/{id}', [ListingController::class, 'show']);
    
    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::post('/', [ListingController::class, 'store']);
        Route::put('/{id}', [ListingController::class, 'update']);
        Route::delete('/{id}', [ListingController::class, 'destroy']);
    });
});

// Real Estate Listing Routes
Route::prefix('real-estate')->group(function () {
    // Public routes
    Route::get('/', [RealEstateController::class, 'index']);
    Route::get('/search', [RealEstateController::class, 'search']);
    Route::get('/{id}', [RealEstateController::class, 'show']);
    
    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::get('/create', [RealEstateController::class, 'create']);
        Route::post('/', [RealEstateController::class, 'store']);
    });
});

// Vehicle Listing Routes
Route::prefix('vehicles')->group(function () {
    // Public routes
    Route::get('/', [VehicleController::class, 'index']);
    Route::get('/search', [VehicleController::class, 'search']);
    Route::get('/{id}', [VehicleController::class, 'show']);
    
    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::get('/create', [VehicleController::class, 'create']);
        Route::post('/', [VehicleController::class, 'store']);
    });
});

// Service Listing Routes
Route::prefix('services')->group(function () {
    // Public routes
    Route::get('/', [ServiceController::class, 'index']);
    Route::get('/search', [ServiceController::class, 'search']);
    Route::get('/{id}', [ServiceController::class, 'show']);
    
    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::get('/create', [ServiceController::class, 'create']);
        Route::post('/', [ServiceController::class, 'store']);
    });
});

// Job Listing Routes
Route::prefix('jobs')->group(function () {
    // Public routes
    Route::get('/', [JobController::class, 'index']);
    Route::get('/search', [JobController::class, 'search']);
    Route::get('/{id}', [JobController::class, 'show']);
    
    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::get('/create', [JobController::class, 'create']);
        Route::post('/', [JobController::class, 'store']);
    });
});

// Bid/Tender Listing Routes
Route::prefix('bids')->group(function () {
    // Public routes
    Route::get('/', [BidController::class, 'index']);
    Route::get('/search', [BidController::class, 'search']);
    Route::get('/{id}', [BidController::class, 'show']);
    
    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::get('/create', [BidController::class, 'create']);
        Route::post('/', [BidController::class, 'store']);
    });
});

// Admin routes for reports
Route::middleware(['auth:api', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/listings/{id}/reports', [ListingController::class, 'getListingReports']);
});

// Image management routes
Route::middleware('auth:api')->group(function () {
    Route::post('/listings/{id}/images', [ListingImageController::class, 'upload']);
    Route::delete('/images/{id}', [ListingImageController::class, 'delete']);
    Route::put('/images/{id}/primary', [ListingImageController::class, 'setPrimary']);
    Route::put('/listings/{id}/images/order', [ListingImageController::class, 'updateOrder']);
});
Route::get('/listings/{id}/images', [ListingImageController::class, 'getListingImages']);

// Category management routes
Route::post('/listings/categories', [ListingCategoryController::class, 'store']);
Route::post('/listings/subcategories', [ListingCategoryController::class, 'storeSubcategory']);

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('communities', CommunityController::class)->names('community');
});
