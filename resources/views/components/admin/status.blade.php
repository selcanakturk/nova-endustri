@props(['active'])
<span @class(['admin-status', 'is-active' => $active, 'is-inactive' => !$active])><span aria-hidden="true"></span>{{ $active ? 'Aktif' : 'Pasif' }}</span>
