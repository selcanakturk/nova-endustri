@props(['status', 'statuses'])
<span @class(['admin-status', 'inquiry-status-new' => $status === 'new', 'is-active' => in_array($status, ['replied', 'quoted'], true), 'is-inactive' => in_array($status, ['archived', 'closed', 'cancelled'], true), 'inquiry-status-reviewing' => in_array($status, ['read', 'reviewing'], true)])>{{ $statuses[$status] }}</span>
