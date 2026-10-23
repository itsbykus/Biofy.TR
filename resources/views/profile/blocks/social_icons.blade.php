@if (! empty($socialLinks))
    <ul class="bp-social bp-social--{{ $b['size'] ?? 'md' }}">
        @foreach ($socialLinks as $link)
            <li>
                <a href="{{ $link['url'] }}" target="_blank" rel="noopener me" aria-label="{{ __('social.platforms.'.$link['platform']) }}">
                    <x-brand-icon :platform="$link['platform']" :label="__('social.platforms.'.$link['platform'])" />
                </a>
            </li>
        @endforeach
    </ul>
@endif
