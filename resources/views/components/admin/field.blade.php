@props(['name', 'label', 'value' => null, 'type' => 'text', 'required' => false, 'hint' => null, 'options' => []])
@php
    $fieldValue = $type === 'password' ? '' : old($name, $value);
    $fieldValue = is_scalar($fieldValue) ? $fieldValue : '';
@endphp
<div class="admin-field">
    <label for="{{ $name }}">{{ $label }} @if($required)<span aria-hidden="true">*</span>@endif</label>
    @if($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" {{ $attributes }} @required($required) aria-describedby="{{ $name }}-hint {{ $name }}-error" @error($name) aria-invalid="true" @enderror>{{ $fieldValue }}</textarea>
    @elseif($type === 'select')
        <select id="{{ $name }}" name="{{ $name }}" {{ $attributes }} @required($required) aria-describedby="{{ $name }}-hint {{ $name }}-error" @error($name) aria-invalid="true" @enderror>
            @foreach($options as $key => $option)<option value="{{ $key }}" @selected((string)$fieldValue === (string)$key)>{{ $option }}</option>@endforeach
        </select>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $fieldValue }}" {{ $attributes }} @required($required) aria-describedby="{{ $name }}-hint {{ $name }}-error" @error($name) aria-invalid="true" @enderror>
    @endif
    <span id="{{ $name }}-hint" class="admin-field-hint">{{ $hint }}</span>
    <span id="{{ $name }}-error" class="admin-field-error">@error($name){{ $message }}@enderror</span>
</div>
