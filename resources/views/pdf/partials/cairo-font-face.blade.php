{{-- خط Cairo العربي لـ dompdf؛ الأرقام واللاتينية عبر DejaVu Sans كاحتياطي في font-family --}}
@php
    $cairoRegularPath = str_replace('\\', '/', base_path('resources/fonts/cairo/Cairo-Regular.ttf'));
    $cairoBoldPath = str_replace('\\', '/', base_path('resources/fonts/cairo/Cairo-Bold.ttf'));
@endphp
<style>
    @font-face {
        font-family: 'Cairo';
        font-weight: normal;
        font-style: normal;
        src: url('{{ $cairoRegularPath }}') format('truetype');
    }
    @font-face {
        font-family: 'Cairo';
        font-weight: bold;
        font-style: normal;
        src: url('{{ $cairoBoldPath }}') format('truetype');
    }
</style>
