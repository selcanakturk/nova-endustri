@props(['entry', 'name' => 'image', 'attribute' => 'image'])
<div class="admin-image-field" data-image-field>
    <label class="admin-section-label" for="{{ $name }}">Görsel</label>
    <div class="admin-image-preview"><img src="{{ $entry->image_url }}" alt="Mevcut görsel önizlemesi" width="640" height="440" data-image-preview></div>
    <label class="admin-upload-label" for="{{ $name }}">Yeni görsel seçin</label>
    <input type="file" id="{{ $name }}" name="{{ $name }}" accept=".jpg,.jpeg,.png,.webp" aria-describedby="{{ $name }}-hint {{ $name }}-error" @error($name) aria-invalid="true" @enderror data-image-input>
    <p id="{{ $name }}-hint" class="admin-field-hint">JPG, PNG veya WebP · En fazla 4 MB. Yeni görsel mevcut görselin yerini alır. SVG demo görselleri korunur.</p>
    <p id="{{ $name }}-error" class="admin-field-error">@error($name){{ $message }}@enderror</p>
    @if($entry->{$attribute})<label class="admin-check"><input type="checkbox" name="remove_image" value="1" @checked(old('remove_image', false))> Mevcut görseli kaldır</label>@endif
</div>
