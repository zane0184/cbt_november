<?php

namespace App\Http\Controllers\Teacher;

use App\Exports\QuestionImport\QuestionImportTemplateExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class QuestionImportTemplateController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        // The route is protected by auth, tenant, license, and exact-role TeacherPortalAccess.
        abort_unless($request->user() && $request->user()->tenant_id, 403);

        $response = Excel::download(
            new QuestionImportTemplateExport(),
            'Template_Import_Soal_CBT-QUESTION-1.xlsx'
        );

        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
