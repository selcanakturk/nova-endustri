@props(['value' => null])
<div class="admin-markdown" data-markdown-editor data-preview-url="{{ route('admin.posts.preview') }}">
    <label class="admin-section-label" for="content">İçerik <span aria-hidden="true">*</span></label>
    <div class="admin-editor-toolbar" role="group" aria-label="Metin biçimlendirme" hidden>
        @foreach(['h2' => 'H2', 'h3' => 'H3', 'bold' => 'Kalın', 'italic' => 'İtalik', 'ul' => '• Liste', 'ol' => '1. Liste', 'link' => 'Bağlantı', 'quote' => 'Alıntı'] as $command => $label)<button type="button" data-markdown-command="{{ $command }}">{{ $label }}</button>@endforeach
        <button type="button" data-markdown-preview aria-controls="markdown-preview" aria-expanded="false">Önizle</button>
    </div>
    <textarea id="content" name="content" rows="18" required maxlength="100000" aria-describedby="content-hint content-error" @error('content') aria-invalid="true" @enderror>{{ is_string(old('content', $value)) ? old('content', $value) : '' }}</textarea>
    <p id="content-hint" class="admin-field-hint">Markdown: ## başlık, **kalın**, *italik*, - liste, [bağlantı](https://...). HTML çalıştırılmaz. Yayın saatleri İstanbul saatine göredir.</p><p id="content-error" class="admin-field-error">@error('content'){{ $message }}@enderror</p>
    <p role="status" class="admin-field-hint" data-preview-status></p><div id="markdown-preview" class="post-prose admin-markdown-preview" hidden></div>
</div>
