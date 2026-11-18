<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LearningPath;
use App\Models\LearningPathVersion;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LearningPathController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index()
    {
        return response()->json(['success' => true, 'data' => LearningPath::with('currentVersion')->paginate()]);
    }

    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'curriculum_id' => ['required', 'uuid', Rule::exists('curricula', 'id')->where('tenant_id', $tenantId)],
            'subject_id' => ['required', 'uuid', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)],
            'phase_id' => ['required', 'uuid', Rule::exists('phases', 'id')->where('tenant_id', $tenantId)],
            'code' => ['required', 'string', 'max:50', Rule::unique('learning_paths', 'code')->where('tenant_id', $tenantId)],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objective_ids' => 'required|array',
            'objective_ids.*' => ['uuid', Rule::exists('learning_objectives', 'id')->where('tenant_id', $tenantId)]
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $path = LearningPath::create($request->only(['curriculum_id', 'subject_id', 'phase_id', 'code', 'name']));

            $version = $path->versions()->create([
                'major_version' => 1,
                'minor_version' => 0,
                'description' => $validated['description'],
                'is_current' => true
            ]);

            foreach ($validated['objective_ids'] as $index => $id) {
                $version->objectives()->attach($id, ['id' => (string) \Illuminate\Support\Str::uuid(), 'order_index' => $index, 'tenant_id' => $request->user()->tenant_id]);
            }

            $this->auditService->log('CREATE', 'LearningPath', $path->id, null, $path->toArray());

            return response()->json(['success' => true, 'data' => $path->load('currentVersion.objectives')], 201);
        });
    }

    public function show(LearningPath $learningPath)
    {
        return response()->json(['success' => true, 'data' => $learningPath->load('currentVersion.objectives', 'versions')]);
    }

    public function update(Request $request, LearningPath $learningPath)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'objective_ids' => 'sometimes|required|array',
            'objective_ids.*' => ['uuid', Rule::exists('learning_objectives', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'create_new_version' => 'boolean'
        ]);

        $old = $learningPath->load('currentVersion')->toArray();

        return DB::transaction(function () use ($learningPath, $validated, $request, $old) {
            $learningPath->update($request->only('name'));

            if ($request->boolean('create_new_version') || isset($validated['objective_ids'])) {
                $learningPath->versions()->update(['is_current' => false]);
                $last = $learningPath->versions()->orderBy('major_version', 'desc')->orderBy('minor_version', 'desc')->first();

                $newVersion = $learningPath->versions()->create([
                    'major_version' => $last->major_version,
                    'minor_version' => $last->minor_version + 1,
                    'description' => $validated['description'] ?? $last->description,
                    'is_current' => true
                ]);

                $ids = $validated['objective_ids'] ?? $last->objectives->pluck('id')->toArray();
                foreach ($ids as $index => $id) {
                    $newVersion->objectives()->attach($id, ['id' => (string) \Illuminate\Support\Str::uuid(), 'order_index' => $index, 'tenant_id' => $request->user()->tenant_id]);
                }
            } else {
                $learningPath->currentVersion->update(['description' => $validated['description'] ?? $learningPath->currentVersion->description]);
            }

            $this->auditService->log('UPDATE', 'LearningPath', $learningPath->id, $old, $learningPath->load('currentVersion')->toArray());

            return response()->json(['success' => true, 'data' => $learningPath->load('currentVersion.objectives')]);
        });
    }

    public function destroy(LearningPath $learningPath)
    {
        $this->auditService->log('DELETE', 'LearningPath', $learningPath->id, $learningPath->toArray(), null);
        $learningPath->delete();
        return response()->json(['success' => true]);
    }
}
