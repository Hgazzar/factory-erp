<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\EinvoiceSetting;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Salla\ZATCA\Exception\CSRValidationException;
use Salla\ZATCA\GenerateCSR;
use Salla\ZATCA\Helpers\Certificate;
use Salla\ZATCA\Helpers\UXML;
use Salla\ZATCA\Models\CSR;
use Salla\ZATCA\Models\CSRRequest;
use Salla\ZATCA\Models\Invoice as SignedZatcaInvoice;
use Salla\ZATCA\Models\InvoiceSign;

/**
 * طبقة تكامل مع مكتبة salla/zatca: توليد CSR، بناء UBL مبسّط، والتوقيع (SHA-256 + ECDSA + امتداد UBL).
 */
class ZatcaService
{
    /**
     * قيمة PIH الافتراضية للفاتورة الأولى وفق أمثلة ZATCA / الحزمة.
     *
     * @see https://github.com/SallaApp/ZATCA/blob/master/tests/simplified_invoice.xml
     */
    public const DEFAULT_FIRST_PREVIOUS_INVOICE_HASH_BASE64 = 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==';

    /**
     * مساحة أسماء UUIDv5 ثابتة للفواتير — لا تغيّرها بعد إصدار فواتير مرتبطة بهذا المعرّف.
     */
    private const SALES_INVOICE_UUID_NAMESPACE = 'a3f7c2e1-9b4d-5e6f-a7b8-c9d0e1f2a3b4';

    public function generateCsr(CSRRequest $request): CSR
    {
        return GenerateCSR::fromRequest($request)->initialize()->generate();
    }

    /**
     * توليد CSR ومفتاح EC الخاص (secp256k1) وفق متطلبات ZATCA، ثم حفظهما على القرص المحلي وتحديث einvoice_settings.
     *
     * مصادر الحقول: الرقم الضريبي (UID) من zatca_tax_number ثم tax_number في إعدادات الشركة؛ اسم المنشأة من zatca_seller_name ثم اسم الشركة؛
     * العنوان المسجّل من عنوان الشركة؛ الرقم الموحّد للوحدة التنظيمية يُشتق من السجل التجاري عندما يتطلّب ZATCA ذلك.
     */
    public function generateAndStoreCsrForEinvoiceSettings(
        ?EinvoiceSetting $setting = null,
        ?CompanySetting $company = null,
    ): EinvoiceSetting {
        $setting ??= EinvoiceSetting::get();
        $company ??= auth()->check()
            ? CompanySetting::forTenant((int) auth()->id())
            : CompanySetting::query()->orderBy('id')->first();

        $uid = $this->normalizeZatcaOrganizationIdentifier(
            $setting->zatca_tax_number ?: $company?->tax_number,
        );

        $organizationName = $this->sanitizeOpenSslDnField(
            $setting->zatca_seller_name ?: $company?->name,
            fallback: 'Organization',
        );

        $commonName = $this->sanitizeOpenSslDnField(
            $setting->zatca_seller_name ?: $company?->name,
            fallback: 'EGS-'.$setting->id,
        );

        $organizationalUnit = $this->resolveOrganizationalUnitName($uid, $company, $setting);

        $registeredAddress = $this->sanitizeOpenSslDnField(
            $company?->address,
            fallback: 'Riyadh',
        );

        $zatcaEnv = $this->mapEinvoiceEnvironmentToCsrEnv($setting->environment);

        $solutionName = $this->sanitizeOpenSslDnField(config('app.name'), fallback: 'FactoryERP');
        $solutionVersion = $this->sanitizeOpenSslDnField((string) config('app.version', '1.0'), fallback: '1.0');
        $deviceSerial = Str::upper(Str::replace('-', '', (string) Str::uuid()));

        try {
            $request = CSRRequest::make()
                ->setUID($uid)
                ->setSerialNumber($solutionName, $solutionVersion, $deviceSerial)
                ->setCommonName($commonName)
                ->setOrganizationName($organizationName)
                ->setOrganizationalUnitName($organizationalUnit)
                ->setCountryName('SA')
                ->setRegisteredAddress($registeredAddress)
                ->setInvoiceType(true, true)
                ->setCurrentZatcaEnv($zatcaEnv)
                ->setBusinessCategory('company');
        } catch (CSRValidationException $e) {
            throw new InvalidArgumentException('بيانات CSR غير صالحة: '.$e->getMessage(), previous: $e);
        }

        $generated = $this->generateCsr($request);

        if (! openssl_pkey_export($generated->getPrivateKey(), $privateKeyPem)) {
            throw new RuntimeException('تعذّر تصدير المفتاح الخاص إلى PEM.');
        }

        $disk = Storage::disk('local');
        $directory = 'zatca/einvoice-settings/'.$setting->id;
        $csrRelativePath = $directory.'/zatca.csr';
        $keyRelativePath = $directory.'/private.pem';

        DB::transaction(function () use ($disk, $generated, $privateKeyPem, $setting, $directory, $csrRelativePath, $keyRelativePath) {
            $disk->makeDirectory($directory);
            $disk->put($csrRelativePath, $generated->getCsrContent());
            $disk->put($keyRelativePath, $privateKeyPem);

            $setting->csr_path = $csrRelativePath;
            $setting->private_key_path = $keyRelativePath;
            $setting->save();
        });

        return $setting->refresh();
    }

