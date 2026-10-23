<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\GenericTemplatedNotification;
use App\Services\AuditLogger;
use App\Services\Mail\Mailer;
use App\Services\Settings;
use App\Services\System\HealthCheck;
use App\Services\System\MaintenanceRunner;
use App\Services\System\ReleaseVerifier;
use App\Services\System\UpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SystemController extends Controller
{
    public function __construct(
        private readonly Settings $settings,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(Request $request, HealthCheck $health, UpdateService $updates): View
    {
        $token = (string) $this->settings->get('cron.token');

        return view('admin.system.index', [
            'checks' => $health->full(),
            'probe' => $request->session()->get('system.probe'),
            'release' => $request->session()->get('system.release'),
            'codeVersion' => $updates->codeVersion(),
            'installedVersion' => $updates->installedVersion(),
            'pendingUpdate' => $updates->isPending(),
            'pendingMigrations' => $updates->pendingMigrations(),
            'cronUrl' => $token !== '' ? url('/cron/'.$token) : null,
            'cronCommand' => 'php '.base_path('artisan').' schedule:run',
            'lastCron' => $this->settings->get('cron.last_run_at'),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'db' => config('database.default'),
        ]);
    }

    public function probe(Request $request, HealthCheck $health): RedirectResponse
    {
        $request->session()->flash('system.probe', $health->exposureProbe());

        return redirect()->route('admin.system.index')->withFragment('probe');
    }

    public function verifyRelease(Request $request, ReleaseVerifier $verifier): RedirectResponse
    {
        $request->session()->flash('system.release', $verifier->verify());

        return redirect()->route('admin.system.index')->withFragment('release');
    }

    public function update(Request $request, UpdateService $updates): RedirectResponse
    {
        $result = $updates->run($request->user(), ! $request->boolean('skip_backup'));

        return redirect()->route('admin.system.index')->with('success', __('system.updated', ['from' => $result['from'] ?? '-', 'to' => $result['to']]));
    }

    public function maintenance(MaintenanceRunner $runner): RedirectResponse
    {
        $report = $runner->run();
        $this->audit->log('system.maintenance_run', null, $report);

        return back()->with('success', __('system.maintenance_done', ['report' => collect($report)->map(fn ($v, $k) => $k.': '.$v)->implode(', ')]));
    }

    public function regenerateCronToken(): RedirectResponse
    {
        $this->settings->set('cron.token', Str::random(40));
        $this->audit->log('system.cron_token_regenerated');

        return back()->with('success', __('system.cron_token_regenerated'));
    }

    public function clearCache(): RedirectResponse
    {
        Cache::flush();
        Artisan::call('view:clear');
        $this->settings->flush();
        $this->audit->log('system.cache_cleared');

        return back()->with('success', __('system.cache_cleared'));
    }

    public function announcement(): View
    {
        return view('admin.system.announcement', ['state' => Cache::get('announcement.mail_state')]);
    }

    /**
     * E-mail announcement sent in batches of 50 per click, so it works inside the
     * time limits of shared hosting without a queue worker.
     */
    public function sendAnnouncement(Request $request, Mailer $mailer): RedirectResponse
    {
        $state = Cache::get('announcement.mail_state');

        if ($request->filled('message')) {
            $data = $request->validate(['message' => ['required', 'string', 'max:3000'], 'audience' => ['required', 'in:all,verified']]);
            $state = ['message' => $data['message'], 'audience' => $data['audience'], 'cursor' => 0, 'sent' => 0, 'failed' => 0, 'started_at' => now()->toIso8601String()];
            $this->audit->log('announcement.started', null, ['audience' => $data['audience'], 'length' => mb_strlen($data['message'])]);
        }

        abort_if(! is_array($state), 422);

        $users = User::query()->where('status', User::STATUS_ACTIVE)->whereNull('deletion_requested_at')
            ->when($state['audience'] === 'verified', fn ($q) => $q->whereNotNull('email_verified_at'))
            ->where('id', '>', $state['cursor'])->orderBy('id')->limit(50)->get();

        foreach ($users as $user) {
            $mailer->send($user, new GenericTemplatedNotification('announcement', ['message' => $state['message']])) ? $state['sent']++ : $state['failed']++;
            $state['cursor'] = $user->id;
        }

        $remaining = User::query()->where('status', User::STATUS_ACTIVE)->whereNull('deletion_requested_at')
            ->when($state['audience'] === 'verified', fn ($q) => $q->whereNotNull('email_verified_at'))
            ->where('id', '>', $state['cursor'])->count();
        $state['remaining'] = $remaining;

        if ($remaining === 0) {
            Cache::forget('announcement.mail_state');
            $this->audit->log('announcement.finished', null, ['sent' => $state['sent'], 'failed' => $state['failed']]);

            return redirect()->route('admin.announcement.create')->with('success', __('admin.announcement.finished', ['sent' => $state['sent'], 'failed' => $state['failed']]));
        }

        Cache::put('announcement.mail_state', $state, now()->addDay());

        return redirect()->route('admin.announcement.create')->with('warning', __('admin.announcement.progress', ['sent' => $state['sent'], 'remaining' => $remaining]));
    }
}
