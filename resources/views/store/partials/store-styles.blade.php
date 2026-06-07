<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Cairo',sans-serif; background:#f8f9fa; }
    ::-webkit-scrollbar { width:6px; }
    ::-webkit-scrollbar-track { background:#f1f1f1; }
    ::-webkit-scrollbar-thumb { background:#888; border-radius:3px; }
    .hero-gradient { background:linear-gradient(135deg,#1e293b 0%,#334155 50%,#475569 100%); }
    .card-hover { transition:all 0.4s cubic-bezier(0.175,0.885,0.32,1.275); }
    .card-hover:hover { transform:translateY(-8px); box-shadow:0 20px 40px rgba(0,0,0,0.15); }
    .badge-pulse { animation:pulse 2s infinite; }
    @keyframes pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.1)} }
    @keyframes slideIn { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }
    @keyframes fadeIn { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
    @keyframes scaleIn { from{transform:scale(0.8);opacity:0} to{transform:scale(1);opacity:1} }
    @keyframes bounceIn { 0%{transform:scale(0)} 50%{transform:scale(1.1)} 100%{transform:scale(1)} }
    .slide-in { animation:slideIn 0.4s ease-out; }
    .fade-in { animation:fadeIn 0.5s ease-out; }
    .scale-in { animation:scaleIn 0.3s ease-out; }
    .bounce-in { animation:bounceIn 0.4s ease-out; }
    .gradient-text {
        background:linear-gradient(135deg, var(--store-primary, #dc2626), var(--store-secondary, #f97316));
        -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
    }
    .glass { background:rgba(255,255,255,0.85); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); }
    .product-img { transition:transform 0.5s ease; }
    .product-card:hover .product-img { transform:scale(1.08); }
    .filter-btn.active {
        background:linear-gradient(135deg, var(--store-primary, #dc2626), var(--store-secondary, #f97316));
        color:white; box-shadow:0 4px 15px color-mix(in srgb, var(--store-primary, #dc2626) 40%, transparent);
    }
    .qty-btn:active { transform:scale(0.9); }
    .checkout-step.active .step-circle {
        background:linear-gradient(135deg, var(--store-primary, #dc2626), var(--store-secondary, #f97316));
        color:white; border-color:transparent;
    }
    .checkout-step.completed .step-circle { background:#16a34a; color:white; border-color:transparent; }
    .toast { animation:slideInRight 0.4s ease-out; }
    @keyframes slideInRight { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }
    .overlay { animation:fadeIn 0.3s ease-out; }
    .notification-badge {
        position:absolute; top:-8px; right:-8px; min-width:20px; height:20px; border-radius:50%;
        display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:white;
        background:linear-gradient(135deg, var(--store-primary, #dc2626), var(--store-secondary, #f97316));
    }
    .bg-store-gradient { background:linear-gradient(to right, var(--store-primary, #dc2626), var(--store-secondary, #f97316)); }
    .bg-store-gradient-br { background:linear-gradient(to bottom right, var(--store-primary, #dc2626), var(--store-secondary, #f97316)); }
    .text-store-primary { color: var(--store-primary, #dc2626); }
    .border-store-primary { border-color: var(--store-primary, #dc2626); }
    .hover-shadow-store:hover { box-shadow:0 10px 25px color-mix(in srgb, var(--store-primary, #dc2626) 30%, transparent); }
    [x-cloak] { display:none !important; }
</style>
