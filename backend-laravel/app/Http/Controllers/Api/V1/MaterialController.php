<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index()
    {
        return response()->json(['success' => true, 'data' => Material::all()]);
    }

    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'curriculum_id' => ['required', 'uuid', Rule::exists('curricula', 'id')->where('tenant_id', $tenantId)],
            'phase_id' => ['required', 'uuid', Rule::exists('phases', 'id')->where('tenant_id', $tenantId)],
            'subject_id' => ['required', 'uuid', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)],
            'grade_level_id' => ['nullable', 'uuid', Rule::exists('grade_levels', 'id')->where('tenant_id', $tenantId)],
            'learning_outcome_id' => ['nullable', 'uuid', Rule::exists('learning_outcomes', 'id')->where('tenant_id', $tenantId)],
            'learning_objective_id' => ['nullable', 'uuid', Rule::exists('learning_objectives', 'id')->where('tenant_id', $tenantId)],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $material = Material::create($validated);
        $this->auditService->log('CREATE', 'Material', $material->id, null, $material->toArray());

        return response()->json(['success' => true, 'data' => $material], 201);
    }

    public function show(Material $material)
    {
        return response()->json(['success' => true, 'data' => $material]);
    }

    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'string'
        ]);

        $old = $material->toArray();
        $material->update($validated);
        $this->auditService->log('UPDATE', 'Material', $material->id, $old, $material->toArray());

        return response()->json(['success' => true, 'data' => $material]);
    }

    public function destroy(Material $material)
    {
        $this->auditService->log('DELETE', 'Material', $material->id, $material->toArray(), null);
        $material->delete();
        return response()->json(['success' => true]);
    }
}
