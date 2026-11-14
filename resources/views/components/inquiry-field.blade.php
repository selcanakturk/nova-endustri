@props(['name', 'label', 'type' => 'text', 'required' => false, 'hint' => null])
@php
    $value = old($name, '');
    $value = is_scalar($value) ? $value : '';
@endphp
<div class="inquiry-field">
    <label for="{{ $name }}">{{ $label }} @if($required)<span aria-hidden="true">*</span>@endif</label>
    @if($type === 'textarea')<textarea id="{{ $name }}" name="{{ $name }}" {{ $attributes }} @required($required) aria-describedby="{{ $name }}-hint {{ $name }}-error" @error($name) aria-invalid="true" @enderror>{{ $value }}</textarea>
    @else<input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}" {{ $attributes }} @required($required) aria-describedby="{{ $name }}-hint {{ $name }}-error" @error($name) aria-invalid="true" @enderror>@endif
    <span id="{{ $name }}-hint" class="inquiry-hint">{{ $hint }}</span><span id="{{ $name }}-error" class="inquiry-error">@error($name){{ $message }}@enderror</span>
</div>
