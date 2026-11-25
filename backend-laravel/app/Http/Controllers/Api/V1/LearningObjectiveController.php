<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LearningObjective;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LearningObjectiveController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index()
    {
        return response()->json(['success' => true, 'data' => LearningObjective::all()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'learning_outcome_id' => ['required', 'uuid', Rule::exists('learning_outcomes', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'code' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:255',
            'description' => 'required|string',
            'order_index' => 'integer'
        ]);

        $objective = LearningObjective::create($validated);
        $this->auditService->log('CREATE', 'LearningObjective', $objective->id, null, $objective->toArray());

        return response()->json(['success' => true, 'data' => $objective], 201);
    }

    public function show(LearningObjective $learningObjective)
    {
        return response()->json(['success' => true, 'data' => $learningObjective]);
    }

    public function update(Request $request, LearningObjective $learningObjective)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:255',
            'description' => 'sometimes|required|string',
            'order_index' => 'sometimes|integer',
            'status' => 'string'
        ]);

        $old = $learningObjective->toArray();
        $learningObjective->update($validated);
        $this->auditService->log('UPDATE', 'LearningObjective', $learningObjective->id, $old, $learningObjective->toArray());

        return response()->json(['success' => true, 'data' => $learningObjective]);
    }

    public function destroy(LearningObjective $learningObjective)
    {
        $this->auditService->log('DELETE', 'LearningObjective', $learningObjective->id, $learningObjective->toArray(), null);
        $learningObjective->delete();
        return response()->json(['success' => true]);
    }
}
