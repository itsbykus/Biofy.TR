<x-form.input name="data[phone]" type="tel" :label="__('blocks.fields.whatsapp_number')" :value="$data['phone'] ?? ''" placeholder="+90 555 123 45 67" required autofocus />
<x-form.input name="data[label]" :label="__('blocks.fields.label')" :value="$data['label'] ?? ''" maxlength="100" :placeholder="__('profile.whatsapp_default')" />
<x-form.textarea name="data[message]" :label="__('blocks.fields.prefilled_message')" :value="$data['message'] ?? ''" rows="2" maxlength="300" />
