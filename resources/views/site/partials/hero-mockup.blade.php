{{--
    Landing hero visual: a phone showing a sample bio page, drawn in HTML so it stays sharp,
    translatable and CSP-safe (no inline styles). Replaced by an uploaded image when
    Admin → Settings → Home page → "Hero image" is set.
--}}
@if ($site['hero_image'])
    <div class="relative mx-auto w-full max-w-xl">
        <img src="{{ $site['hero_image'] }}" alt="" class="h-auto w-full rounded-[2rem] object-contain drop-shadow-2xl">
    </div>
@else
    <div class="relative mx-auto h-[36rem] w-full max-w-[34rem] select-none sm:h-[40rem]" aria-hidden="true">
        {{-- Glow and orbit rings behind the device --}}
        <div class="absolute top-1/2 left-1/2 size-[30rem] -translate-1/2 rounded-full bg-linear-to-br from-brand/50 via-amber-200/40 to-sky-200/50 blur-3xl"></div>
        <div @class(['absolute top-1/2 left-1/2 size-[27rem] -translate-1/2 rounded-full border', 'border-zinc-900/5' => ! $dark, 'border-white/10' => $dark])></div>
        <div @class(['absolute top-1/2 left-1/2 size-[36rem] -translate-1/2 rounded-full border border-dashed', 'border-zinc-900/[0.07]' => ! $dark, 'border-white/10' => $dark])></div>

        {{-- Phone --}}
        <div class="absolute top-4 left-1/2 w-[16.5rem] -translate-x-1/2 sm:top-6 sm:w-[18rem]">
            <div class="rounded-[3rem] bg-linear-to-b from-zinc-700 to-zinc-900 p-[3px] shadow-[0_50px_100px_-30px_rgb(0_0_0/0.55)]">
                <div class="rounded-[2.85rem] bg-ink p-2.5">
                    <div class="relative overflow-hidden rounded-[2.3rem] bg-[#fbf8f1]">
                        <span class="absolute top-2.5 left-1/2 z-10 h-6 w-24 -translate-x-1/2 rounded-full bg-ink"></span>

                        {{-- Cover + profile --}}
                        <div class="h-28 bg-linear-to-br from-amber-300 via-brand to-orange-400">
                            <div class="landing-grid h-full opacity-60"></div>
                        </div>
                        <div class="-mt-11 flex flex-col items-center px-5 text-center">
                            <div class="grid size-[5.25rem] place-items-center rounded-full bg-linear-to-br from-zinc-800 to-ink text-2xl font-black text-brand ring-4 ring-[#fbf8f1]">DY</div>
                            <p class="mt-3 flex items-center gap-1 text-[15px] font-bold text-zinc-900">{{ __('site.home.mock_name') }}<x-icon name="badge-check" class="size-4 text-sky-500" /></p>
                            <p class="mt-0.5 text-[11px] font-semibold tracking-wide text-amber-700 uppercase">{{ __('site.home.mock_title') }}</p>
                            <p class="mt-2 text-[12px] leading-snug text-zinc-600">{{ __('site.home.mock_bio') }}</p>
                            <div class="mt-3 flex items-center gap-2 text-zinc-800">
                                @foreach (['instagram', 'tiktok', 'youtube', 'spotify'] as $platform)
                                    <span class="grid size-8 place-items-center rounded-full bg-white shadow-sm ring-1 ring-zinc-900/5"><x-brand-icon :platform="$platform" class="size-3.5" /></span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Links --}}
                        <div class="mt-4 space-y-2 px-4 pb-5">
                            <div class="flex items-center gap-3 rounded-2xl bg-ink p-2 pr-3 text-white shadow-lg shadow-zinc-900/20">
                                <span class="size-10 shrink-0 rounded-xl bg-linear-to-br from-orange-300 via-rose-400 to-fuchsia-500"></span>
                                <span class="min-w-0 flex-1 text-left">
                                    <span class="block truncate text-[12px] font-semibold">{{ __('site.home.mock_link_0') }}</span>
                                    <span class="block truncate text-[10px] text-zinc-400">{{ __('site.home.mock_link_0_sub') }}</span>
                                </span>
                                <x-icon name="arrow-right" class="size-3.5 text-brand" />
                            </div>
                            @foreach ([1, 2] as $i)
                                <div class="rounded-2xl bg-white px-3 py-3 text-center text-[12px] font-semibold text-zinc-900 shadow-sm ring-1 ring-zinc-900/5">{{ __('site.home.mock_link_'.$i) }}</div>
                            @endforeach
                            <div class="flex items-center gap-2.5 rounded-2xl bg-white p-2 shadow-sm ring-1 ring-zinc-900/5">
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-[#1db954] text-white"><x-brand-icon platform="spotify" class="size-4" /></span>
                                <span class="min-w-0 flex-1 text-left">
                                    <span class="block truncate text-[11px] font-semibold text-zinc-900">{{ __('site.home.mock_music') }}</span>
                                    <span class="mt-1 block h-1 overflow-hidden rounded-full bg-zinc-200"><span class="block h-full w-2/5 rounded-full bg-zinc-900"></span></span>
                                </span>
                                <span class="grid size-7 place-items-center rounded-full bg-zinc-900 text-white"><x-icon name="play" class="size-3" /></span>
                            </div>
                            <p class="pt-1 text-center text-[9px] font-semibold text-zinc-400"><span class="mr-1 inline-block size-2 rounded-[3px] bg-brand align-middle"></span>{{ $site['name'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Analytics card --}}
        <div class="landing-float absolute top-14 -left-2 hidden w-52 rounded-2xl bg-white/95 p-4 text-zinc-900 shadow-[0_20px_50px_-15px_rgb(0_0_0/0.25)] ring-1 ring-zinc-900/5 backdrop-blur sm:block">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold tracking-wide text-zinc-500 uppercase">{{ __('site.home.mock_week') }}</span>
                <span class="inline-flex items-center gap-0.5 rounded-full bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700"><x-icon name="trending-up" class="size-3" />38%</span>
            </div>
            <p class="mt-1 text-2xl font-black tracking-tight tabular-nums">{{ num(12480) }}</p>
            <p class="text-[11px] text-zinc-500">{{ __('site.home.mock_views') }}</p>
            <svg viewBox="0 0 160 44" class="mt-2 h-11 w-full" fill="none">
                <defs>
                    <linearGradient id="hero-spark" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#ffcf00" stop-opacity="0.55" />
                        <stop offset="1" stop-color="#ffcf00" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <path d="M0 36 L18 30 L36 33 L54 22 L72 26 L90 15 L108 19 L126 8 L144 11 L160 3 L160 44 L0 44 Z" fill="url(#hero-spark)" />
                <path d="M0 36 L18 30 L36 33 L54 22 L72 26 L90 15 L108 19 L126 8 L144 11 L160 3" stroke="#111111" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                <circle cx="160" cy="3" r="3" fill="#111111" />
            </svg>
        </div>

        {{-- Live click --}}
        <div class="landing-float-late absolute top-28 -right-4 hidden items-center gap-3 rounded-2xl bg-white/95 py-2.5 pr-4 pl-2.5 text-zinc-900 shadow-[0_20px_50px_-15px_rgb(0_0_0/0.25)] ring-1 ring-zinc-900/5 backdrop-blur sm:flex">
            <span class="relative grid size-9 place-items-center rounded-xl bg-brand text-ink">
                <x-icon name="mouse-pointer-click" class="size-4" />
                <span class="absolute -top-0.5 -right-0.5 size-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
            </span>
            <span>
                <span class="block text-[12px] font-bold">{{ __('site.home.mock_click') }}</span>
                <span class="block text-[11px] text-zinc-500">{{ __('site.home.mock_link_1') }} · {{ __('site.home.mock_now') }}</span>
            </span>
        </div>

        {{-- Custom domain --}}
        <div class="landing-float absolute bottom-24 -left-4 hidden items-center gap-2.5 rounded-2xl bg-ink py-2.5 pr-4 pl-2.5 text-white shadow-[0_20px_50px_-15px_rgb(0_0_0/0.45)] sm:flex">
            <span class="grid size-8 place-items-center rounded-xl bg-white/10"><x-icon name="lock" class="size-4 text-emerald-400" /></span>
            <span>
                <span class="block font-mono text-[12px] font-semibold">deniz.studio</span>
                <span class="block text-[10px] text-zinc-400">{{ __('site.home.chip_domain') }}</span>
            </span>
        </div>

        {{-- Theme picker --}}
        <div class="landing-float-late absolute right-4 bottom-0 hidden items-center gap-3 rounded-2xl bg-white/95 px-3.5 py-2.5 text-zinc-900 shadow-[0_20px_50px_-15px_rgb(0_0_0/0.25)] ring-1 ring-zinc-900/5 backdrop-blur sm:flex">
            <span class="text-[11px] font-semibold text-zinc-500">{{ __('site.home.mock_theme') }}</span>
            <span class="flex -space-x-1.5">
                <span class="size-6 rounded-full bg-brand ring-2 ring-white"></span>
                <span class="size-6 rounded-full bg-ink ring-2 ring-white"></span>
                <span class="size-6 rounded-full bg-linear-to-br from-fuchsia-500 to-indigo-500 ring-2 ring-white"></span>
                <span class="size-6 rounded-full bg-emerald-400 ring-2 ring-white"></span>
            </span>
        </div>
    </div>
@endif
