<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionPublication;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuestionPublicationController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Question::class);
        $publications = QuestionPublication::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->with(['question.currentVersion'])
            ->orderByDesc('created_at')
            ->paginate(25);
        return response()->json(['success' => true, 'data' => $publications]);
    }

    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'question_id' => ['required', 'uuid', Rule::exists('questions', 'id')->where('tenant_id', $tenantId)],
            'share_scope' => 'required|in:MADRASAH,ALL_MADRASAH',
        ]);

        $question = Question::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($validated['question_id'])
            ->firstOrFail();
        $this->authorize('update', $question);

        if ($question->status !== 'PUBLISHED') {
            return response()->json([
                'success' => false,
                'message' => 'Only published questions can be shared.'
            ], 422);
        }

        return DB::transaction(function () use ($validated, $request, $question) {
            $publication = QuestionPublication::updateOrCreate(
                ['question_id' => $validated['question_id']],
                [
                    'tenant_id' => $request->user()->tenant_id,
                    'source_tenant_id' => $request->user()->tenant_id,
                    'source_user_id' => $request->user()->id,
                    'share_scope' => $validated['share_scope'],
                    // Madrasah-only sharing is local and may be approved by
                    // the author. Cross-madrasah sharing stays pending review.
                    'status' => $validated['share_scope'] === 'MADRASAH' ? 'APPROVED' : 'PENDING',
                    'published_by' => $request->user()->id,
                    'published_at' => now(),
                ]
            );

            $this->auditService->log('SHARE', 'Question', $question->id, null, ['scope' => $validated['share_scope']]);

            return response()->json(['success' => true, 'data' => $publication], 201);
        });
    }

    public function destroy(Request $request, QuestionPublication $questionPublication)
    {
        abort_unless($questionPublication->tenant_id === $request->user()->tenant_id, 404);
        $question = Question::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->whereKey($questionPublication->question_id)
            ->firstOrFail();
        $this->authorize('update', $question);

        return DB::transaction(function () use ($questionPublication, $question) {
            $locked = QuestionPublication::query()
                ->where('tenant_id', $question->tenant_id)
                ->whereKey($questionPublication->id)
                ->lockForUpdate()
                ->firstOrFail();
            $locked->update(['status' => 'REVOKED']);
            $this->auditService->log('UNSHARE', 'Question', $question->id, null, ['publication_id' => $locked->id, 'status' => 'REVOKED']);
            return response()->json(['success' => true, 'message' => 'Publication revoked.']);
        });
    }
}