    /**
     * إكمال الربط (Onboarding): إرسال CSR مع OTP إلى بوابة الامتثال وحفظ الشهادة والمفتاح ومعرّف الطلب.
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function completeOnboarding(EinvoiceSetting $setting, string $otp): EinvoiceSetting
    {
        $otp = trim($otp);
        if ($otp === '') {
            throw new InvalidArgumentException('رمز OTP مطلوب.');
        }

        $csrPath = $setting->csr_path;
        if ($csrPath === null || $csrPath === '') {
            throw new RuntimeException('لم يُولَّد CSR بعد. نفّذ أمر zatca:generate-csr أو ولّد CSR من النظام أولاً.');
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($csrPath)) {
            throw new RuntimeException('ملف CSR غير موجود في التخزين المحلي.');
        }

        $csrPem = $disk->get($csrPath);
        if ($csrPem === false || trim($csrPem) === '') {
            throw new RuntimeException('تعذّر قراءة ملف CSR.');
        }

        $csrBase64 = base64_encode($csrPem);

        $environment = $setting->environment === 'production' ? 'production' : 'sandbox';
        $url = config('zatca.compliance_urls.'.$environment);
        if (! is_string($url) || $url === '') {
            throw new RuntimeException('عنوان Compliance غير مُعرّف في الإعدادات.');
        }

        $response = Http::timeout(120)
            ->withHeaders([
                'Accept-Version' => 'V2',
                'OTP' => $otp,
                'Content-Type' => 'application/json',
            ])
            ->acceptJson()
            ->post($url, ['csr' => $csrBase64]);

        if (! $response->successful()) {
            $body = $response->json();
            $msg = $this->formatZatcaHttpErrorBody($body, $response->body());

            throw new RuntimeException('فشل طلب الربط مع ZATCA (HTTP '.$response->status().'): '.$msg);
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('استجابة غير متوقعة من خادم ZATCA.');
        }

        $b64Token = $data['binarySecurityToken'] ?? null;
        $secret = $data['secret'] ?? null;
        $requestId = $data['requestID'] ?? $data['requestId'] ?? $data['compliance_request_id'] ?? null;

        if (! is_string($b64Token) || $b64Token === '') {
            throw new RuntimeException('الاستجابة لا تتضمن binarySecurityToken.');
        }

        $certPem = $this->normalizeZatcaCertificateToPem($b64Token);

        $privateKeyPem = $this->resolvePrivateKeyPemFromSetting($setting, $disk);
        if ($privateKeyPem === '') {
            throw new RuntimeException('لم يُعثر على المفتاح الخاص المرتبط بإعدادات الفوترة (private_key_path أو private_key).');
        }

        $setting->certificate = $certPem;
        $setting->private_key = $privateKeyPem;
        $setting->request_id = is_string($requestId) ? $requestId : null;
        $setting->compliance_secret = is_string($secret) ? $secret : null;
        $setting->otp = null;
        $setting->save();

        return $setting->refresh();
    }

    /**
     * تحويل binarySecurityToken من الاستجابة إلى PEM يقبلها {@see Certificate}.
     */
    private function normalizeZatcaCertificateToPem(string $binarySecurityToken): string
    {
        $trimmed = trim($binarySecurityToken);
        if (str_contains($trimmed, '-----BEGIN')) {
            return $trimmed;
        }

        $der = base64_decode($trimmed, true);
        if ($der === false) {
            throw new InvalidArgumentException('شهادة ZATCA (binarySecurityToken) غير صالحة كـ Base64.');
        }

        $resource = openssl_x509_read($der);
        if ($resource === false) {
            throw new InvalidArgumentException('تعذّر قراءة شهادة X509 من الاستجابة.');
        }

        $pem = '';
        if (! openssl_x509_export($resource, $pem)) {
            throw new RuntimeException('تعذّر تصدير الشهادة إلى PEM.');
        }

        return $pem;
    }

