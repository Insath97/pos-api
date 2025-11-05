<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = Unit::query();

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

            if ($request->has('is_base_unit')) {
                $query->where('is_base_unit', $request->boolean('is_base_unit'));
            }

            $query->orderBy('name', 'asc');

            $units = $query->paginate($perPage);

            if ($units->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No units found',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Units retrieved successfully',
                'data' => $units
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve units',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function create() {}

    public function store(CreateUnitRequest $request)
    {
        try {
            $data = $request->validated();

            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $unit = Unit::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Unit created successfully',
                'data' => $unit
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create unit',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $unit = Unit::find($id);

            if (!$unit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Unit retrieved successfully',
                'data' => $unit
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve unit',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function edit(string $id) {}

    public function update(UpdateUnitRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $unit = Unit::find($id);

            if (!$unit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit not found',
                    'data' => []
                ], 404);
            }

            if (isset($data['name']) && $data['name'] !== $unit->name) {
                $data['slug'] = Str::slug($data['name']);
            }

            $unit->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Unit updated successfully',
                'data' => $unit
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update unit',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $unit = Unit::find($id);

            if (!$unit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit not found',
                    'data' => []
                ], 404);
            }

            $unit->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Unit deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete unit',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function forceDestroy(string $id)
    {
        try {
            $unit = Unit::withTrashed()->find($id);

            if (!$unit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit not found',
                    'data' => []
                ], 404);
            }

            $unit->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Unit permanently deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete unit',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $unit = Unit::withTrashed()->find($id);

            if (!$unit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit not found',
                    'data' => []
                ], 404);
            }

            if (!$unit->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit is not deleted',
                    'data' => [
                        'unit_id' => $unit->id,
                        'unit_name' => $unit->name
                    ]
                ], 422);
            }

            $unit->restore();

            return response()->json([
                'status' => 'success',
                'message' => 'Unit restored successfully',
                'data' => $unit
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore unit',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function activateUnit(string $id)
    {
        try {
            $unit = Unit::find($id);

            if (!$unit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit not found',
                    'data' => []
                ], 404);
            }

            if ($unit->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit is already active',
                    'data' => [
                        'current_status' => 'active',
                        'unit_id' => $unit->id,
                        'unit_name' => $unit->name
                    ]
                ], 422);
            }

            $unit->activate();

            return response()->json([
                'status' => 'success',
                'message' => 'Unit activated successfully',
                'data' => $unit
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deactivateUnit(string $id)
    {
        try {
            $unit = Unit::find($id);

            if (!$unit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit not found',
                    'data' => []
                ], 404);
            }

            if (!$unit->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit is already inactive',
                    'data' => [
                        'current_status' => 'inactive',
                        'unit_id' => $unit->id,
                        'unit_name' => $unit->name
                    ]
                ], 422);
            }

            $unit->deactivate();

            return response()->json([
                'status' => 'success',
                'message' => 'Unit deactivated successfully',
                'data' => $unit
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActiveUnits()
    {
        try {
            $units = Unit::active()
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'short_code', 'is_base_unit']);

            return response()->json([
                'status' => 'success',
                'message' => 'Active units retrieved successfully',
                'data' => $units
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve active units',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
