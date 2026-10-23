<x-form.input name="data[title]" :label="__('blocks.fields.section_title')" :value="$data['title'] ?? ''" maxlength="100" />
<div data-repeater data-max="20">
    <p class="label">{{ __('blocks.fields.questions') }}</p>
    <div class="space-y-3" data-repeater-list>
        @foreach (($data['items'] ?? [['q' => '', 'a' => '']]) as $i => $item)
            <div class="space-y-2 rounded-lg border border-zinc-200 p-3" data-repeater-item>
                <input class="input" name="data[items][{{ $i }}][q]" value="{{ $item['q'] }}" placeholder="{{ __('blocks.fields.question') }}" maxlength="200" aria-label="{{ __('blocks.fields.question') }}">
                <textarea class="textarea min-h-16" name="data[items][{{ $i }}][a]" rows="2" placeholder="{{ __('blocks.fields.answer') }}" maxlength="1500" aria-label="{{ __('blocks.fields.answer') }}">{{ $item['a'] }}</textarea>
                <button type="button" class="btn btn-danger-ghost btn-sm" data-repeater-remove>{{ __('common.remove') }}</button>
            </div>
        @endforeach
    </div>
    <template data-repeater-template>
        <div class="space-y-2 rounded-lg border border-zinc-200 p-3" data-repeater-item>
            <input class="input" name="data[items][__i__][q]" placeholder="{{ __('blocks.fields.question') }}" maxlength="200" aria-label="{{ __('blocks.fields.question') }}">
            <textarea class="textarea min-h-16" name="data[items][__i__][a]" rows="2" placeholder="{{ __('blocks.fields.answer') }}" maxlength="1500" aria-label="{{ __('blocks.fields.answer') }}"></textarea>
            <button type="button" class="btn btn-danger-ghost btn-sm" data-repeater-remove>{{ __('common.remove') }}</button>
        </div>
    </template>
    <button type="button" class="btn btn-secondary btn-sm mt-3" data-repeater-add><x-icon name="plus" class="size-3.5" />{{ __('blocks.add_question') }}</button>
    <p class="error" data-error-for="data.items" hidden></p>
</div>
