@props(['status'])
@php($map = ['published' => 'badge-green', 'active' => 'badge-green', 'verified' => 'badge-green', 'completed' => 'badge-green', 'draft' => 'badge-gray', 'pending' => 'badge-yellow', 'running' => 'badge-yellow', 'new' => 'badge-yellow', 'suspended' => 'badge-red', 'failed' => 'badge-red', 'disabled' => 'badge-red', 'archived' => 'badge-gray', 'read' => 'badge-gray', 'unknown' => 'badge-gray'])
<span {{ $attributes->merge(['class' => 'badge '.($map[$status] ?? 'badge-gray')]) }}>{{ __('common.status.'.$status) }}</span>
