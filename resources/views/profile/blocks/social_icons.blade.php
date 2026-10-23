@if (! empty($socialLinks))
    <ul class="bp-social bp-social--{{ $b['size'] ?? 'md' }}">
        @foreach ($socialLinks as $link)
            <li>
                <a href="{{ $link['url'] }}" class="br-{{ $link['platform'] }}" @unless (str_starts_with($link['url'], 'mailto:')) target="_blank" @endunless rel="noopener me" aria-label="{{ __('social.platforms.'.$link['platform']) }}">
                    <x-brand-icon :platform="$link['platform']" :label="__('social.platforms.'.$link['platform'])" />
                </a>
            </li>
        @endforeach
    </ul>
@endif
