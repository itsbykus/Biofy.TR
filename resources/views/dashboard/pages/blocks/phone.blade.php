<x-form.input name="data[phone]" type="tel" :label="__('blocks.fields.phone')" :value="$data['phone'] ?? ''" placeholder="+90 555 123 45 67" required autofocus />
<x-form.input name="data[label]" :label="__('blocks.fields.label')" :value="$data['label'] ?? ''" maxlength="100" :placeholder="__('blocks.placeholders.phone_label')" />
