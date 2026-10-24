{{-- One post in a grid. $featured: wide card for the newest post on the first page. --}}
<article @class(['group relative flex flex-col overflow-hidden rounded-3xl border border-zinc-200 bg-white transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-zinc-900/5', 'md:col-span-2 lg:col-span-3 lg:flex-row' => $featured ?? false])>
    <div @class(['relative aspect-[16/9] shrink-0 overflow-hidden bg-zinc-100', 'lg:aspect-auto lg:w-3/5' => $featured ?? false])>
        @if ($post->cover)
            <img src="{{ $post->cover->url() }}" alt="" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-[1.03]">
        @else
            <div class="landing-grid absolute inset-0 bg-brand-soft"></div>
            <span class="absolute inset-0 grid place-items-center"><span class="grid size-14 place-items-center rounded-2xl bg-brand text-ink shadow-lg"><x-icon name="newspaper" class="size-6" /></span></span>
        @endif
    </div>
    <div @class(['flex flex-1 flex-col p-6', 'lg:justify-center lg:p-10' => $featured ?? false])>
        <p class="flex items-center gap-2 text-xs font-medium text-zinc-500">
            <time datetime="{{ $post->published_at->toIso8601String() }}">{{ local_time($post->published_at, 'd F Y') }}</time>
            <span aria-hidden="true">·</span>
            <span>{{ __('site.blog.reading_time', ['minutes' => $post->readingMinutes()]) }}</span>
        </p>
        <h2 @class(['mt-3 font-bold tracking-tight text-zinc-900 text-balance', 'text-2xl lg:text-3xl' => $featured ?? false, 'text-lg' => ! ($featured ?? false)])>
            <a href="{{ $post->url() }}" class="after:absolute after:inset-0">{{ $post->translate('title') }}</a>
        </h2>
        @if ($post->translate('excerpt'))
            <p @class(['mt-2 leading-relaxed text-zinc-600', 'text-base' => $featured ?? false, 'line-clamp-3 text-sm' => ! ($featured ?? false)])>{{ $post->translate('excerpt') }}</p>
        @endif
        <span class="mt-auto inline-flex items-center gap-1.5 pt-5 text-sm font-semibold text-zinc-900">{{ __('site.blog.read_more') }}<x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" /></span>
    </div>
</article>
