<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Package;
use App\Models\Page;
use App\Notifications\GenericTemplatedNotification;
use App\Services\Mail\Mailer;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Contact form; also used for "request a package" since there is no checkout in v1. */
class ContactController extends Controller
{
    public function __construct(
        private readonly Settings $settings,
        private readonly Mailer $mailer,
    ) {
    }

    public function show(Request $request): View
    {
        $package = $request->filled('package') ? Package::query()->available()->where('key', $request->query('package'))->first() : null;

        return view('site.contact', [
            'page' => Page::query()->where('slug', 'contact')->where('is_published', true)->first(),
            'package' => $package,
            'packages' => Package::query()->onPricingPage()->get(),
            'supportEmail' => (string) $this->settings->get('site.support_email'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: real users never fill this hidden field.
        if ($request->filled('website')) {
            return back()->with('success', __('contact.sent'));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:191'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'package_id' => ['nullable', 'integer', Rule::exists('packages', 'id')->where('is_archived', false)],
        ]);

        $message = ContactMessage::query()->create($data + [
            'type' => ! empty($data['package_id']) ? 'package' : 'general',
            'user_id' => $request->user()?->id,
            'status' => 'new',
        ]);

        $support = (string) $this->settings->get('site.support_email');

        if ($support !== '' && filter_var($support, FILTER_VALIDATE_EMAIL)) {
            $notifiable = (new AnonymousNotifiable)->route('mail', $support);
            $this->mailer->send($notifiable, new GenericTemplatedNotification('contact_received', [
                'message' => Str::limit($message->subject.' — '.$message->name.' <'.$message->email.'>', 300),
            ], route('admin.messages.show', $message)));
        }

        return back()->with('success', __('contact.sent'));
    }
}
