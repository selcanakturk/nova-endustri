<x-admin.layout :title="$title">
<div class="admin-page-intro">@isset($page)<a class="admin-link" href="{{ route('admin.pages.index') }}">← Sayfalara dön</a><a class="admin-link" href="{{ route($page->key) }}">Sayfayı sitede aç ↗</a>@else<span class="admin-subtle">Yalnızca halka açık şirket bilgileri. Şifre veya API anahtarı girmeyin.</span>@endisset</div>
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="cms-editor">@csrf @method('PUT')
@foreach(collect($fields)->groupBy('group', preserveKeys: true) as $group => $groupFields)
<section class="admin-panel admin-form-panel"><h2>{{ $group }}</h2><p class="admin-panel-description">Düz metin kullanın. Boş SEO alanlarında sayfa ve site varsayılanları kullanılır.</p>
@foreach($groupFields as $key => $field)
@if($field['type'] === 'image')
<div class="admin-field"><label for="{{ $key }}">{{ $field['label'] }}</label>@if(filled($values[$key] ?? null))<img class="cms-image-preview" src="{{ \App\CatalogImage::url($values[$key]) }}" alt="{{ $field['label'] }} önizlemesi" width="320" height="200">@endif<input type="file" id="{{ $key }}" name="{{ $key }}" accept=".jpg,.jpeg,.png,.webp"><span class="admin-field-hint">JPG, PNG veya WebP · En fazla 4 MB ve 6000 × 6000 piksel. Mevcut SVG varlıkları korunur.</span>@error($key)<span class="admin-field-error">{{ $message }}</span>@enderror<label class="admin-check"><input type="checkbox" name="remove_{{ $key }}" value="1" @checked(old('remove_'.$key))> Görseli kaldır</label></div>
@else
<x-admin.field :name="$key" :label="$field['label']" :type="in_array($field['type'], ['textarea', 'email', 'url']) ? $field['type'] : 'text'" :value="$values[$key] ?? null" :maxlength="str_contains($key, 'description') && in_array($key, ['meta_description', 'og_description']) ? 1000 : ($field['type'] === 'textarea' ? 10000 : (in_array($field['type'], ['link', 'url']) ? 2048 : 255))" :hint="in_array($field['type'], ['url', 'link']) ? 'HTTP/HTTPS bağlantısı'.($field['type'] === 'link' ? ' veya / ile başlayan site içi adres.' : '.') : null" />
@endif
@endforeach</section>
@endforeach
<div class="admin-save-bar"><button class="admin-button" type="submit">Değişiklikleri kaydet →</button></div>
</form></x-admin.layout>