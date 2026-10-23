<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div class="text-sm text-zinc-500">
        {{ __('editor.usage_links', ['used' => $usage['links'], 'limit' => limit_label($limits['links'])]) }} ·
        {{ __('editor.usage_blocks', ['used' => $usage['blocks'], 'limit' => limit_label($limits['blocks'])]) }}
    </div>
    <button type="button" class="btn btn-dark" data-dialog-open="#block-picker" @disabled($profile->status === 'suspended')><x-icon name="plus" />{{ __('editor.add_block') }}</button>
</div>

<div data-fragment="blocks-list">
    @include('dashboard.pages.partials.blocks-list')
</div>
