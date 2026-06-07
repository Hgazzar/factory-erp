<?php

declare(strict_types=1);

namespace App\Support;

/**
 * مفاتيح المزايا البريميوم (tenant_features) — تُدار من لوحة السوبر أدمن حسب النيش.
 */
final class PremiumFeatureKeys
{
    // Retail
    public const RETAIL_MULTI_BRANCHES = 'retail_multi_branches';

    public const RETAIL_POS_DEVICE_LINK = 'retail_pos_device_link';

    public const RETAIL_WHATSAPP_AUTOMATION = 'retail_whatsapp_automation';

    // Manufacturing
    public const MANUFACTURING_SMART_PRODUCTION_ENTRY = 'manufacturing_smart_production_entry';

    public const MANUFACTURING_INVENTORY_AUTO_LINK = 'manufacturing_inventory_auto_link';

    public const MANUFACTURING_MACHINE_DOWNTIME = 'manufacturing_machine_downtime';

    // Medical clinics (بعض المفاتيح مستخدمة مسبقاً في موديول العيادات)
    public const CLINIC_MEDICAL_INSURANCE = 'clinic_medical_insurance';

    public const CLINIC_BRANCH_APPOINTMENTS = 'clinic_branch_appointments';

    public const CLINIC_WHATSAPP_AUTOMATION = 'clinic_whatsapp_automation';

    // Nurseries
    public const NURSERY_WHATSAPP_AUTOMATION = 'nursery_whatsapp_automation';

    public const NURSERY_SUBSCRIPTION_FINANCE = 'nursery_subscription_finance';

    public const NURSERY_PARENT_PORTAL = 'nursery_portal';

    /** مفتاح موحّد لبوابة أولياء الأمور (tenant_features). */
    public const NURSERY_PORTAL = 'nursery_portal';
}
