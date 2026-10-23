@props(['name', 'label', 'value' => [], 'textarea' => false, 'rows' => 3, 'help' => null, 'requiredTr' => false])
{{-- Turkish is the default language; English is optional and falls back to Turkish when empty. --}}
<fieldset {{ $attributes->merge(['class' => '']) }}>
    <legend class="label">{{ $label }}</legend>
    <div class="grid gap-3 md:grid-cols-2">
        @foreach (['tr' => 'Türkçe', 'en' => 'English'] as $locale => $localeLabel)
            @php($field = $name.'['.$locale.']')
            @if ($textarea)
                <x-form.textarea :name="$field" :value="$value[$locale] ?? ''" :rows="$rows" :aria-label="$label.' ('.$localeLabel.')'" :placeholder="$localeLabel" :required="$requiredTr && $locale === 'tr'" />
            @else
                <x-form.input :name="$field" :value="$value[$locale] ?? ''" :aria-label="$label.' ('.$localeLabel.')'" :placeholder="$localeLabel" :required="$requiredTr && $locale === 'tr'" />
            @endif
        @endforeach
    </div>
    @if ($help)<p class="help">{{ $help }}</p>@endif
</fieldset>
