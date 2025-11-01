<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\BranchController;
use App\Http\Controllers\V1\BrandController;
use App\Http\Controllers\V1\MainCategoryController;
use App\Http\Controllers\V1\OrganizationController;
use App\Http\Controllers\V1\SubCategoryController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth:api')->prefix('v1')->group(function () {

    Route::post('logout', [AuthController::class, 'logout']);

    Route::apiResource('organizations', OrganizationController::class);
    Route::prefix('organizations')->group(function () {
        Route::delete('{id}/force', [OrganizationController::class, 'forceDestroy']);
        Route::patch('{id}/restore', [OrganizationController::class, 'restore']);
        Route::delete('{id}/logo', [OrganizationController::class, 'removeLogo']);
        Route::patch('{id}/logo', [OrganizationController::class, 'updateLogo']);
        Route::patch('{id}/activate', [OrganizationController::class, 'activateOrganization']);
        Route::patch('{id}/deactivate', [OrganizationController::class, 'deactivateOrganization']);
    });

    Route::apiResource('branches', BranchController::class);
    Route::prefix('branches')->group(function () {
        Route::delete('{id}/force', [BranchController::class, 'forceDestroy']);
        Route::patch('{id}/restore', [BranchController::class, 'restore']);
        Route::patch('{id}/activate', [BranchController::class, 'activateBranch']);
        Route::patch('{id}/deactivate', [BranchController::class, 'deactivateBranch']);
        Route::patch('{id}/set-main', [BranchController::class, 'setAsMainBranch']);
        Route::get('organization/{organizationId}', [BranchController::class, 'getByOrganization']);
    });

    Route::apiResource('main-categories', MainCategoryController::class);
    Route::prefix('main-categories')->group(function () {
        Route::delete('{id}/force', [MainCategoryController::class, 'forceDestroy']);
        Route::patch('{id}/restore', [MainCategoryController::class, 'restore']);
        Route::patch('{id}/activate', [MainCategoryController::class, 'activateMainCategory']);
        Route::patch('{id}/deactivate', [MainCategoryController::class, 'deactivateMainCategory']);
        Route::get('active/list', [MainCategoryController::class, 'getActiveCategories']);
    });

    Route::apiResource('sub-categories', SubCategoryController::class);
    Route::prefix('sub-categories')->group(function () {
        Route::delete('{id}/force', [SubCategoryController::class, 'forceDestroy']);
        Route::patch('{id}/restore', [SubCategoryController::class, 'restore']);
        Route::patch('{id}/activate', [SubCategoryController::class, 'activateSubCategory']);
        Route::patch('{id}/deactivate', [SubCategoryController::class, 'deactivateSubCategory']);
        Route::get('main-category/{mainCategoryId}', [SubCategoryController::class, 'getByMainCategory']);
        Route::get('active/list', [SubCategoryController::class, 'getActiveSubCategories']);
    });

    Route::apiResource('brands', BrandController::class);
    Route::prefix('brands')->group(function () {
        Route::delete('{id}/force', [BrandController::class, 'forceDestroy']);
        Route::patch('{id}/restore', [BrandController::class, 'restore']);
        Route::patch('{id}/activate', [BrandController::class, 'activateBrand']);
        Route::patch('{id}/deactivate', [BrandController::class, 'deactivateBrand']);
        Route::get('active/list', [BrandController::class, 'getActiveBrands']);
    });
});
