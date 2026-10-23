<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\CustomDomain;
use App\Models\MediaFile;
use App\Models\SecurityEvent;
use App\Services\AuditLogger;
use App\Services\Domains\DomainService;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Media library, custom domains, contact inbox and logs. */
class OperationsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function media(Request $request, MediaService $media): View
    {
        $files = MediaFile::query()->with('user:id,username')
            ->when($request->filled('purpose'), fn ($q) => $q->where('purpose', $request->query('purpose')))
            ->when($request->filled('user'), fn ($q) => $q->whereHas('user', fn ($u) => $u->where('username', $request->query('user'))))
            ->latest('id')->paginate(40)->withQueryString();

        $perUser = MediaFile::query()->whereNotNull('user_id')->selectRaw('user_id, SUM(size) as bytes, COUNT(*) as files')
            ->groupBy('user_id')->orderByDesc('bytes')->limit(10)->with('user:id,username')->get();

        return view('admin.operations.media', [
            'files' => $files,
            'perUser' => $perUser,
            'totalBytes' => (int) MediaFile::query()->sum('size'),
            'inUse' => fn (MediaFile $f) => $media->isInUse($f),
        ]);
    }

    public function destroyMedia(MediaFile $media, MediaService $service): RedirectResponse
    {
        if ($service->isInUse($media)) {
            return back()->with('error', __('media.in_use'));
        }

        $this->audit->log('media.deleted', $media, ['path' => $media->path, 'owner' => $media->user_id]);
        $service->delete($media);

        return back()->with('success', __('media.deleted'));
    }

    public function domains(Request $request): View
    {
        return view('admin.operations.domains', [
            'domains' => CustomDomain::query()->with(['user:id,username', 'profile:id,slug'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
                ->latest()->paginate(30)->withQueryString(),
        ]);
    }

    public function disableDomain(Request $request, CustomDomain $domain, DomainService $domains): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $domains->disable($domain, $data['reason']);
        $this->audit->log('domain.disabled', $domain, ['hostname' => $domain->hostname, 'reason' => $data['reason']]);

        return back()->with('success', __('admin.saved'));
    }

    public function enableDomain(CustomDomain $domain, DomainService $domains): RedirectResponse
    {
        $domains->enable($domain);
        $this->audit->log('domain.enabled', $domain, ['hostname' => $domain->hostname]);

        return back()->with('success', __('admin.saved'));
    }

    public function recheckDomain(CustomDomain $domain, DomainService $domains): RedirectResponse
    {
        $domains->verify($domain);

        return back()->with('success', __('admin.domains.rechecked'));
    }

    public function messages(Request $request): View
    {
        return view('admin.operations.messages', [
            'messages' => ContactMessage::query()->with('package')
                ->when($request->query('status') === 'archived',
                    fn ($q) => $q->where('status', 'archived'),
                    fn ($q) => $q->where('status', '!=', 'archived'))
                ->latest()->paginate(30)->withQueryString(),
        ]);
    }

    public function showMessage(ContactMessage $message): View
    {
        if ($message->status === 'new') {
            $message->update(['status' => 'read']);
        }

        return view('admin.operations.message', ['message' => $message->load(['package', 'user'])]);
    }

    public function archiveMessage(ContactMessage $message): RedirectResponse
    {
        $message->update(['status' => $message->status === 'archived' ? 'read' : 'archived']);

        return redirect()->route('admin.messages.index')->with('success', __('admin.saved'));
    }

    public function destroyMessage(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('success', __('admin.deleted'));
    }

    public function audit(Request $request): View
    {
        return view('admin.operations.audit', [
            'logs' => AuditLog::query()->with('actor:id,name,username')
                ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', str_replace(['%', '_'], ['\%', '\_'], (string) $request->query('action')).'%'))
                ->when($request->filled('subject'), fn ($q) => $q->where('subject_type', $request->query('subject')))
                ->latest('id')->paginate(50)->withQueryString(),
        ]);
    }

    public function securityEvents(Request $request): View
    {
        return view('admin.operations.security-events', [
            'events' => SecurityEvent::query()->with('user:id,username')
                ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')))
                ->when($request->filled('user'), fn ($q) => $q->whereHas('user', fn ($u) => $u->where('username', $request->query('user'))))
                ->latest('id')->paginate(50)->withQueryString(),
        ]);
    }
}
