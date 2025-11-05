<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateMeasurementUnitRequest;
use App\Http\Requests\UpdateMeasurementUnitRequest;
use App\Models\MeasurementUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MeasurementUnitController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = MeasurementUnit::query();

            // Search
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('slug', 'LIKE', "%{$search}%")
                        ->orWhere('short_code', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $query->orderBy('type', 'asc')->orderBy('name', 'asc');

            $measurementUnits = $query->paginate($perPage);

            if ($measurementUnits->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No measurement units found',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Measurement units retrieved successfully',
                'data' => $measurementUnits
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve measurement units',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function create() {}

    public function store(CreateMeasurementUnitRequest $request)
    {
        try {
            $data = $request->validated();

            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $measurementUnit = MeasurementUnit::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Measurement unit created successfully',
                'data' => $measurementUnit
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create measurement unit',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $measurementUnit = MeasurementUnit::find($id);

            if (!$measurementUnit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Measurement unit not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Measurement unit retrieved successfully',
                'data' => $measurementUnit
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve measurement unit',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function edit(string $id) {}

    public function update(UpdateMeasurementUnitRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $measurementUnit = MeasurementUnit::find($id);

            if (!$measurementUnit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Measurement unit not found',
                    'data' => []
                ], 404);
            }

            if (isset($data['name']) && $data['name'] !== $measurementUnit->name) {
                $data['slug'] = Str::slug($data['name']);
            }

            $measurementUnit->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Measurement unit updated successfully',
                'data' => $measurementUnit
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update measurement unit',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $measurementUnit = MeasurementUnit::find($id);

            if (!$measurementUnit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Measurement unit not found',
                    'data' => []
                ], 404);
            }

            $measurementUnit->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Measurement unit deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete measurement unit',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function forceDestroy(string $id)
    {
        try {
            $measurementUnit = MeasurementUnit::withTrashed()->find($id);

            if (!$measurementUnit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Measurement unit not found',
                    'data' => []
                ], 404);
            }

            $measurementUnit->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Measurement unit permanently deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete measurement unit',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $measurementUnit = MeasurementUnit::withTrashed()->find($id);

            if (!$measurementUnit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Measurement unit not found',
                    'data' => []
                ], 404);
            }

            if (!$measurementUnit->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Measurement unit is not deleted',
                    'data' => [
                        'measurement_unit_id' => $measurementUnit->id,
                        'measurement_unit_name' => $measurementUnit->name
                    ]
                ], 422);
            }

            $measurementUnit->restore();

            return response()->json([
                'status' => 'success',
                'message' => 'Measurement unit restored successfully',
                'data' => $measurementUnit
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore measurement unit',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function activateMeasurementUnit(string $id)
    {
        try {
            $measurementUnit = MeasurementUnit::find($id);

            if (!$measurementUnit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Measurement unit not found',
                    'data' => []
                ], 404);
            }

            if ($measurementUnit->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Measurement unit is already active',
                    'data' => [
                        'current_status' => 'active',
                        'measurement_unit_id' => $measurementUnit->id,
                        'measurement_unit_name' => $measurementUnit->name
                    ]
                ], 422);
            }

            $measurementUnit->activate();

            return response()->json([
                'status' => 'success',
                'message' => 'Measurement unit activated successfully',
                'data' => $measurementUnit
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate measurement unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deactivateMeasurementUnit(string $id)
    {
        try {
            $measurementUnit = MeasurementUnit::find($id);

            if (!$measurementUnit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Measurement unit not found',
                    'data' => []
                ], 404);
            }

            if (!$measurementUnit->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Measurement unit is already inactive',
                    'data' => [
                        'current_status' => 'inactive',
                        'measurement_unit_id' => $measurementUnit->id,
                        'measurement_unit_name' => $measurementUnit->name
                    ]
                ], 422);
            }

            $measurementUnit->deactivate();

            return response()->json([
                'status' => 'success',
                'message' => 'Measurement unit deactivated successfully',
                'data' => $measurementUnit
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate measurement unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActiveMeasurementUnits()
    {
        try {
            $measurementUnits = MeasurementUnit::active()
                ->orderBy('type')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'short_code', 'type']);

            return response()->json([
                'status' => 'success',
                'message' => 'Active measurement units retrieved successfully',
                'data' => $measurementUnits
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve active measurement units',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function getTypes()
    {
        try {
            $jsonPath = public_path('data/measurement_types.json');

            if (!file_exists($jsonPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Measurement types file not found',
                    'data' => []
                ], 404);
            }

            $data = json_decode(file_get_contents($jsonPath), true);

            return response()->json([
                'success' => true,
                'message' => 'Measurement types retrieved successfully',
                'data' => $data['types'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load measurement types',
                'error' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function getType($id)
    {
        try {
            $jsonPath = public_path('data/measurement_types.json');

            if (!file_exists($jsonPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Measurement types file not found'
                ], 404);
            }

            $data = json_decode(file_get_contents($jsonPath), true);
            $types = $data['types'] ?? [];

            $type = collect($types)->firstWhere('id', (int)$id);

            if (!$type) {
                return response()->json([
                    'success' => false,
                    'message' => 'Measurement type not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Measurement type retrieved successfully',
                'data' => $type
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load measurement type',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
