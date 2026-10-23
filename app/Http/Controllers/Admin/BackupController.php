<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupRecord;
use App\Services\AuditLogger;
use App\Services\Backup\BackupService;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        private readonly BackupService $backups,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(Settings $settings): View
    {
        return view('admin.backups.index', [
            'records' => BackupRecord::query()->with('creator:id,name')->latest('id')->paginate(20),
            'encrypt' => $settings->bool('backups.encrypt'),
            'schedule' => $settings->get('backups.schedule'),
            'lastBackup' => $settings->get('system.last_backup_at'),
            'directory' => $this->backups->directory(),
            'maxUpload' => ini_get('upload_max_filesize'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $record = $this->backups->create('manual', $request->user());
        $this->audit->log('backup.created', $record, ['file' => $record->filename, 'status' => $record->status]);

        return $record->status === 'completed'
            ? back()->with('success', __('backups.created', ['file' => $record->filename]))
            : back()->with('error', __('backups.failed', ['error' => $record->error]));
    }

    public function verify(BackupRecord $backup): RedirectResponse
    {
        $result = $this->backups->verify($backup);

        return $result['ok']
            ? back()->with('success', __('backups.verified'))
            : back()->with('error', implode(' ', $result['errors']));
    }

    public function download(BackupRecord $backup): BinaryFileResponse
    {
        $path = $this->backups->path($backup);
        abort_unless($backup->status === 'completed' && is_file($path), 404);
        $this->audit->log('backup.downloaded', $backup, ['file' => $backup->filename]);

        return response()->download($path, $backup->filename, ['Content-Type' => 'application/octet-stream']);
    }

    public function restore(Request $request, BackupRecord $backup): RedirectResponse
    {
        $request->validate(['confirm' => ['required', 'in:RESTORE,GERİ YÜKLE,GERI YUKLE']], ['confirm.in' => __('backups.restore_confirm_mismatch')]);
        $actor = $request->user();
        $safety = $this->backups->restore($backup, $actor);

        $this->audit->log('backup.restored', $backup, ['file' => $backup->filename, 'safety_backup' => $safety->filename, 'actor_email_hash' => hash('sha256', $actor->email)], null);

        // Sessions were part of the replaced data set: sign in again.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', __('backups.restored', ['safety' => $safety->filename]));
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file']]);
        $file = $request->file('file');
        $record = $this->backups->import($file->getRealPath(), $file->getClientOriginalName(), $request->user());
        $this->audit->log('backup.uploaded', $record, ['file' => $record->filename]);

        return back()->with('success', __('backups.uploaded'));
    }

    public function destroy(BackupRecord $backup): RedirectResponse
    {
        $this->audit->log('backup.deleted', $backup, ['file' => $backup->filename]);
        $this->backups->delete($backup);

        return back()->with('success', __('admin.deleted'));
    }
}
