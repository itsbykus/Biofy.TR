<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ $site['name'] ?? 'Biofy.TR' }}</title>
    @php
        try {
            echo app(\Illuminate\Foundation\Vite::class)(['resources/css/app.css'])->toHtml();
        } catch (\Throwable) {
            // Assets unavailable: the page still renders (unstyled) rather than failing again.
        }
    @endphp
</head>
<body class="flex min-h-screen items-center justify-center bg-zinc-50 px-4 text-zinc-900">
    <main class="w-full max-w-md text-center">
        <p class="inline-grid size-14 place-items-center rounded-2xl bg-brand text-2xl font-black text-ink" aria-hidden="true">@yield('code', '!')</p>
        <h1 class="mt-6 text-2xl font-bold tracking-tight">@yield('heading')</h1>
        <p class="mt-3 text-zinc-600">@yield('message')</p>
        @hasSection('actions')
            <div class="mt-8 flex justify-center gap-2">@yield('actions')</div>
        @endif
    </main>
</body>
</html>
