<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Phase;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhaseController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index()
    {
        $this->authorize('viewAny', Phase::class);
        return response()->json(['success' => true, 'data' => Phase::all()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Phase::class);
        $validated = $request->validate([
            'curriculum_id' => ['required', 'uuid', Rule::exists('curricula', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('phases', 'code')->where('curriculum_id', $request->curriculum_id)],
            'description' => 'nullable|string',
        ]);

        $phase = Phase::create($validated);
        $this->auditService->log('CREATE', 'Phase', $phase->id, null, $phase->toArray());

        return response()->json(['success' => true, 'data' => $phase], 201);
    }

    public function show(Phase $phase)
    {
        $this->authorize('view', $phase);
        return response()->json(['success' => true, 'data' => $phase]);
    }

    public function update(Request $request, Phase $phase)
    {
        $this->authorize('update', $phase);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('phases', 'code')->where('curriculum_id', $phase->curriculum_id)->ignore($phase->id)],
            'description' => 'nullable|string',
            'status' => 'string'
        ]);

        $old = $phase->toArray();
        $phase->update($validated);
        $this->auditService->log('UPDATE', 'Phase', $phase->id, $old, $phase->toArray());

        return response()->json(['success' => true, 'data' => $phase]);
    }

    public function destroy(Phase $phase)
    {
        $this->authorize('delete', $phase);
        $this->auditService->log('DELETE', 'Phase', $phase->id, $phase->toArray(), null);
        $phase->delete();
        return response()->json(['success' => true]);
    }
}
