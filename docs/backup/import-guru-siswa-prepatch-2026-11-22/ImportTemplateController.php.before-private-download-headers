<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Import\ImportTemplateExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportTemplateController extends Controller
{
    public function download(Request $request, string $type): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user && $user->tenant_id && $user->hasRole('ADMIN_MADRASAH') && $user->hasPermission('user.manage'), 403);
        $type = strtoupper($type);
        abort_unless(in_array($type, ['GURU', 'SISWA'], true), 404);
        $filename = $type === 'GURU' ? 'Template Import Guru.xlsx' : 'Template Import Siswa.xlsx';
        return Excel::download(new ImportTemplateExport((string) $user->tenant_id, $type), $filename);
    }
}
