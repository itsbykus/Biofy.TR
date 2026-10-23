<x-form.input name="data[email]" type="email" :label="__('blocks.fields.email')" :value="$data['email'] ?? ''" required autofocus />
<x-form.input name="data[label]" :label="__('blocks.fields.label')" :value="$data['label'] ?? ''" maxlength="100" :placeholder="__('blocks.placeholders.email_label')" />
<x-form.input name="data[subject]" :label="__('blocks.fields.subject')" :value="$data['subject'] ?? ''" maxlength="120" />
