@extends('layouts.app')

@section('title', __('nav.api'))

@section('content')
    <x-page-header :title="__('nav.api')" :description="__('api.help')" />

    @if (! $allowed)
        <x-empty icon="code" :title="__('api.locked_title')" :text="__('api.locked_text')">
            <a href="{{ route('plan.show') }}" class="btn btn-primary">{{ __('plans.see_plans') }}</a>
        </x-empty>
    @else
        @if ($newToken)
            <div class="alert alert-success mb-6" role="alert">
                <x-icon name="key-round" class="mt-0.5" />
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ __('api.token_created') }}</p>
                    <p class="text-xs">{{ __('api.token_once') }}</p>
                    <div class="mt-2 flex items-center gap-2"><code class="code min-w-0 flex-1 break-all">{{ $newToken }}</code><button type="button" class="btn btn-secondary btn-sm" data-copy="{{ $newToken }}" data-copied="{{ __('common.copied') }}">{{ __('common.copy') }}</button></div>
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <div class="space-y-6">
                <section class="card">
                    <h2 class="card-title border-b border-zinc-100 px-5 py-4">{{ __('api.tokens') }}</h2>
                    @if ($tokens->isEmpty())
                        <p class="muted p-5">{{ __('api.no_tokens') }}</p>
                    @else
                        <ul class="divide-y divide-zinc-100">
                            @foreach ($tokens as $token)
                                <li class="flex flex-col gap-2 px-5 py-3 sm:flex-row sm:items-center">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium">{{ $token->name }}</p>
                                        <p class="text-xs text-zinc-500">{{ implode(', ', $token->abilities) }}</p>
                                        <p class="text-xs text-zinc-500">{{ __('api.last_used', ['time' => $token->last_used_at ? local_time($token->last_used_at) : __('common.never')]) }} · {{ __('api.expires', ['time' => $token->expires_at ? local_time($token->expires_at, 'd.m.Y') : __('common.never')]) }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('api-tokens.destroy', $token->id) }}" data-confirm="{{ __('api.revoke_confirm') }}">@csrf @method('DELETE')<button class="btn btn-danger-ghost btn-sm">{{ __('api.revoke') }}</button></form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="card card-pad">
                    <h2 class="card-title">{{ __('api.quickstart') }}</h2>
                    <p class="muted mt-1">{{ __('api.quickstart_help', ['limit' => limit_label($rateLimit)]) }}</p>
                    <pre class="mt-3 overflow-x-auto rounded-lg bg-ink p-4 text-xs text-zinc-100"><code>curl -H "Authorization: Bearer &lt;TOKEN&gt;" \
     -H "Accept: application/json" \
     {{ url('/api/v1/profiles') }}</code></pre>
                    <p class="mt-3 text-xs text-zinc-500">{{ __('api.docs_hint') }}</p>
                </section>

                @if ($recent->isNotEmpty())
                    <section class="card">
                        <h2 class="card-title border-b border-zinc-100 px-5 py-4">{{ __('api.recent') }}</h2>
                        <div class="overflow-x-auto">
                            <table class="table text-xs">
                                <tbody>
                                    @foreach ($recent as $log)
                                        <tr><td class="font-mono">{{ $log->method }}</td><td class="font-mono">{{ $log->path }}</td><td><span class="badge {{ $log->status < 400 ? 'badge-green' : 'badge-red' }}">{{ $log->status }}</span></td><td class="text-zinc-500">{{ $log->duration_ms }} ms</td><td class="whitespace-nowrap text-zinc-500">{{ local_time($log->created_at) }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
            </div>

            <form method="POST" action="{{ route('api-tokens.store') }}" class="card card-pad h-fit space-y-4" novalidate>
                @csrf
                <h2 class="card-title">{{ __('api.create') }}</h2>
                <x-form.input name="name" :label="__('api.token_name')" maxlength="60" required :placeholder="__('api.token_name_placeholder')" />
                <fieldset>
                    <legend class="label">{{ __('api.abilities') }}</legend>
                    <div class="space-y-2">
                        @foreach ($abilities as $ability)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="abilities[]" value="{{ $ability }}" class="checkbox" @checked(str_ends_with($ability, ':read'))><span class="font-mono text-xs">{{ $ability }}</span> <span class="text-zinc-500">— {{ __('api.ability.'.str_replace(':', '_', $ability)) }}</span></label>
                        @endforeach
                    </div>
                    @error('abilities')<p class="error">{{ $message }}</p>@enderror
                </fieldset>
                <x-form.select name="expires_in_days" :label="__('api.expiry')" value="90" :placeholder="__('api.no_expiry')" :options="['30' => __('api.days', ['n' => 30]), '90' => __('api.days', ['n' => 90]), '180' => __('api.days', ['n' => 180]), '365' => __('api.days', ['n' => 365])]" />
                <button class="btn btn-dark w-full">{{ __('api.create') }}</button>
                <p class="text-xs text-zinc-500">{{ __('api.usage', ['used' => $tokens->count(), 'limit' => limit_label($limit)]) }}</p>
            </form>
        </div>
    @endif
@endsection
