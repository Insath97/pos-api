<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\BranchController;
use App\Http\Controllers\V1\BrandController;
use App\Http\Controllers\V1\ContainerController;
use App\Http\Controllers\V1\MainCategoryController;
use App\Http\Controllers\V1\MeasurementUnitController;
use App\Http\Controllers\V1\OrganizationController;
use App\Http\Controllers\V1\PermissionController;
use App\Http\Controllers\V1\RoleController;
use App\Http\Controllers\V1\SubCategoryController;
use App\Http\Controllers\V1\SupplierController;
use App\Http\Controllers\V1\UnitController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth:api')->prefix('v1')->group(function () {

    Route::get('me', [AuthController::class, 'me']);

    Route::post('logout', [AuthController::class, 'logout']);

    Route::apiResource('permissions', PermissionController::class);

    Route::apiResource('roles', RoleController::class);

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

    Route::apiResource('suppliers', SupplierController::class);
    Route::prefix('suppliers')->group(function () {
        Route::delete('{id}/force', [SupplierController::class, 'forceDestroy']);
        Route::patch('{id}/restore', [SupplierController::class, 'restore']);
        Route::patch('{id}/activate', [SupplierController::class, 'activateSupplier']);
        Route::patch('{id}/deactivate', [SupplierController::class, 'deactivateSupplier']);
        Route::get('active/list', [SupplierController::class, 'getActiveSuppliers']);
    });

    Route::apiResource('units', UnitController::class);
    Route::prefix('units')->group(function () {
        Route::delete('{id}/force', [UnitController::class, 'forceDestroy']);
        Route::patch('{id}/restore', [UnitController::class, 'restore']);
        Route::patch('{id}/activate', [UnitController::class, 'activateUnit']);
        Route::patch('{id}/deactivate', [UnitController::class, 'deactivateUnit']);
        Route::get('active/list', [UnitController::class, 'getActiveUnits']);
    });

    Route::get('measurement-types', [MeasurementUnitController::class, 'getTypes']);
    Route::get('measurement-types/{id}', [MeasurementUnitController::class, 'getType']);

    Route::apiResource('measurement-units', MeasurementUnitController::class);
    Route::prefix('measurement-units')->group(function () {
        Route::delete('{id}/force', [MeasurementUnitController::class, 'forceDestroy']);
        Route::patch('{id}/restore', [MeasurementUnitController::class, 'restore']);
        Route::patch('{id}/activate', [MeasurementUnitController::class, 'activateMeasurementUnit']);
        Route::patch('{id}/deactivate', [MeasurementUnitController::class, 'deactivateMeasurementUnit']);
        Route::get('active/list', [MeasurementUnitController::class, 'getActiveMeasurementUnits']);
    });

    Route::apiResource('containers', ContainerController::class);
    Route::prefix('containers')->group(function () {
        Route::delete('{id}/force', [ContainerController::class, 'forceDestroy']);
        Route::patch('{id}/restore', [ContainerController::class, 'restore']);
        Route::patch('{id}/activate', [ContainerController::class, 'activateContainer']);
        Route::patch('{id}/deactivate', [ContainerController::class, 'deactivateContainer']);
        Route::get('active/list', [ContainerController::class, 'getActiveContainers']);
    });
});
