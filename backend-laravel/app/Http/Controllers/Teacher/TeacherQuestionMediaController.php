<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

final class TeacherQuestionMediaController extends Controller
{
    /**
     * Store and attach a private tenant media asset to the selected draft version.
     * The existing MediaController remains the single source of upload/attach policy.
     */
    public function storeAndAttach(
        Request $request,
        string $questionId,
        string $versionId,
        MediaController $mediaController
    ): RedirectResponse {
        $request->merge([
            'owner_type' => 'QUESTION',
            'owner_id' => $questionId,
        ]);

        $storeResponse = $mediaController->store($request);
        $payload = $storeResponse->getData(true);
        $mediaId = $payload['data']['id'] ?? null;
        abort_unless(is_string($mediaId) && $mediaId !== '', 500, 'Media upload did not return an asset identifier.');

        $attachRequest = Request::create($request->url(), 'POST', [
            'media_id' => $mediaId,
            'role' => $request->input('role', 'STEM'),
            'sequence' => $request->integer('sequence', 0),
        ]);
        $attachRequest->setUserResolver(fn () => $request->user());

        try {
            $mediaController->attachToQuestionVersion($attachRequest, $questionId, $versionId);
        } catch (Throwable $exception) {
            // Do not leave an orphaned private upload when attachment is rejected.
            $asset = MediaAsset::query()
                ->where('owner_type', 'QUESTION')
                ->where('owner_id', $questionId)
                ->where('uploaded_by', $request->user()->id)
                ->find($mediaId);

            if ($asset) {
                $cleanupRequest = Request::create($request->url(), 'DELETE');
                $cleanupRequest->setUserResolver(fn () => $request->user());

                try {
                    $mediaController->destroy($cleanupRequest, $asset);
                } catch (Throwable) {
                    // Preserve the original attachment failure for the caller.
                }
            }

            throw $exception;
        }

        return redirect()
            ->route('teacher.questions.index', ['edit_question' => $questionId])
            ->with('message', 'Media berhasil diunggah dan dipasang pada versi draft soal.');
    }

    public function detach(
        Request $request,
        string $questionId,
        string $versionId,
        string $mediaId,
        MediaController $mediaController
    ): RedirectResponse {
        $detachRequest = Request::create($request->url(), 'DELETE');
        $detachRequest->setUserResolver(fn () => $request->user());

        $mediaController->detachFromQuestionVersion($detachRequest, $questionId, $versionId, $mediaId);

        return redirect()
            ->route('teacher.questions.index', ['edit_question' => $questionId])
            ->with('message', 'Media dilepas dari versi draft soal.');
    }
}
