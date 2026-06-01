function prBilling() {
    return {
        cycle: 'monthly',
        isYearly() {
            return this.cycle === 'yearly';
        },
        price(plan) {
            return this.isYearly() ? plan.yearly : plan.monthly;
        },
        periodLabel() {
            return this.isYearly() ? 'سنوياً' : 'شهرياً';
        },
        monthlyEquivalent(plan) {
            if (!this.isYearly()) return null;
            return Math.round(plan.yearly / 12);
        },
    };
}

function prNav() {
    return {
        scrolled: false,
        init() {
            const onScroll = () => {
                this.scrolled = window.scrollY > 12;
            };
            onScroll();
            window.addEventListener('scroll', onScroll, { passive: true });
        },
    };
}

window.prBilling = prBilling;
window.prNav = prNav;

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
