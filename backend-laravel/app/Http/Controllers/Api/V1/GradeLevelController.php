<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GradeLevelController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index()
    {
        return response()->json(['success' => true, 'data' => GradeLevel::all()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'phase_id' => ['required', 'uuid', Rule::exists('phases', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('grade_levels', 'code')->where('phase_id', $request->phase_id)],
            'level' => 'required|integer',
        ]);

        $gradeLevel = GradeLevel::create($validated);
        $this->auditService->log('CREATE', 'GradeLevel', $gradeLevel->id, null, $gradeLevel->toArray());

        return response()->json(['success' => true, 'data' => $gradeLevel], 201);
    }

    public function show(GradeLevel $gradeLevel)
    {
        return response()->json(['success' => true, 'data' => $gradeLevel]);
    }

    public function update(Request $request, GradeLevel $gradeLevel)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('grade_levels', 'code')->where('phase_id', $gradeLevel->phase_id)->ignore($gradeLevel->id)],
            'level' => 'sometimes|integer',
            'status' => 'string'
        ]);

        $old = $gradeLevel->toArray();
        $gradeLevel->update($validated);
        $this->auditService->log('UPDATE', 'GradeLevel', $gradeLevel->id, $old, $gradeLevel->toArray());

        return response()->json(['success' => true, 'data' => $gradeLevel]);
    }

    public function destroy(GradeLevel $gradeLevel)
    {
        $this->auditService->log('DELETE', 'GradeLevel', $gradeLevel->id, $gradeLevel->toArray(), null);
        $gradeLevel->delete();
        return response()->json(['success' => true]);
    }
}
