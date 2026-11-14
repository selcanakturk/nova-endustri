@if(session('status'))<div class="inquiry-alert inquiry-success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="inquiry-alert inquiry-invalid" role="alert"><strong>Lütfen bilgileri kontrol edin.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
