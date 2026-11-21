<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreLearningOutcomeRequest;
use App\Http\Resources\Api\V1\LearningOutcomeResource;
use App\Models\LearningOutcome;
use App\Models\LearningOutcomeVersion;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearningOutcomeController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index()
    {
        $outcomes = LearningOutcome::with(['currentVersion'])->paginate();
        return LearningOutcomeResource::collection($outcomes);
    }

    public function store(StoreLearningOutcomeRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $outcome = LearningOutcome::create($request->only([
                'curriculum_id', 'phase_id', 'subject_id', 'element_id', 'code'
            ]));

            $version = $outcome->versions()->create([
                'major_version' => 1,
                'minor_version' => 0,
                'title' => $request->title,
                'description' => $request->description,
                'is_current' => true,
                'created_by' => $request->user()->id
            ]);

            $this->auditService->log('CREATE', 'LearningOutcome', $outcome->id, null, $outcome->toArray());

            return new LearningOutcomeResource($outcome->load('currentVersion'));
        });
    }

    public function show(LearningOutcome $learningOutcome)
    {
        return new LearningOutcomeResource($learningOutcome->load(['currentVersion', 'versions']));
    }

    public function update(Request $request, LearningOutcome $learningOutcome)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'create_new_version' => 'boolean'
        ]);

        $oldValue = $learningOutcome->load('currentVersion')->toArray();

        return DB::transaction(function () use ($request, $learningOutcome, $validated, $oldValue) {
            if ($request->boolean('create_new_version')) {
                $learningOutcome->versions()->update(['is_current' => false]);

                $lastVersion = $learningOutcome->versions()->orderBy('major_version', 'desc')->orderBy('minor_version', 'desc')->first();

                $learningOutcome->versions()->create([
                    'major_version' => $lastVersion->major_version,
                    'minor_version' => $lastVersion->minor_version + 1,
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'is_current' => true,
                    'created_by' => $request->user()->id
                ]);
            } else {
                $learningOutcome->currentVersion()->update([
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                ]);
            }

            $this->auditService->log('UPDATE', 'LearningOutcome', $learningOutcome->id, $oldValue, $learningOutcome->load('currentVersion')->toArray());

            return new LearningOutcomeResource($learningOutcome->load('currentVersion'));
        });
    }

    public function destroy(LearningOutcome $learningOutcome)
    {
        $this->auditService->log('DELETE', 'LearningOutcome', $learningOutcome->id, $learningOutcome->toArray(), null);
        $learningOutcome->delete();
        return response()->json(['success' => true]);
    }
}
