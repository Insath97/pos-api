<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Models\SupplierBankAccount;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = Supplier::with('bankAccounts');

            // Search
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%")
                        ->orWhere('company_name', 'LIKE', "%{$search}%")
                        ->orWhere('contact_person_name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('contact_person_phone', 'LIKE', "%{$search}%");
                });
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('city')) {
                $query->where('city', 'LIKE', "%{$request->city}%");
            }

            $query->orderBy('name', 'asc');

            $suppliers = $query->paginate($perPage);

            if ($suppliers->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No suppliers found',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Suppliers retrieved successfully',
                'data' => $suppliers
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve suppliers',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function create() {}

    public function store(CreateSupplierRequest $request)
    {
        try {
            $data = $request->validated();

            $supplier = Supplier::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'company_name' => $data['company_name'],
                'contact_person_name' => $data['contact_person_name'],
                'contact_person_phone' => $data['contact_person_phone'],
                'alternate_phone' => $data['alternate_phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? null,
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'fax' => $data['fax'] ?? null,
                'email' => $data['email'] ?? null,
                'website' => $data['website'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active' => true
            ]);

            $bankAccounts = [];
            $hasDefault = false;

            foreach ($data['bank_names'] as $index => $bankName) {
                $isDefault = isset($data['is_default'][$index]) && $data['is_default'][$index];

                // Ensure only one default account
                if ($isDefault && $hasDefault) {
                    $isDefault = false;
                } elseif ($isDefault) {
                    $hasDefault = true;
                }

                // If no default set, make first account default
                if ($index === 0 && !$hasDefault) {
                    $isDefault = true;
                    $hasDefault = true;
                }

                $bankAccounts[] = [
                    'supplier_id' => $supplier->id,
                    'bank_name' => $bankName,
                    'account_holder_name' => $data['account_holder_names'][$index],
                    'branch_name' => $data['branch_names'][$index],
                    'account_number' => $data['account_numbers'][$index],
                    'is_default' => $isDefault,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $supplier->bankAccounts()->createMany($bankAccounts);

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier created successfully',
                'data' => $supplier->load('bankAccounts')
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create supplier',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $supplier = Supplier::with(['bankAccounts' => function ($query) {
                $query->orderBy('is_default', 'desc')->orderBy('bank_name');
            }])->find($id);

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier retrieved successfully',
                'data' => $supplier
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve supplier',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function edit(string $id) {}

    public function update(UpdateSupplierRequest $request, string $id)
    {
        try {
            $data = $request->validated();
            $supplier = Supplier::find($id);

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found',
                    'data' => []
                ], 404);
            }

            $supplierData = array_filter([
                'name' => $data['name'] ?? null,
                'code' => $data['code'] ?? null,
                'company_name' => $data['company_name'] ?? null,
                'contact_person_name' => $data['contact_person_name'] ?? null,
                'contact_person_phone' => $data['contact_person_phone'] ?? null,
                'alternate_phone' => $data['alternate_phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? null,
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'fax' => $data['fax'] ?? null,
                'email' => $data['email'] ?? null,
                'website' => $data['website'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? $supplier->is_active,
            ], function ($value) {
                return !is_null($value);
            });

            $supplier->update($supplierData);

            // Update bank accounts if provided
            if (isset($data['bank_names'])) {
                $existingBankAccountIds = $supplier->bankAccounts->pluck('id')->toArray();
                $providedBankAccountIds = $data['bank_account_ids'] ?? [];
                $accountsToDelete = array_diff($existingBankAccountIds, $providedBankAccountIds);

                // Delete removed bank accounts
                if (!empty($accountsToDelete)) {
                    SupplierBankAccount::whereIn('id', $accountsToDelete)->forceDelete();
                }

                // Update or create bank accounts
                $hasDefault = false;
                foreach ($data['bank_names'] as $index => $bankName) {
                    $bankAccountId = $data['bank_account_ids'][$index] ?? null;
                    $isDefault = isset($data['is_default'][$index]) && $data['is_default'][$index];

                    // Ensure only one default account
                    if ($isDefault && $hasDefault) {
                        $isDefault = false;
                    } elseif ($isDefault) {
                        $hasDefault = true;
                    }

                    // If no default set and first account, make it default
                    if ($index === 0 && !$hasDefault) {
                        $isDefault = true;
                        $hasDefault = true;
                    }

                    $bankAccountData = [
                        'bank_name' => $bankName,
                        'account_holder_name' => $data['account_holder_names'][$index],
                        'branch_name' => $data['branch_names'][$index],
                        'account_number' => $data['account_numbers'][$index],
                        'is_default' => $isDefault,
                        'is_active' => true,
                    ];

                    if ($bankAccountId) {
                        // Update existing account
                        SupplierBankAccount::where('id', $bankAccountId)->update($bankAccountData);
                    } else {
                        // Create new account
                        $bankAccountData['supplier_id'] = $supplier->id;
                        SupplierBankAccount::create($bankAccountData);
                    }
                }
            }

            $supplier->load(['bankAccounts' => function ($query) {
                $query->orderBy('is_default', 'desc')->orderBy('bank_name');
            }]);

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier updated successfully',
                'data' => $supplier
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update supplier',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $supplier = Supplier::find($id);

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found',
                    'data' => []
                ], 404);
            }

            $supplier->delete();
            $supplier->bankAccounts()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete supplier',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function forceDestroy(string $id)
    {
        try {
            $supplier = Supplier::withTrashed()->find($id);

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found',
                    'data' => []
                ], 404);
            }

            $supplier->forceDelete();
            $supplier->bankAccounts()->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier permanently deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete supplier',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $supplier = Supplier::withTrashed()->find($id);

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found',
                    'data' => []
                ], 404);
            }

            if (!$supplier->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier is not deleted',
                    'data' => [
                        'supplier_id' => $supplier->id,
                        'supplier_name' => $supplier->name
                    ]
                ], 422);
            }

            $supplier->restore();
            $supplier->bankAccounts()->restore();
            $supplier->load('bankAccounts');

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier restored successfully',
                'data' => $supplier
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore supplier',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function activateSupplier(string $id)
    {
        try {
            $supplier = Supplier::find($id);

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found',
                    'data' => []
                ], 404);
            }

            if ($supplier->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier is already active',
                    'data' => [
                        'current_status' => 'active',
                        'supplier_id' => $supplier->id,
                        'supplier_name' => $supplier->name
                    ]
                ], 422);
            }

            $supplier->activate();
            $supplier->load('bankAccounts');

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier activated successfully',
                'data' => $supplier
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deactivateSupplier(string $id)
    {
        try {
            $supplier = Supplier::find($id);

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found',
                    'data' => []
                ], 404);
            }

            if (!$supplier->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier is already inactive',
                    'data' => [
                        'current_status' => 'inactive',
                        'supplier_id' => $supplier->id,
                        'supplier_name' => $supplier->name
                    ]
                ], 422);
            }

            $supplier->deactivate();
            $supplier->load('bankAccounts');

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier deactivated successfully',
                'data' => $supplier
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActiveSuppliers()
    {
        try {
            $suppliers = Supplier::with('bankAccounts')
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'company_name', 'contact_person_name']);

            return response()->json([
                'status' => 'success',
                'message' => 'Active suppliers retrieved successfully',
                'data' => $suppliers
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve active suppliers',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