    private function resolvePrivateKeyPemFromSetting(EinvoiceSetting $setting, \Illuminate\Contracts\Filesystem\Filesystem $disk): string
    {
        if (is_string($setting->private_key) && trim($setting->private_key) !== '') {
            return $setting->private_key;
        }

        $keyPath = $setting->private_key_path;
        if ($keyPath === null || $keyPath === '' || ! $disk->exists($keyPath)) {
            return '';
        }

        $pem = $disk->get($keyPath);

        return is_string($pem) ? $pem : '';
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function formatZatcaHttpErrorBody(?array $body, string $raw): string
    {
        if ($body === null) {
            return Str::limit($raw, 500, '…');
        }

        if (isset($body['message']) && is_string($body['message'])) {
            return $body['message'];
        }

        if (isset($body['errors']) && is_array($body['errors'])) {
            $first = $body['errors'][0] ?? null;
            if (is_array($first) && isset($first['message']) && is_string($first['message'])) {
                return $first['message'];
            }
            if (is_string($first)) {
                return $first;
            }
        }

        return Str::limit(json_encode($body, JSON_UNESCAPED_UNICODE) ?: $raw, 500, '…');
    }

    /**
     * معرّف المنشأة ZATCA (UID): 15 رقماً يبدأ بـ 3 وينتهي بـ 3.
     */
    private function normalizeZatcaOrganizationIdentifier(?string $vat): string
    {
        $digits = preg_replace('/\D/', '', (string) $vat) ?? '';

        if (strlen($digits) !== 15 || ! str_starts_with($digits, '3') || ! str_ends_with($digits, '3')) {
            throw new InvalidArgumentException(
                'الرقم الضريبي لـ ZATCA يجب أن يكون 15 رقماً يبدأ بـ 3 وينتهي بـ 3. ضبط «zatca_tax_number» في الفوترة الإلكترونية أو «tax_number» في إعدادات الشركة.',
            );
        }

        return $digits;
    }

    private function mapEinvoiceEnvironmentToCsrEnv(string $environment): string
    {
        return match ($environment) {
            'production' => CSRRequest::PRODUCTION,
            default => CSRRequest::SANDBOX,
        };
    }

    /**
     * عند كون الرقم الحادي عشر من UID = 1، تتطلّب المكتبة/الدليل أن يكون organizationalUnitName مكوّناً من 10 أرقام.
     */
    private function resolveOrganizationalUnitName(string $uid, ?CompanySetting $company, EinvoiceSetting $setting): string
    {
        $eleventhIsOne = strlen($uid) >= 11 && $uid[10] === '1';

        if ($eleventhIsOne) {
            $digits = preg_replace('/\D/', '', (string) ($company?->commercial_register ?? '')) ?? '';
            $ten = substr(str_pad($digits, 10, '0', STR_PAD_LEFT), -10);
            if (strlen($ten) !== 10 || ! ctype_digit($ten)) {
                $ten = str_pad((string) ($setting->id % 10_000_000_000), 10, '0', STR_PAD_LEFT);
            }

            return $ten;
        }

        return $this->sanitizeOpenSslDnField(
            $company?->commercial_register ?: 'MAIN',
            fallback: 'MAIN',
            maxLength: 64,
        );
    }

    /**
     * حقول DN في إعداد OpenSSL للمكتبة تُعرّف utf8 = no؛ نقلّص الأحرف غير الآمنة ونحوّل تقريباً إلى ASCII لتفادي فشل openssl.
     */
    private function sanitizeOpenSslDnField(?string $value, string $fallback, int $maxLength = 128): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            $raw = $fallback;
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $raw);
            if ($converted !== false && $converted !== '') {
                $raw = $converted;
            }
        }

        $raw = preg_replace('/[^\x20-\x7E]+/', ' ', $raw) ?? $raw;
        $raw = preg_replace('/\s+/', ' ', trim($raw)) ?? $raw;

        if ($raw === '') {
            $raw = $fallback;
        }

        return Str::limit($raw, $maxLength, '');
    }

    public function makeCertificate(string $x509Pem, string $ecPrivateKeyPem, ?string $apiSecretKey = null): Certificate
    {
        $certificate = new Certificate($x509Pem, $ecPrivateKeyPem);
        if ($apiSecretKey !== null && $apiSecretKey !== '') {
            $certificate->setSecretKey($apiSecretKey);
        }

        return $certificate;
    }

    /**
     * توقيع فاتورة UBL غير موقّعة (بدون امتداد UBL النهائي وبدون QR فعلي — الحزمة تُعيد إدراجها).
     */
    public function signUnsignedUblXml(string $unsignedUblXml, Certificate $certificate): SignedZatcaInvoice
    {
        return (new InvoiceSign($unsignedUblXml, $certificate))->sign();
    }

    /**
     * قراءة الشهادة من العمود `certificate` بعد الـ Onboarding، والمفتاح من `private_key` أو من الملف عبر `private_key_path`.
     */
    public function signUnsignedUblXmlUsingEinvoiceSetting(
        string $unsignedUblXml,
        EinvoiceSetting $setting,
        ?string $x509Pem = null,
        ?string $apiSecretKey = null,
    ): SignedZatcaInvoice {
        $x509Pem ??= is_string($setting->certificate) ? $setting->certificate : null;
        if ($x509Pem === null || trim($x509Pem) === '') {
            throw new RuntimeException('لم تُضبط شهادة التوقيع. أكمل الربط مع ZATCA من إعدادات الفوترة الإلكترونية (Onboarding).');
        }

        $disk = Storage::disk('local');
        $privateKeyPem = $this->resolvePrivateKeyPemFromSetting($setting, $disk);
        if ($privateKeyPem === '') {
            throw new RuntimeException('تعذّر قراءة المفتاح الخاص من قاعدة البيانات أو من التخزين المحلي (private_key / private_key_path).');
        }

        $apiSecretKey ??= is_string($setting->compliance_secret) ? $setting->compliance_secret : null;

        $certificate = $this->makeCertificate($x509Pem, $privateKeyPem, $apiSecretKey);

        return $this->signUnsignedUblXml($unsignedUblXml, $certificate);
    }

    /**
     * يحوّل فاتورة المبيعات إلى UBL 2.1 مبسّط (فاتورة ضريبية 388 / name=0200000) مع الحقول الإلزامية لمسار ZATCA،
     * يمرّر الناتج على `Salla\ZATCA\Helpers\UXML` لضبط التنسيق وحساب تجزئة ما قبل التوقيع كما في `InvoiceSign`.
     *
     * @return array{xml: string, invoice_uuid: string, unsigned_invoice_hash_base64: string}
     */
    public function mapSalesInvoiceToUbl21Xml(
        SalesInvoice $invoice,
        EinvoiceSetting $einvoice,
        int $invoiceCounterValue,
        ?string $previousInvoiceHashBase64 = null,
        ?CompanySetting $company = null,
        ?string $invoiceUuid = null,
    ): array {
        $previousInvoiceHashBase64 ??= self::DEFAULT_FIRST_PREVIOUS_INVOICE_HASH_BASE64;

        $sellerVat = $einvoice->zatca_tax_number ?: null;
        if ($sellerVat === null || $sellerVat === '') {
            throw new InvalidArgumentException('يجب ضبط الرقم الضريبي للبائع (zatca_tax_number) في إعدادات الفوترة الإلكترونية.');
        }

        $company ??= CompanySetting::forTenant((int) $invoice->user_id);
        $sellerName = $einvoice->zatca_seller_name
            ?: ($company?->name ?? 'Seller');
        $crn = $company?->commercial_register ?: '0000000000';

        $invoice->loadMissing(['customer', 'items.item']);
        $customer = $invoice->customer;
        if (! $customer) {
            throw new InvalidArgumentException('الفاتورة بدون عميل؛ لا يمكن بناء UBL.');
        }

        $buyerVat = $customer->vat_number ?: $customer->tax_number;
        if ($buyerVat === null || trim((string) $buyerVat) === '') {
            throw new InvalidArgumentException('يجب ضبط الرقم الضريبي للعميل (vat_number / tax_number) لبناء فاتورة ZATCA.');
        }

        $buyerName = $customer->name_ar ?: $customer->name;

        $invoiceUuid ??= $this->deterministicSalesInvoiceUuid($invoice);
        $issueDate = $invoice->date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $issueTimeUtc = ($invoice->created_at ?? now())->clone()->timezone('UTC')->format('H:i:s');
        $invoiceId = $invoice->reference ?: 'SINV-'.$invoice->id;

        $total = round((float) $invoice->total, 2);
        $vatAmount = round((float) $invoice->vat_amount, 2);
        $vatRate = round((float) $invoice->vat_rate, 2);
        $taxExclusive = round($total - $vatAmount, 2);

        $lines = $invoice->items;
        if ($lines->isEmpty()) {
            throw new InvalidArgumentException('الفاتورة لا تحتوي بنوداً.');
        }

        $sumLineTotal = $lines->sum(fn ($row) => (float) $row->line_total);
        if ($sumLineTotal <= 0) {
            throw new InvalidArgumentException('مجموع بنود الفاتورة غير صالح.');
        }

        $lineParts = [];
        $allocatedNet = 0.0;
        $allocatedTax = 0.0;
        $count = $lines->count();
        $idx = 0;

        foreach ($lines as $row) {
            $idx++;
            $lineTotal = round((float) $row->line_total, 2);
            $isLast = $idx === $count;

            if ($isLast) {
                $lineNet = round($taxExclusive - $allocatedNet, 2);
                $lineTax = round($vatAmount - $allocatedTax, 2);
            } else {
                $ratio = $lineTotal / $sumLineTotal;
                $lineNet = round($taxExclusive * $ratio, 2);
                $lineTax = round($vatAmount * $ratio, 2);
                $allocatedNet += $lineNet;
                $allocatedTax += $lineTax;
            }

            $itemLabel = $row->item
                ? (string) ($row->item->name_ar ?: $row->item->name_en ?: $row->item->code)
                : 'Item '.$row->id;

            $taxCategory = $vatRate > 0 ? 'S' : 'Z';
            $taxPercentXml = $vatRate > 0
                ? $this->xmlAmount($vatRate)
                : '0';

            $lineParts[] = $this->buildInvoiceLineXml(
                id: (string) $idx,
                quantity: (float) $row->quantity,
                lineExtensionAmount: $lineNet,
                lineTaxAmount: $lineTax,
                roundingAmount: $lineTotal,
                itemName: $itemLabel,
                taxCategoryId: $taxCategory,
                taxPercent: $taxPercentXml,
                unitPrice: $lineTotal / max((float) $row->quantity, 0.00001),
            );
        }

        $invoiceLinesXml = implode("\n", $lineParts);

        $sellerStreet = $company?->address ?: '—';
        $buyerStreet = $customer->address ?: '—';

        $documentTaxCategory = $vatRate > 0 ? 'S' : 'Z';
        $documentTaxPercent = $vatRate > 0 ? $this->xmlAmount($vatRate) : '0';

        $rawXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
    <ext:UBLExtensions/>
    <cbc:ProfileID>reporting:1.0</cbc:ProfileID>
    <cbc:ID>{$this->xmlEscape($invoiceId)}</cbc:ID>
    <cbc:UUID>{$this->xmlEscape($invoiceUuid)}</cbc:UUID>
    <cbc:IssueDate>{$this->xmlEscape($issueDate)}</cbc:IssueDate>
    <cbc:IssueTime>{$this->xmlEscape($issueTimeUtc)}</cbc:IssueTime>
    <cbc:InvoiceTypeCode name="0200000">388</cbc:InvoiceTypeCode>
    <cbc:DocumentCurrencyCode>SAR</cbc:DocumentCurrencyCode>
    <cbc:TaxCurrencyCode>SAR</cbc:TaxCurrencyCode>
    <cac:AdditionalDocumentReference>
        <cbc:ID>ICV</cbc:ID>
        <cbc:UUID>{$this->xmlEscape((string) $invoiceCounterValue)}</cbc:UUID>
    </cac:AdditionalDocumentReference>
    <cac:AdditionalDocumentReference>
        <cbc:ID>PIH</cbc:ID>
        <cac:Attachment>
            <cbc:EmbeddedDocumentBinaryObject mimeCode="text/plain">{$this->xmlEscape($previousInvoiceHashBase64)}</cbc:EmbeddedDocumentBinaryObject>
        </cac:Attachment>
    </cac:AdditionalDocumentReference>
    <cac:AdditionalDocumentReference>
        <cbc:ID>QR</cbc:ID>
        <cac:Attachment>
            <cbc:EmbeddedDocumentBinaryObject mimeCode="text/plain">TEMP_QR_VALUE</cbc:EmbeddedDocumentBinaryObject>
        </cac:Attachment>
    </cac:AdditionalDocumentReference>
    <cac:Signature>
        <cbc:ID>urn:oasis:names:specification:ubl:signature:Invoice</cbc:ID>
        <cbc:SignatureMethod>urn:oasis:names:specification:ubl:dsig:enveloped:xades</cbc:SignatureMethod>
    </cac:Signature>
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="CRN">{$this->xmlEscape($crn)}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PostalAddress>
                <cbc:StreetName>{$this->xmlEscape($sellerStreet)}</cbc:StreetName>
                <cbc:BuildingNumber>0000</cbc:BuildingNumber>
                <cbc:PlotIdentification>0000</cbc:PlotIdentification>
                <cbc:CitySubdivisionName>الرياض</cbc:CitySubdivisionName>
                <cbc:CityName>الرياض</cbc:CityName>
                <cbc:PostalZone>00000</cbc:PostalZone>
                <cac:Country>
                    <cbc:IdentificationCode>SA</cbc:IdentificationCode>
                </cac:Country>
            </cac:PostalAddress>
            <cac:PartyTaxScheme>
                <cbc:CompanyID>{$this->xmlEscape($sellerVat)}</cbc:CompanyID>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:PartyTaxScheme>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName>{$this->xmlEscape($sellerName)}</cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>
    <cac:AccountingCustomerParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="TIN">{$this->xmlEscape($buyerVat)}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PostalAddress>
                <cbc:StreetName>{$this->xmlEscape($buyerStreet)}</cbc:StreetName>
                <cbc:BuildingNumber>0000</cbc:BuildingNumber>
                <cbc:PlotIdentification>0000</cbc:PlotIdentification>
                <cbc:CitySubdivisionName>الرياض</cbc:CitySubdivisionName>
                <cbc:CityName>الرياض</cbc:CityName>
                <cbc:PostalZone>00000</cbc:PostalZone>
                <cac:Country>
                    <cbc:IdentificationCode>SA</cbc:IdentificationCode>
                </cac:Country>
            </cac:PostalAddress>
            <cac:PartyTaxScheme>
                <cbc:CompanyID>{$this->xmlEscape($buyerVat)}</cbc:CompanyID>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:PartyTaxScheme>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName>{$this->xmlEscape($buyerName)}</cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingCustomerParty>
    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="SAR">{$this->xmlAmount($vatAmount)}</cbc:TaxAmount>
    </cac:TaxTotal>
    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="SAR">{$this->xmlAmount($vatAmount)}</cbc:TaxAmount>
        <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID="SAR">{$this->xmlAmount($taxExclusive)}</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID="SAR">{$this->xmlAmount($vatAmount)}</cbc:TaxAmount>
            <cac:TaxCategory>
                <cbc:ID>{$documentTaxCategory}</cbc:ID>
                <cbc:Percent>{$documentTaxPercent}</cbc:Percent>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:TaxCategory>
        </cac:TaxSubtotal>
    </cac:TaxTotal>
    <cac:LegalMonetaryTotal>
        <cbc:LineExtensionAmount currencyID="SAR">{$this->xmlAmount($taxExclusive)}</cbc:LineExtensionAmount>
        <cbc:TaxExclusiveAmount currencyID="SAR">{$this->xmlAmount($taxExclusive)}</cbc:TaxExclusiveAmount>
        <cbc:TaxInclusiveAmount currencyID="SAR">{$this->xmlAmount($total)}</cbc:TaxInclusiveAmount>
        <cbc:AllowanceTotalAmount currencyID="SAR">0.00</cbc:AllowanceTotalAmount>
        <cbc:PrepaidAmount currencyID="SAR">0.00</cbc:PrepaidAmount>
        <cbc:PayableAmount currencyID="SAR">{$this->xmlAmount($total)}</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>
{$invoiceLinesXml}
</Invoice>
XML;

        $uxml = UXML::fromString($rawXml);
        $normalizedXml = trim($uxml->asXML('1.0', 'UTF-8', false));
        $hashSource = UXML::fromString($normalizedXml);

        return [
            'xml' => $normalizedXml,
            'invoice_uuid' => $invoiceUuid,
            'unsigned_invoice_hash_base64' => $hashSource->getXmlHash(),
        ];
    }

    /**
     * بناء XML فاتورة ضريبية مبسّطة جاهز للتوقيع عبر InvoiceSign (نفس مسار mapSalesInvoiceToUbl21Xml).
     *
     * @param  int  $invoiceCounterValue  قيمة عداد الفواتير (ICV) المطلوبة من ZATCA.
     * @param  string|null  $previousInvoiceHashBase64  تجزئة الفاتورة السابقة (PIH) بصيغة Base64؛ للأولى استخدم DEFAULT_FIRST_PREVIOUS_INVOICE_HASH_BASE64.
     */
    public function buildUnsignedSimplifiedTaxInvoiceXml(
        SalesInvoice $invoice,
        EinvoiceSetting $einvoice,
        int $invoiceCounterValue,
        ?string $previousInvoiceHashBase64 = null,
        ?CompanySetting $company = null,
        ?string $invoiceUuid = null,
    ): string {
        return $this->mapSalesInvoiceToUbl21Xml(
            $invoice,
            $einvoice,
            $invoiceCounterValue,
            $previousInvoiceHashBase64,
            $company,
            $invoiceUuid,
        )['xml'];
    }

    private function deterministicSalesInvoiceUuid(SalesInvoice $invoice): string
    {
        return Uuid::uuid5(
            Uuid::fromString(self::SALES_INVOICE_UUID_NAMESPACE),
            'sales-invoice:'.$invoice->getKey(),
        )->toString();
    }

    /**
     * توليد وفقيمة وتوقيع في خطوة واحدة (XML غير موقّع → موقّع).
     */
    public function buildAndSignSimplifiedTaxInvoice(
        SalesInvoice $invoice,
        EinvoiceSetting $einvoice,
        ?string $x509Pem,
        int $invoiceCounterValue,
        ?string $previousInvoiceHashBase64 = null,
        ?string $apiSecretKey = null,
        ?CompanySetting $company = null,
    ): SignedZatcaInvoice {
        $unsigned = $this->mapSalesInvoiceToUbl21Xml(
            $invoice,
            $einvoice,
            $invoiceCounterValue,
            $previousInvoiceHashBase64,
            $company,
        )['xml'];

        return $this->signUnsignedUblXmlUsingEinvoiceSetting($unsigned, $einvoice, $x509Pem, $apiSecretKey);
    }

    private function buildInvoiceLineXml(
        string $id,
        float $quantity,
        float $lineExtensionAmount,
        float $lineTaxAmount,
        float $roundingAmount,
        string $itemName,
        string $taxCategoryId,
        string $taxPercent,
        float $unitPrice,
    ): string {
        return <<<XML
    <cac:InvoiceLine>
        <cbc:ID>{$this->xmlEscape($id)}</cbc:ID>
        <cbc:InvoicedQuantity unitCode="PCE">{$this->xmlAmount($quantity)}</cbc:InvoicedQuantity>
        <cbc:LineExtensionAmount currencyID="SAR">{$this->xmlAmount($lineExtensionAmount)}</cbc:LineExtensionAmount>
        <cac:TaxTotal>
            <cbc:TaxAmount currencyID="SAR">{$this->xmlAmount($lineTaxAmount)}</cbc:TaxAmount>
            <cbc:RoundingAmount currencyID="SAR">{$this->xmlAmount($roundingAmount)}</cbc:RoundingAmount>
        </cac:TaxTotal>
        <cac:Item>
            <cbc:Name>{$this->xmlEscape($itemName)}</cbc:Name>
            <cac:ClassifiedTaxCategory>
                <cbc:ID>{$this->xmlEscape($taxCategoryId)}</cbc:ID>
                <cbc:Percent>{$taxPercent}</cbc:Percent>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:ClassifiedTaxCategory>
        </cac:Item>
        <cac:Price>
            <cbc:PriceAmount currencyID="SAR">{$this->xmlAmount($unitPrice)}</cbc:PriceAmount>
        </cac:Price>
    </cac:InvoiceLine>
XML;
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function xmlAmount(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
