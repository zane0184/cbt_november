<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->hasExactRole('GURU') && $user->hasPermission('question.view'), 403);

        $tags = Tag::query()
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $tags]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->hasExactRole('GURU') && $user->hasPermission('question.manage'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $tag = DB::transaction(function () use ($validated, $user) {
            $tag = Tag::create([
                'name' => trim($validated['name']),
                'slug' => Str::slug($validated['name']),
                'tenant_id' => $user->tenant_id,
            ]);

            $this->auditService->log('CREATE', 'Tag', $tag->id, null, [
                'name' => $tag->name,
                'slug' => $tag->slug,
            ]);

            return $tag;
        });

        return response()->json(['success' => true, 'data' => $tag], 201);
    }

    public function destroy(Request $request, Tag $tag)
    {
        $user = $request->user();
        abort_unless($user && $user->hasExactRole('GURU') && $user->hasPermission('question.manage'), 403);
        abort_unless($tag->tenant_id === $user->tenant_id, 404);

        return DB::transaction(function () use ($tag) {
            $lockedTag = Tag::query()
                ->where('tenant_id', $tag->tenant_id)
                ->whereKey($tag->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTag->questions()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tag yang masih dipakai Bank Soal tidak dapat dihapus.'
                ], 409);
            }

            $oldValues = $lockedTag->only(['name', 'slug']);
            $lockedTag->delete();
            $this->auditService->log('DELETE', 'Tag', $lockedTag->id, $oldValues, null);

            return response()->json(['success' => true]);
        });
    }
}
