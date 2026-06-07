<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store\Portal;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\PosSale;
use App\Models\TenantStoreSetting;
use App\Services\Store\StoreCheckoutService;
use App\Services\Store\StorefrontCatalogService;
use App\Services\Tenant\TenantBrandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class StorePortalWebController extends Controller
{
    public function __construct(
        private readonly TenantBrandingService $brandingService,
    ) {}

    private function baseData(Request $request): array
    {
        $tenantUserId = (int) $request->attributes->get('store_portal_tenant_user_id');
        /** @var TenantStoreSetting $settings */
        $settings = $request->attributes->get('store_portal_settings');
        $company = CompanySetting::forTenant($tenantUserId);
        $slug = (string) $request->route('tenant_slug');
        $branding = $this->brandingService->branding($tenantUserId);

        return [
            'tenantUserId' => $tenantUserId,
            'storeSettings' => $settings,
            'company' => $company,
            'tenantBrand' => $branding,
            'storeName' => $branding['display_name'],
            'currencyCode' => CompanySetting::resolvedCurrencyCode($tenantUserId),
            'tenantSlug' => $slug,
            'apiBase' => url('/s/'.$slug.'/api'),
            'routes' => [
                'home' => route('store.portal.home', ['tenant_slug' => $slug]),
                'shop' => route('store.portal.shop', ['tenant_slug' => $slug]),
                'offers' => route('store.portal.offers', ['tenant_slug' => $slug]),
                'checkout' => route('store.portal.checkout', ['tenant_slug' => $slug]),
                'about' => route('store.portal.about', ['tenant_slug' => $slug]),
                'contact' => route('store.portal.contact', ['tenant_slug' => $slug]),
                'faq' => route('store.portal.faq', ['tenant_slug' => $slug]),
                'shipping' => route('store.portal.shipping', ['tenant_slug' => $slug]),
                'returns' => route('store.portal.returns', ['tenant_slug' => $slug]),
                'track' => route('store.portal.track', ['tenant_slug' => $slug]),
                'privacy' => route('store.portal.privacy', ['tenant_slug' => $slug]),
            ],
        ];
    }

    public function home(Request $request, StorefrontCatalogService $catalog): View
    {
        $data = $this->baseData($request);
        $tid = $data['tenantUserId'];

        $data['allProducts'] = $catalog->paginatedProducts($tid, ['per_page' => 48])->items();
        $data['categories'] = $catalog->categories($tid);

        return view('store.home', $data);
    }

    public function shop(Request $request): RedirectResponse
    {
        $slug = (string) $request->route('tenant_slug');
        $query = array_filter([
            'category_id' => $request->query('category_id'),
            'q' => $request->query('q'),
        ]);

        $url = route('store.portal.home', ['tenant_slug' => $slug]);
        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return redirect()->to($url.'#productsSection');
    }

    public function offers(Request $request): RedirectResponse
    {
        $slug = (string) $request->route('tenant_slug');

        return redirect()->to(route('store.portal.home', ['tenant_slug' => $slug]).'?featured=1#productsSection');
    }

    public function product(Request $request, string $tenant_slug, int|string $product, StorefrontCatalogService $catalog): View|RedirectResponse
    {
        $product = (int) $product;
        $data = $this->baseData($request);
        $model = $catalog->findPublishedProduct($data['tenantUserId'], $product);

        if ($model === null) {
            return redirect()->route('store.portal.home', ['tenant_slug' => $data['tenantSlug']]);
        }

        $data['product'] = $catalog->detailPayload($model, $data['tenantSlug']);
        $data['product']['related'] = $catalog->productList(
            $data['tenantUserId'],
            4,
            null,
        );

        return view('store.product', $data);
    }

    public function checkout(Request $request, StoreCheckoutService $checkout): View
    {
        $data = $this->baseData($request);
        /** @var TenantStoreSetting $settings */
        $settings = $request->attributes->get('store_portal_settings');
        $data['paymentMethods'] = $checkout->availablePaymentMethods($data['tenantUserId']);
        $data['onlinePaymentEnabled'] = collect($data['paymentMethods'])->contains(fn ($m) => ($m['key'] ?? '') === \App\Models\PosSale::PAYMENT_CARD);
        $data['paymentProvider'] = $settings->effectivePaymentProvider();
        $data['paymentProviderLabel'] = $settings->paymentProviderLabel();
        $data['paymentSandbox'] = ($settings->online_payment_mode ?? 'sandbox') === 'sandbox'
            || config('store.payment.sandbox', true);

        return view('store.checkout', $data);
    }

    public function cart(Request $request): RedirectResponse
    {
        $slug = (string) $request->route('tenant_slug');

        return redirect()->to(route('store.portal.home', ['tenant_slug' => $slug]).'#cart');
    }

    public function orderSuccess(Request $request, string $tenant_slug, int|string $saleId): View
    {
        $tenantUserId = (int) $request->attributes->get('store_portal_tenant_user_id');
        $saleId = (int) $saleId;

        $posSale = PosSale::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($saleId)
            ->firstOrFail();

        if ($posSale->sale_channel !== null && $posSale->sale_channel !== PosSale::CHANNEL_ONLINE_STORE) {
            abort(404);
        }

        $data = $this->baseData($request);
        $data['sale'] = $posSale->load(['items.product']);

        return view('store.order-success', $data);
    }

    public function about(Request $request): View
    {
        return view('store.page', array_merge($this->baseData($request), [
            'pageTitle' => 'من نحن',
            'pageBody' => $request->attributes->get('store_portal_settings')->about_us,
        ]));
    }

    public function contact(Request $request): View
    {
        return view('store.page', array_merge($this->baseData($request), [
            'pageTitle' => 'اتصل بنا',
            'pageBody' => $request->attributes->get('store_portal_settings')->contact_us,
        ]));
    }

    public function faq(Request $request): View
    {
        return view('store.page', array_merge($this->baseData($request), [
            'pageTitle' => 'الأسئلة الشائعة',
            'pageBody' => $request->attributes->get('store_portal_settings')->faq,
        ]));
    }

    public function privacy(Request $request): View
    {
        return view('store.page', array_merge($this->baseData($request), [
            'pageTitle' => 'سياسة الخصوصية',
            'pageBody' => $request->attributes->get('store_portal_settings')->privacy_policy,
        ]));
    }

    public function shipping(Request $request): View
    {
        return view('store.page', array_merge($this->baseData($request), [
            'pageTitle' => 'الشحن والتوصيل',
            'pageBody' => $request->attributes->get('store_portal_settings')->shipping_policy,
        ]));
    }

    public function returns(Request $request): View
    {
        return view('store.page', array_merge($this->baseData($request), [
            'pageTitle' => 'سياسة الإرجاع',
            'pageBody' => $request->attributes->get('store_portal_settings')->return_policy,
        ]));
    }

    public function track(Request $request): View
    {
        $data = $this->baseData($request);
        /** @var TenantStoreSetting $settings */
        $settings = $request->attributes->get('store_portal_settings');
        $data['pageHelp'] = $settings->track_order_help;
        $data['trackedSale'] = null;
        $data['trackError'] = null;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'invoice_number' => ['required', 'string', 'max:64'],
                'customer_phone' => ['required', 'string', 'max:32'],
            ]);

            $phone = preg_replace('/\D+/', '', $validated['customer_phone']) ?? '';
            $invoice = trim($validated['invoice_number']);

            $sale = PosSale::withoutGlobalScopes()
                ->where('user_id', $data['tenantUserId'])
                ->where('sale_channel', PosSale::CHANNEL_ONLINE_STORE)
                ->where('invoice_number', $invoice)
                ->first();

            if ($sale === null) {
                $data['trackError'] = 'لم نعثر على طلب بهذا الرقم.';
            } else {
            $salePhone = substr(preg_replace('/\D+/', '', (string) $sale->customer_phone) ?? '', -9);
            if ($phone === '' || $salePhone === '' || substr($phone, -9) !== $salePhone) {
                $data['trackError'] = 'رقم الجوال لا يطابق الطلب.';
            } else {
                $data['trackedSale'] = $sale->load(['items.product']);
            }
            }
        }

        return view('store.track', $data);
    }
}
