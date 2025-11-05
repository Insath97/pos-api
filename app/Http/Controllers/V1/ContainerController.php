<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateContainerRequest;
use App\Http\Requests\UpdateContainerRequest;
use App\Models\Container;
use App\Models\MeasurementUnit;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContainerController extends Controller
{

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = Container::with(['baseUnit', 'measurementUnit']);

            // Search
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('slug', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('base_unit_id')) {
                $query->where('base_unit_id', $request->base_unit_id);
            }

            if ($request->has('measurement_unit_id')) {
                $query->where('measurement_unit_id', $request->measurement_unit_id);
            }

            $query->orderBy('name', 'asc');

            $containers = $query->paginate($perPage);

            if ($containers->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No containers found',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Containers retrieved successfully',
                'data' => $containers
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve containers',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function create() {}

    public function store(CreateContainerRequest $request)
    {
        try {
            $data = $request->validated();

            // Check if base unit exists and is active
            $baseUnit = Unit::find($data['base_unit_id']);
            if (!$baseUnit || !$baseUnit->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Base unit not found or is inactive',
                    'data' => []
                ], 422);
            }

            // Check if measurement unit exists and is active
            $measurementUnit = MeasurementUnit::find($data['measurement_unit_id']);
            if (!$measurementUnit || !$measurementUnit->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Measurement unit not found or is inactive',
                    'data' => []
                ], 422);
            }

            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $container = Container::create($data);
            $container->load(['baseUnit', 'measurementUnit']);

            return response()->json([
                'status' => 'success',
                'message' => 'Container created successfully',
                'data' => $container
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create container',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $container = Container::with(['baseUnit', 'measurementUnit'])->find($id);

            if (!$container) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Container not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Container retrieved successfully',
                'data' => $container
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve container',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function edit(string $id) {}

    public function update(UpdateContainerRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $container = Container::find($id);

            if (!$container) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Container not found',
                    'data' => []
                ], 404);
            }

            // Check base unit if provided
            if (isset($data['base_unit_id'])) {
                $baseUnit = Unit::find($data['base_unit_id']);
                if (!$baseUnit || !$baseUnit->is_active) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Base unit not found or is inactive',
                        'data' => []
                    ], 422);
                }
            }

            // Check measurement unit if provided
            if (isset($data['measurement_unit_id'])) {
                $measurementUnit = MeasurementUnit::find($data['measurement_unit_id']);
                if (!$measurementUnit || !$measurementUnit->is_active) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Measurement unit not found or is inactive',
                        'data' => []
                    ], 422);
                }
            }

            if (isset($data['name']) && $data['name'] !== $container->name) {
                $data['slug'] = Str::slug($data['name']);
            }

            $container->update($data);
            $container->load(['baseUnit', 'measurementUnit']);

            return response()->json([
                'status' => 'success',
                'message' => 'Container updated successfully',
                'data' => $container
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update container',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $container = Container::find($id);

            if (!$container) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Container not found',
                    'data' => []
                ], 404);
            }

            $container->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Container deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete container',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function forceDestroy(string $id)
    {
        try {
            $container = Container::withTrashed()->find($id);

            if (!$container) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Container not found',
                    'data' => []
                ], 404);
            }

            $container->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Container permanently deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete container',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $container = Container::withTrashed()->find($id);

            if (!$container) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Container not found',
                    'data' => []
                ], 404);
            }

            if (!$container->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Container is not deleted',
                    'data' => [
                        'container_id' => $container->id,
                        'container_name' => $container->name
                    ]
                ], 422);
            }

            $container->restore();
            $container->load(['baseUnit', 'measurementUnit']);

            return response()->json([
                'status' => 'success',
                'message' => 'Container restored successfully',
                'data' => $container
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore container',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function activateContainer(string $id)
    {
        try {
            $container = Container::find($id);

            if (!$container) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Container not found',
                    'data' => []
                ], 404);
            }

            if ($container->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Container is already active',
                    'data' => [
                        'current_status' => 'active',
                        'container_id' => $container->id,
                        'container_name' => $container->name
                    ]
                ], 422);
            }

            $container->activate();
            $container->load(['baseUnit', 'measurementUnit']);

            return response()->json([
                'status' => 'success',
                'message' => 'Container activated successfully',
                'data' => $container
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate container',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deactivateContainer(string $id)
    {
        try {
            $container = Container::find($id);

            if (!$container) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Container not found',
                    'data' => []
                ], 404);
            }

            if (!$container->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Container is already inactive',
                    'data' => [
                        'current_status' => 'inactive',
                        'container_id' => $container->id,
                        'container_name' => $container->name
                    ]
                ], 422);
            }

            $container->deactivate();
            $container->load(['baseUnit', 'measurementUnit']);

            return response()->json([
                'status' => 'success',
                'message' => 'Container deactivated successfully',
                'data' => $container
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate container',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActiveContainers()
    {
        try {
            $containers = Container::with(['baseUnit', 'measurementUnit'])
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'capacity', 'base_unit_id', 'measurement_unit_id']);

            return response()->json([
                'status' => 'success',
                'message' => 'Active containers retrieved successfully',
                'data' => $containers
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve active containers',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
