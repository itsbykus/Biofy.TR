@extends('layouts.admin')

@section('title', $package->exists ? $package->translate('name') : __('admin.packages.create'))

@section('content')
    <a href="{{ route('admin.packages.index') }}" class="text-xs font-medium text-zinc-500 hover:text-zinc-900">← {{ __('admin.nav.packages') }}</a>
    <x-page-header class="mt-1" :title="$package->exists ? $package->translate('name') : __('admin.packages.create')">
        <x-slot:actions>
            @if ($package->exists)
                <form method="POST" action="{{ route('admin.packages.archive', $package) }}">@csrf<button class="btn btn-secondary">{{ $package->is_archived ? __('admin.packages.unarchive') : __('admin.packages.archive') }}</button></form>
                <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" data-confirm="{{ __('admin.packages.delete_confirm') }}">@csrf @method('DELETE')<button class="btn btn-danger-ghost">{{ __('common.delete') }}</button></form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ $package->exists ? route('admin.packages.update', $package) : route('admin.packages.store') }}" class="grid gap-6 xl:grid-cols-[1fr_1fr]" novalidate>
        @csrf
        @if ($package->exists) @method('PUT') @endif
        <input type="hidden" name="features_present" value="1">

        <div class="space-y-6">
            <section class="card card-pad space-y-4">
                <h2 class="card-title">{{ __('admin.packages.general') }}</h2>
                <x-form.input name="key" :label="__('admin.packages.key')" :value="$package->key" required :help="__('admin.packages.key_help')" />
                <x-form.translatable name="name" :label="__('admin.packages.name')" :value="$package->name ?? []" :required-tr="true" />
                <x-form.translatable name="description" :label="__('admin.packages.description')" :value="$package->description ?? []" :textarea="true" :rows="2" />
                <x-form.translatable name="marketing_features" :label="__('admin.packages.marketing_features')" :value="['tr' => implode("\n", $package->marketing_features['tr'] ?? []), 'en' => implode("\n", $package->marketing_features['en'] ?? [])]" :textarea="true" :rows="6" :help="__('admin.packages.marketing_features_help')" />
                @if ($customUser)
                    <input type="hidden" name="custom_for_user_id" value="{{ $customUser->id }}">
                    <p class="alert alert-info"><x-icon name="user" class="mt-0.5" /><span>{{ __('admin.packages.custom_for', ['user' => '@'.$customUser->username]) }}</span></p>
                @endif
            </section>

            <section class="card card-pad space-y-4">
                <h2 class="card-title">{{ __('admin.packages.pricing') }}</h2>
                <p class="text-xs text-zinc-500">{{ __('admin.packages.no_billing') }}</p>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-form.input name="price_monthly" type="number" step="0.01" min="0" :label="__('admin.packages.price_monthly')" :value="$package->price_monthly" />
                    <x-form.input name="price_yearly" type="number" step="0.01" min="0" :label="__('admin.packages.price_yearly')" :value="$package->price_yearly" />
                    <x-form.input name="currency" :label="__('admin.packages.currency')" :value="$package->currency ?? 'TRY'" maxlength="3" required />
                </div>
                <x-form.input name="sort_order" type="number" min="0" :label="__('admin.packages.sort')" :value="$package->sort_order ?? 0" />
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-form.checkbox name="is_public" :label="__('admin.packages.show_on_pricing')" :checked="$package->is_public" />
                    <x-form.checkbox name="is_highlighted" :label="__('admin.packages.highlight')" :checked="$package->is_highlighted" />
                    <x-form.checkbox name="is_default" :label="__('admin.packages.is_default')" :checked="$package->is_default" :help="__('admin.packages.is_default_help')" />
                    <x-form.checkbox name="is_contact_only" :label="__('admin.packages.contact_only')" :checked="$package->is_contact_only" :help="__('admin.packages.contact_only_help')" />
                </div>
                <x-form.translatable name="contact_message" :label="__('admin.packages.contact_message')" :value="$package->contact_message ?? []" />
                <x-form.input name="contact_url" :label="__('admin.packages.contact_url')" :value="$package->contact_url" placeholder="/contact?package=premium" :help="__('admin.packages.url_help')" />
            </section>
        </div>

        <section class="card h-fit overflow-x-auto">
            <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4">
                <h2 class="card-title">{{ __('admin.packages.features') }}</h2>
                <span class="text-xs text-zinc-500">{{ __('admin.packages.unlimited_hint') }}</span>
            </div>
            <table class="table">
                <tbody>
                    @foreach ($features as $feature)
                        @php($current = $values[$feature->key] ?? $feature->default_value)
                        <tr>
                            <td>
                                <label for="feat-{{ $loop->index }}" class="font-medium">{{ $feature->translate('name') }}</label>
                                <p class="font-mono text-[11px] text-zinc-500">{{ $feature->key }} @unless ($feature->platform_enabled)<span class="badge badge-red">{{ __('admin.features.platform_disabled') }}</span>@endunless</p>
                            </td>
                            <td class="w-36">
                                @if ($feature->isBoolean())
                                    <select id="feat-{{ $loop->index }}" name="features[{{ $feature->key }}]" class="select min-h-9 py-1">
                                        <option value="1" @selected($current === '1')>{{ __('common.yes') }}</option>
                                        <option value="0" @selected($current !== '1')>{{ __('common.no') }}</option>
                                    </select>
                                @else
                                    <input id="feat-{{ $loop->index }}" name="features[{{ $feature->key }}]" type="number" min="-1" value="{{ $current }}" class="input min-h-9 py-1">
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="flex justify-end gap-2 border-t border-zinc-100 px-5 py-4">
                @unless ($package->exists)
                    <select name="copy_from" class="select max-w-48" aria-label="{{ __('admin.packages.copy_from') }}">
                        <option value="">{{ __('admin.packages.copy_from') }}…</option>
                        @foreach ($otherPackages as $other)<option value="{{ $other->id }}">{{ $other->translate('name') }}</option>@endforeach
                    </select>
                @endunless
                <button class="btn btn-dark">{{ __('common.save') }}</button>
            </div>
        </section>
    </form>

    @if ($package->exists)
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <form method="POST" action="{{ route('admin.packages.copy', $package) }}" class="card card-pad flex flex-col gap-3 sm:flex-row sm:items-end" data-confirm="{{ __('admin.packages.copy_confirm') }}">
                @csrf
                <x-form.select name="from" :label="__('admin.packages.copy_from')" :options="$otherPackages->mapWithKeys(fn ($p) => [$p->id => $p->translate('name')])->all()" class="flex-1" />
                <button class="btn btn-secondary">{{ __('admin.packages.copy') }}</button>
            </form>

            <section class="card">
                <h2 class="card-title border-b border-zinc-100 px-5 py-4">{{ __('admin.packages.history') }}</h2>
                <ul class="max-h-72 divide-y divide-zinc-100 overflow-y-auto text-sm">
                    @forelse ($history as $log)
                        <li class="px-5 py-2"><span class="font-medium">{{ audit_label($log->action) }}</span> · {{ $log->actor?->name ?? __('admin.system_actor') }} <span class="text-xs text-zinc-500">· {{ local_time($log->created_at) }}</span>
                            @if (! empty($log->properties['features']))<p class="font-mono text-[11px] text-zinc-500">{{ collect($log->properties['features'])->map(fn ($v, $k) => $k.'='.$v)->implode(', ') }}</p>@endif
                        </li>
                    @empty
                        <li class="muted px-5 py-4">{{ __('admin.dashboard.no_audit') }}</li>
                    @endforelse
                </ul>
            </section>
        </div>
    @endif
@endsection
