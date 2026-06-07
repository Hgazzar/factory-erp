@php
    $tenantThemeVars = $tenantThemeVars ?? ($nurseryThemeVars ?? app(\App\Services\Tenant\TenantThemeService::class)->cssVariables(null, null));
@endphp
<style id="tenant-theme-vars">
    :root {
@foreach($tenantThemeVars as $var => $value)
        {{ $var }}: {{ $value }};
@endforeach
    }
</style>
