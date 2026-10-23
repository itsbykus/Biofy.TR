<x-form.textarea name="data[text]" :label="__('blocks.fields.text')" :value="$data['text'] ?? ''" rows="5" maxlength="2000" required :help="__('blocks.help.plain_text')" />
<x-form.select name="data[align]" :label="__('blocks.fields.align')" :value="$data['align'] ?? 'center'" :options="['center' => __('blocks.align.center'), 'left' => __('blocks.align.left')]" />
