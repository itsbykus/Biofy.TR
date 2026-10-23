<x-form.input name="data[label]" :label="__('blocks.fields.label')" :value="$data['label'] ?? ''" maxlength="100" :placeholder="__('blocks.types.contact.default_label')" />
<p class="help">{{ __('blocks.types.contact.help') }} <a href="{{ route('pages.edit', ['profile' => $profile, 'tab' => 'profile']) }}" class="font-medium underline">{{ __('editor.tabs.profile') }}</a></p>
