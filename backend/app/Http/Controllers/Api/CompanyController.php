<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Company::query();

        // Filtrar apenas ativas
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Busca por nome
        if ($request->filled('search')) {
            $query->where('name', 'ilike', "%{$request->search}%");
        }

        // Ordenação
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginação ou listagem completa
        if ($request->boolean('all')) {
            $companies = $query->get();
        } else {
            $companies = $query->paginate($request->input('per_page', 15));
        }

        return response()->json($companies);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = Company::create($request->validated());

        return response()->json([
            'message' => 'Empresa criada com sucesso',
            'data' => $company
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company): JsonResponse
    {
        return response()->json($company);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $company->update($request->validated());

        return response()->json([
            'message' => 'Empresa atualizada com sucesso',
            'data' => $company->fresh()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return response()->json([
            'message' => 'Empresa excluída com sucesso'
        ]);
    }

    /**
     * Restore a soft-deleted company.
     */
    public function restore(int $id): JsonResponse
    {
        $company = Company::withTrashed()->findOrFail($id);
        $company->restore();

        return response()->json([
            'message' => 'Empresa restaurada com sucesso',
            'data' => $company->fresh()
        ]);
    }
}
