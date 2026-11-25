<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Services\AuditService;
use Illuminate\Http\Request;

class CurriculumController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index()
    {
        $this->authorize('viewAny', Curriculum::class);
        return response()->json([
            'success' => true,
            'data' => Curriculum::paginate()
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Curriculum::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $curriculum = Curriculum::create($validated);
        $this->auditService->log('CREATE', 'Curriculum', $curriculum->id, null, $curriculum->toArray());

        return response()->json([
            'success' => true,
            'data' => $curriculum
        ], 201);
    }

    public function show(Curriculum $curriculum)
    {
        $this->authorize('view', $curriculum);
        return response()->json([
            'success' => true,
            'data' => $curriculum
        ]);
    }

    public function update(Request $request, Curriculum $curriculum)
    {
        $this->authorize('update', $curriculum);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'status' => 'string'
        ]);

        $old = $curriculum->toArray();
        $curriculum->update($validated);
        $this->auditService->log('UPDATE', 'Curriculum', $curriculum->id, $old, $curriculum->toArray());

        return response()->json([
            'success' => true,
            'data' => $curriculum
        ]);
    }

    public function destroy(Curriculum $curriculum)
    {
        $this->authorize('delete', $curriculum);
        $this->auditService->log('DELETE', 'Curriculum', $curriculum->id, $curriculum->toArray(), null);
        $curriculum->delete();

        return response()->json([
            'success' => true,
            'message' => 'Curriculum deleted successfully'
        ]);
    }
}
