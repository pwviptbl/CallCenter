<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UrgencyKeyword;
use App\Services\UrgencyFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UrgencyKeywordController extends Controller
{
    public function __construct(
        private UrgencyFilter $urgencyFilter
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = UrgencyKeyword::query()->with('company:id,name');

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('keyword', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Filter by active
        if ($request->filled('active_only')) {
            $query->where('active', $request->boolean('active_only'));
        }

        // Filter by company
        if ($request->filled('company_id')) {
            if ($request->input('company_id') === 'global') {
                $query->whereNull('company_id');
            } else {
                $query->where('company_id', $request->input('company_id'));
            }
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'priority_level');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        if ($request->boolean('all')) {
            $keywords = $query->get();
            return response()->json($keywords);
        }

        $perPage = $request->input('per_page', 15);
        $keywords = $query->paginate($perPage);

        return response()->json($keywords);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keyword' => ['required', 'string', 'max:255'],
            'match_type' => ['required', 'in:exact,contains,regex'],
            'description' => ['nullable', 'string', 'max:255'],
            'priority_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'case_sensitive' => ['nullable', 'boolean'],
            'whole_word' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate regex if match_type is regex
        if ($request->input('match_type') === 'regex') {
            $pattern = $request->input('keyword');
            if (@preg_match('/' . $pattern . '/u', '') === false) {
                return response()->json([
                    'message' => 'Padrão regex inválido',
                    'errors' => ['keyword' => ['O padrão regex fornecido é inválido']]
                ], 422);
            }
        }

        $keyword = UrgencyKeyword::create($request->all());

        // Clear cache
        $this->urgencyFilter->clearCache($keyword->company_id);

        return response()->json([
            'message' => 'Keyword criada com sucesso',
            'data' => $keyword->load('company:id,name')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(UrgencyKeyword $urgencyKeyword): JsonResponse
    {
        return response()->json($urgencyKeyword->load('company:id,name'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UrgencyKeyword $urgencyKeyword): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keyword' => ['sometimes', 'required', 'string', 'max:255'],
            'match_type' => ['sometimes', 'required', 'in:exact,contains,regex'],
            'description' => ['nullable', 'string', 'max:255'],
            'priority_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'case_sensitive' => ['nullable', 'boolean'],
            'whole_word' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate regex if match_type is regex
        if ($request->input('match_type') === 'regex') {
            $pattern = $request->input('keyword', $urgencyKeyword->keyword);
            if (@preg_match('/' . $pattern . '/u', '') === false) {
                return response()->json([
                    'message' => 'Padrão regex inválido',
                    'errors' => ['keyword' => ['O padrão regex fornecido é inválido']]
                ], 422);
            }
        }

        $urgencyKeyword->update($request->all());

        // Clear cache
        $this->urgencyFilter->clearCache($urgencyKeyword->company_id);

        return response()->json([
            'message' => 'Keyword atualizada com sucesso',
            'data' => $urgencyKeyword->fresh()->load('company:id,name')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UrgencyKeyword $urgencyKeyword): JsonResponse
    {
        $companyId = $urgencyKeyword->company_id;
        $urgencyKeyword->delete();

        // Clear cache
        $this->urgencyFilter->clearCache($companyId);

        return response()->json([
            'message' => 'Keyword excluída com sucesso'
        ]);
    }

    /**
     * Restore a soft-deleted keyword.
     */
    public function restore(int $id): JsonResponse
    {
        $keyword = UrgencyKeyword::withTrashed()->findOrFail($id);
        $keyword->restore();

        // Clear cache
        $this->urgencyFilter->clearCache($keyword->company_id);

        return response()->json([
            'message' => 'Keyword restaurada com sucesso',
            'data' => $keyword->load('company:id,name')
        ]);
    }

    /**
     * Test keyword matching against sample text.
     */
    public function test(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keyword' => ['required', 'string'],
            'match_type' => ['required', 'in:exact,contains,regex'],
            'text' => ['required', 'string'],
            'case_sensitive' => ['nullable', 'boolean'],
            'whole_word' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $keyword = $request->input('keyword');
        $matchType = $request->input('match_type');
        $text = $request->input('text');
        $caseSensitive = $request->boolean('case_sensitive');
        $wholeWord = $request->boolean('whole_word');

        $matches = $this->urgencyFilter->testKeyword($keyword, $matchType, $text, $caseSensitive, $wholeWord);

        return response()->json([
            'matches' => $matches,
            'keyword' => $keyword,
            'match_type' => $matchType,
            'text' => $text,
            'settings' => [
                'case_sensitive' => $caseSensitive,
                'whole_word' => $wholeWord,
            ]
        ]);
    }

    /**
     * Analyze text for urgency.
     */
    public function analyze(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => ['required', 'string'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $text = $request->input('text');
        $companyId = $request->input('company_id');

        $result = $this->urgencyFilter->analyze($text, $companyId);

        return response()->json($result);
    }
}
