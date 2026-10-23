@can('users.manage')
    <div class="flex flex-wrap items-center gap-2">
        <form method="POST" action="{{ route('admin.profiles.status', $profile) }}" class="flex items-center gap-1.5">
            @csrf
            <label class="sr-only" for="ps-{{ $profile->id }}">{{ __('common.status_label') }}</label>
            <select id="ps-{{ $profile->id }}" name="status" class="select min-h-8 w-36 py-1 text-xs">
                @foreach (['draft', 'published', 'suspended', 'archived'] as $status)
                    <option value="{{ $status }}" @selected($profile->status === $status) @disabled($status === 'published' && ! $profile->published_version_id)>{{ __('common.status.'.$status) }}</option>
                @endforeach
            </select>
            <input name="reason" class="input min-h-8 w-40 py-1 text-xs" placeholder="{{ __('admin.profiles.reason') }}" aria-label="{{ __('admin.profiles.reason') }}">
            <button class="btn btn-secondary btn-sm">{{ __('common.save') }}</button>
        </form>
        <form method="POST" action="{{ route('admin.profiles.verified', $profile) }}">@csrf
            <button class="btn btn-ghost btn-sm">{{ $profile->verified_at ? __('admin.profiles.unverify') : __('admin.profiles.verify') }}</button>
        </form>
    </div>
@endcan
