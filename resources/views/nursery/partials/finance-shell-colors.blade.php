{{-- توحيد ألوان شاشات Finance داخل شل الحضانة مع ثيم النظام --}}
<style id="nursery-finance-color-unify">
    .nursery-main-inner {
        --nf-primary: var(--nursery-primary);
        --nf-primary-dark: var(--nursery-primary-dark);
        --nf-text: var(--nursery-text);
        --nf-muted: var(--nursery-text-muted);
        --nf-border: var(--nursery-border);
        --nf-bg: var(--nursery-bg-mid);
        --nf-soft: var(--nursery-secondary);
        --nf-on: var(--nursery-on-primary);
        --nf-shadow: var(--nursery-shadow);
    }

    /* Cards / panels */
    .nursery-main-inner .erp-card,
    .nursery-main-inner .acc-kpi-card,
    .nursery-main-inner .acc-widget-card,
    .nursery-main-inner .acc-dashboard-toolbar,
    .nursery-main-inner .acc-bottom-card,
    .nursery-main-inner .erp-table-wrap,
    .nursery-main-inner .acc-quick-action-btn,
    .nursery-main-inner .rounded-lg.border.bg-white,
    .nursery-main-inner .rounded-xl.border.bg-white,
    .nursery-main-inner .shadow-sm.border.bg-white {
        border-color: var(--nf-border) !important;
        border-radius: 1rem;
        box-shadow: 0 4px 14px var(--nf-shadow);
    }

    .nursery-main-inner .erp-table-wrap,
    .nursery-main-inner .table-responsive,
    .nursery-main-inner .overflow-x-auto {
        overflow-x: auto;
        max-width: 100%;
        -webkit-overflow-scrolling: touch;
    }

    /* ابقِ عمود الإجراءات ظاهراً بالكامل عند الجداول العريضة (RTL: أقصى اليسار) */
    .nursery-main-inner table th.sticky.left-0,
    .nursery-main-inner table td.sticky.left-0 {
        background-clip: padding-box;
    }

    /* Headings & body text */
    .nursery-main-inner h1,
    .nursery-main-inner h2,
    .nursery-main-inner .h1,
    .nursery-main-inner .h2,
    .nursery-main-inner .h3,
    .nursery-main-inner .h4,
    .nursery-main-inner .text-gray-900,
    .nursery-main-inner .text-slate-900,
    .nursery-main-inner .text-indigo-900,
    .nursery-main-inner .text-blue-900 {
        color: var(--nf-text) !important;
    }

    .nursery-main-inner .text-gray-500,
    .nursery-main-inner .text-gray-600,
    .nursery-main-inner .text-muted,
    .nursery-main-inner .text-slate-500 {
        color: var(--nf-muted) !important;
    }

    /* Links & indigo/blue text accents → nursery primary */
    .nursery-main-inner a.text-indigo-600,
    .nursery-main-inner a.text-indigo-500,
    .nursery-main-inner a.text-blue-600,
    .nursery-main-inner a.text-blue-500,
    .nursery-main-inner a.text-blue-700,
    .nursery-main-inner a.text-blue-800,
    .nursery-main-inner .text-indigo-600,
    .nursery-main-inner .text-indigo-500,
    .nursery-main-inner .text-blue-600,
    .nursery-main-inner .text-blue-500,
    .nursery-main-inner .text-blue-700,
    .nursery-main-inner .text-blue-800,
    .nursery-main-inner .hover\:text-indigo-600:hover,
    .nursery-main-inner .hover\:text-blue-600:hover,
    .nursery-main-inner .hover\:text-blue-700:hover,
    .nursery-main-inner .hover\:text-blue-800:hover {
        color: var(--nf-primary-dark) !important;
    }

    /* Primary solid buttons (Tailwind + Bootstrap + toolbar) */
    .nursery-main-inner .btn-primary,
    .nursery-main-inner .btn-primary-toolbar,
    .nursery-main-inner .bg-blue-600,
    .nursery-main-inner .bg-blue-700,
    .nursery-main-inner .bg-indigo-600,
    .nursery-main-inner .bg-indigo-700,
    .nursery-main-inner .hover\:bg-blue-700:hover,
    .nursery-main-inner .hover\:bg-indigo-700:hover,
    .nursery-main-inner .acc-dashboard-toolbar .btn-primary-toolbar {
        background-color: var(--nf-primary) !important;
        border-color: var(--nf-primary-dark) !important;
        color: var(--nf-on) !important;
    }

    .nursery-main-inner .btn-primary:hover,
    .nursery-main-inner .btn-primary-toolbar:hover,
    .nursery-main-inner .bg-blue-600:hover,
    .nursery-main-inner .bg-indigo-600:hover {
        background-color: var(--nf-primary-dark) !important;
        border-color: var(--nf-primary-dark) !important;
        color: var(--nf-on) !important;
    }

    /* Soft / tinted blue-indigo surfaces */
    .nursery-main-inner .bg-blue-50,
    .nursery-main-inner .bg-indigo-50,
    .nursery-main-inner .bg-blue-100,
    .nursery-main-inner .bg-indigo-100 {
        background-color: var(--nf-soft) !important;
    }

    .nursery-main-inner .border-blue-200,
    .nursery-main-inner .border-indigo-200,
    .nursery-main-inner .border-blue-300,
    .nursery-main-inner .border-indigo-300 {
        border-color: var(--nf-border) !important;
    }

    .nursery-main-inner .text-blue-800,
    .nursery-main-inner .text-indigo-800,
    .nursery-main-inner .bg-blue-50.text-blue-800,
    .nursery-main-inner .bg-indigo-50.text-indigo-800 {
        color: var(--nf-text) !important;
    }

    /* Focus rings / borders on inputs */
    .nursery-main-inner .focus\:border-blue-500:focus,
    .nursery-main-inner .focus\:border-indigo-500:focus,
    .nursery-main-inner input:focus,
    .nursery-main-inner select:focus,
    .nursery-main-inner textarea:focus {
        border-color: var(--nf-primary) !important;
        outline-color: var(--nf-primary);
    }

    .nursery-main-inner .focus\:ring-blue-500:focus,
    .nursery-main-inner .focus\:ring-indigo-500:focus {
        --tw-ring-color: var(--nf-primary) !important;
        box-shadow: 0 0 0 2px color-mix(in srgb, var(--nf-primary) 25%, transparent) !important;
    }

    /* Table headers */
    .nursery-main-inner .erp-table-wrap thead,
    .nursery-main-inner table thead,
    .nursery-main-inner thead.bg-gray-50,
    .nursery-main-inner .bg-gray-50 {
        background-color: var(--nf-bg) !important;
        color: var(--nf-muted);
    }

    /* Secondary / outline buttons */
    .nursery-main-inner .btn-secondary-toolbar,
    .nursery-main-inner .btn-outline-primary {
        color: var(--nf-text) !important;
        border-color: var(--nf-border) !important;
        background: #fff !important;
    }

    .nursery-main-inner .btn-outline-primary:hover,
    .nursery-main-inner .btn-secondary-toolbar:hover {
        background: var(--nf-soft) !important;
        color: var(--nf-text) !important;
        border-color: var(--nf-primary) !important;
    }

    /* Pagination active (Bootstrap) */
    .nursery-main-inner .page-item.active .page-link,
    .nursery-main-inner .pagination .active > .page-link {
        background-color: var(--nf-primary) !important;
        border-color: var(--nf-primary-dark) !important;
        color: var(--nf-on) !important;
    }

    .nursery-main-inner .page-link {
        color: var(--nf-primary-dark);
    }

    .nursery-main-inner .page-link:hover {
        color: var(--nf-primary);
        background-color: var(--nf-soft);
    }

    /* Badges / chips that used indigo-blue */
    .nursery-main-inner .badge.bg-primary,
    .nursery-main-inner .bg-indigo-600.text-white,
    .nursery-main-inner span.bg-blue-600 {
        background-color: var(--nf-primary) !important;
    }

    /* Quick-action / icon circles */
    .nursery-main-inner .text-indigo-500,
    .nursery-main-inner .text-blue-600 svg,
    .nursery-main-inner .qa-icon.text-blue-600 {
        color: var(--nf-primary) !important;
    }

    /* Keep semantic success/danger/warning untouched */
</style>
