@props([
    'name',
    'id' => null,
    'value' => null,
    'required' => false,
    'class' => 'form-control',
])

@php
    $inputId = $id ?? $name;
    $inputValue = old($name, $value !== null && $value !== '' ? ams_date_input($value) : '');
@endphp

<div class="ams-date-field">
    <input
        type="text"
        name="{{ $name }}"
        id="{{ $inputId }}"
        value="{{ $inputValue }}"
        placeholder="dd-mm-yy"
        autocomplete="off"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'ams-date-input ' . $class . ($errors->has($name) ? ' is-invalid' : '')]) }}
    >
</div>

@error($name)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
