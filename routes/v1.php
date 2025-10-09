<?php

use App\Http\Controllers\V1\OrganizationController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

    Route::apiResource('organizations', OrganizationController::class);
    // Additional custom routes
    Route::prefix('organizations')->group(function () {
        // Force delete (permanent deletion)
        Route::delete('{id}/force', [OrganizationController::class, 'forceDestroy']);

        // Restore soft-deleted
        Route::patch('{id}/restore', [OrganizationController::class, 'restore']);

        // Logo management
        Route::delete('{id}/logo', [OrganizationController::class, 'removeLogo']);
        Route::patch('{id}/logo', [OrganizationController::class, 'updateLogo']);

        // Status management
        Route::patch('{id}/activate', [OrganizationController::class, 'activateOrganization']);
        Route::patch('{id}/deactivate', [OrganizationController::class, 'deactivateOrganization']);
    });
});

Route::middleware('auth:api')->prefix('v1')->group(function () {});
