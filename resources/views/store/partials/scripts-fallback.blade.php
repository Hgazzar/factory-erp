@php
    $manifestPath = public_path('build/manifest.json');
    $storeJsFile = null;
    if (is_file($manifestPath)) {
        $manifest = json_decode((string) file_get_contents($manifestPath), true) ?: [];
        $storeJsFile = $manifest['resources/js/store.js']['file'] ?? null;
    }
@endphp
@if($storeJsFile)
    <script src="{{ asset('build/'.$storeJsFile) }}" defer></script>
@else
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
@endif
