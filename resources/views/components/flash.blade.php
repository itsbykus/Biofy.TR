@props(['hideErrors' => false])
@php($types = ['success' => ['alert-success', 'circle-check'], 'error' => ['alert-error', 'circle-x'], 'warning' => ['alert-warning', 'triangle-alert'], 'status' => ['alert-info', 'info']])
<div {{ $attributes->merge(['class' => 'space-y-2']) }} aria-live="polite">
    @foreach ($types as $key => [$class, $icon])
        @if (session($key))
            <div class="alert {{ $class }}" role="{{ $key === 'error' ? 'alert' : 'status' }}" data-dismissable>
                <x-icon :name="$icon" class="mt-0.5 size-4" />
                <p class="flex-1">{{ session($key) }}</p>
                <button type="button" class="opacity-60 hover:opacity-100" data-dismiss aria-label="{{ __('common.close') }}"><x-icon name="x" /></button>
            </div>
        @endif
    @endforeach
    @if ($errors->any() && ! $hideErrors)
        <div class="alert alert-error" role="alert">
            <x-icon name="circle-x" class="mt-0.5 size-4" />
            <div class="flex-1">
                <p class="font-medium">{{ __('common.fix_errors') }}</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach (array_slice($errors->all(), 0, 5) as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>
