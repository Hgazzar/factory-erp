<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'طباعة - '.config('app.name'))</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            margin: 0;
            padding: 0;
            color: #333333;
            background: #fff;
            font-size: 14px;
        }
        .print-page { max-width: 210mm; margin: 0 auto; padding: 14mm 16mm; min-height: 297mm; }
        .no-print { margin-bottom: 1rem; }
        @media screen {
            body { background: #f1f5f9; padding: 1rem; }
            .print-page { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px; }
        }
        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .no-print { display: none !important; }
            .print-page { max-width: 100%; padding: 12mm 14mm; box-shadow: none; min-height: auto; }
            a[href] { text-decoration: none; color: inherit; }
            @page { size: A4; margin: 12mm; }
            .print-table th,
            .print-header,
            .print-totals-row.grand {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* Header */
        .print-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #1a2b4c;
            margin-bottom: 1.25rem;
        }
        .print-logo-wrap {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .print-logo-img { height: 52px; width: auto; }
        .print-company-name { font-size: 1.5rem; font-weight: 700; color: #1a2b4c; margin: 0; }
        .print-tax-number { font-size: 0.85rem; color: #777777; margin-top: 0.25rem; }
        .print-doc-type { font-size: 1.25rem; font-weight: 600; color: #333333; }

        /* Document info */
        .print-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem 2rem;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
        }
        .print-meta-item { display: flex; gap: 0.5rem; }
        .print-meta-label { color: #777777; min-width: 100px; }
        .print-meta-value { font-weight: 600; color: #333333; }

        /* Table */
        .print-table-wrap { overflow-x: auto; margin-bottom: 1.25rem; }
        .print-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            text-align: right;
        }
        .print-table th, .print-table td { padding: 0.55rem 0.5rem; border: 1px solid #e0e0e0; }
        .print-table th { background: #f8f9fa; font-weight: 600; color: #333333; }
        .print-table tbody tr { background: #fff; }
        .print-table .text-left { text-align: left; }
        .print-table .num { font-family: inherit; font-variant-numeric: tabular-nums; text-align: center; direction: ltr; unicode-bidi: embed; }

        /* Footer */
        .print-footer { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e0e0e0; }
        .print-totals { margin-bottom: 1rem; }
        .print-totals-row { display: flex; justify-content: flex-end; gap: 2rem; padding: 0.25rem 0; }
        .print-totals-row.grand { font-weight: 700; font-size: 1.05rem; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #e0e0e0; color: #333333; }
        .print-terms { font-size: 0.8rem; color: #777777; line-height: 1.5; margin-bottom: 1rem; white-space: pre-line; }
        .print-qr-wrap { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-top: 1.5rem; }
        .print-qr-box { flex-shrink: 0; width: 100px; text-align: center; }
        .print-qr-box img { width: 90px; height: 90px; display: block; margin: 0 auto 0.25rem; }
        .print-qr-caption { font-size: 0.7rem; color: #777777; line-height: 1.3; }
        /* الختم المائي / المسودة */
        .print-watermark { position: fixed; top: 0; left: 0; right: 0; bottom: 0; pointer-events: none; z-index: 0; overflow: hidden; }
        .print-watermark-text { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%) rotate(-35deg); font-size: 6rem; font-weight: 700; color: rgba(0,0,0,0.06); white-space: nowrap; font-family: inherit; }
        /* ختم مدفوع */
        .print-stamp { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none; z-index: 0; }
        .print-stamp-inner { width: 140px; height: 140px; border: 4px solid rgba(34, 197, 94, 0.5); border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(34, 197, 94, 0.08); font-size: 1.5rem; font-weight: 700; color: #16a34a; font-family: inherit; }
        .print-page { position: relative; z-index: 1; }
    </style>
    @stack('print_styles')
</head>
<body>
    <div class="no-print" style="text-align: left; margin-bottom: 1rem;">
        <button type="button" onclick="window.print();" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white font-medium text-sm" style="background: #2563eb;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
            طباعة
        </button>
        <button type="button" onclick="window.close();" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium text-sm ml-2">إغلاق</button>
    </div>
    @yield('watermark')
    @yield('stamp')

    <div class="print-page">
        {{-- ترويسة موحدة (البيانات من إعدادات المنشأة) --}}
        <header class="print-header">
            <div class="print-logo-wrap">
                @yield('company_logo')
                <div>
                    <h1 class="print-company-name">@yield('company_name', config('app.name'))</h1>
                    <p class="print-tax-number">@yield('company_tax', 'الرقم الضريبي: —')</p>
                </div>
            </div>
            <div class="print-doc-type">@yield('doc_type')</div>
        </header>

        {{-- بيانات المستند --}}
        <div class="print-meta">@yield('document_meta')</div>

        {{-- جدول البنود --}}
        <div class="print-table-wrap">@yield('document_table')</div>

        {{-- تذييل: ملخص، شروط، QR بموقع ثابت ونص التحقق --}}
        <footer class="print-footer">@yield('document_footer')</footer>
    </div>
    @stack('print_scripts')
</body>
</html>
