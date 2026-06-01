<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store\Portal;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\PosProduct;
use App\Models\PosSale;
use App\Models\TenantStoreSetting;
use App\Services\Store\StorefrontCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class StorePortalWebController extends Controller
{
    private function baseData(Request $request): array
    {
        $tenantUserId = (int) $request->attributes->get('store_portal_tenant_user_id');
        /** @var TenantStoreSetting $settings */
        $settings = $request->attributes->get('store_portal_settings');
        $company = CompanySetting::forTenant($tenantUserId);
        $slug = (string) $request->route('tenant_slug');

        return [
            'tenantUserId' => $tenantUserId,
            'storeSettings' => $settings,
            'company' => $company,
            'storeName' => $company?->name ?? config('app.name'),
            'currencyCode' => CompanySetting::resolvedCurrencyCode($tenantUserId),
            'tenantSlug' => $slug,
            'apiBase' => url('/s/'.$slug.'/api'),
            'routes' => [
                'home' => route('store.portal.home', ['tenant_slug' => $slug]),
                'shop' => route('store.portal.shop', ['tenant_slug' => $slug]),
                'checkout' => route('store.portal.checkout', ['tenant_slug' => $slug]),
                'about' => route('store.portal.about', ['tenant_slug' => $slug]),
                'contact' => route('store.portal.contact', ['tenant_slug' => $slug]),
                'faq' => route('store.portal.faq', ['tenant_slug' => $slug]),
                'shipping' => route('store.portal.shipping', ['tenant_slug' => $slug]),
                'privacy' => route('store.portal.privacy', ['tenant_slug' => $slug]),
            ],
        ];
    }

    public function home(Request $request, StorefrontCatalogService $catalog): View
    {
        $data = $this->baseData($request);
        $tid = $data['tenantUserId'];

        $data['banners'] = $catalog->activeBanners($tid);
        if ($data['banners'] === []) {
            $data['banners'] = $this->defaultBanners($data['storeSettings']);
        }
        $data['featured'] = $catalog->productList($tid, 8, 'featured');
        $data['trending'] = $catalog->productList($tid, 8, 'trending');
        $data['bestsellers'] = $catalog->productList($tid, 8, 'bestseller');
        $data['latest'] = $catalog->productList($tid, 8);
        $data['allProducts'] = $catalog->paginatedProducts($tid, ['per_page' => 12])->items();
        $data['categories'] = $catalog->categories($tid);

        return view('store.premium.home', $data);
    }

    public function shop(Request $request, StorefrontCatalogService $catalog): View
    {
        $data = $this->baseData($request);
        $data['categories'] = $catalog->categories($data['tenantUserId']);
        $data['initialProducts'] = $catalog->paginatedProducts($data['tenantUserId'], [
            'page' => (int) $request->query('page', 1),
            'category_id' => $request->query('category_id') ? (int) $request->query('category_id') : null,
            'q' => $request->query('q'),
            'sort' => $request->query('sort', 'newest'),
        ])->items();

        return view('store.premium.shop', $data);
    }

    public function product(Request $request, string $tenant_slug, int|string $product, StorefrontCatalogService $catalog): View|RedirectResponse
    {
        $product = (int) $product;
        $data = $this->baseData($request);
        $model = $catalog->findPublishedProduct($data['tenantUserId'], $product);

        if ($model === null) {
            return redirect()->route('store.portal.shop', ['tenant_slug' => $data['tenantSlug']]);
        }

        $data['product'] = $catalog->detailPayload($model, $data['tenantSlug']);
        $data['product']['related'] = $catalog->productList(
            $data['tenantUserId'],
            4,
            null,
        );

        return view('store.premium.product', $data);
    }

    public function checkout(Request $request): View
    {
        return view('store.premium.checkout', $this->baseData($request));
    }

    public function cart(): RedirectResponse
    {
        return redirect()->back()->withFragment('cart');
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

        return view('store.premium.order-success', $data);
    }

    public function about(Request $request): View
    {
        return view('store.premium.page', array_merge($this->baseData($request), [
            'pageTitle' => 'من نحن',
            'pageBody' => $request->attributes->get('store_portal_settings')->about_us,
        ]));
    }

    public function contact(Request $request): View
    {
        return view('store.premium.page', array_merge($this->baseData($request), [
            'pageTitle' => 'اتصل بنا',
            'pageBody' => $request->attributes->get('store_portal_settings')->contact_us,
        ]));
    }

    public function faq(Request $request): View
    {
        return view('store.premium.page', array_merge($this->baseData($request), [
            'pageTitle' => 'الأسئلة الشائعة',
            'pageBody' => $request->attributes->get('store_portal_settings')->faq,
        ]));
    }

    public function privacy(Request $request): View
    {
        return view('store.premium.page', array_merge($this->baseData($request), [
            'pageTitle' => 'سياسة الخصوصية',
            'pageBody' => $request->attributes->get('store_portal_settings')->privacy_policy,
        ]));
    }

    public function shipping(Request $request): View
    {
        return view('store.premium.page', array_merge($this->baseData($request), [
            'pageTitle' => 'سياسة الشحن',
            'pageBody' => $request->attributes->get('store_portal_settings')->shipping_policy,
        ]));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaultBanners(TenantStoreSetting $settings): array
    {
        return [[
            'title' => $settings->hero_title ?: 'مجموعة أكواد الفاخرة',
            'subtitle' => $settings->hero_subtitle ?: 'تجربة تسوق راقية بمعايير عالمية',
            'cta_label' => 'تسوق الآن',
            'cta_url' => null,
            'image_url' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1920&q=85&auto=format&fit=crop',
        ], [
            'title' => $settings->hero_offer_text ?: 'عروض محدودة',
            'subtitle' => 'خصومات حصرية على مختارات الموسم',
            'cta_label' => 'اكتشف العروض',
            'cta_url' => null,
            'image_url' => 'https://images.unsplash.com/photo-1469334031218-e8bd6864f804?w=1920&q=85&auto=format&fit=crop',
        ]];
    }
}
