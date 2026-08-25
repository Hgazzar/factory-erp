{{-- قوائم الإجراءات (⋯): موضع ثابت + نقل القائمة إلى body عند الفتح (تفادي القص داخل الجداول) — موحّد لكل الشاشات --}}
@once
<style>
    .erp-actions-menu {
        overflow: visible !important;
        max-height: none !important;
        -webkit-overflow-scrolling: auto;
    }
    .erp-actions-item-icon {
        background: color-mix(in srgb, var(--nursery-primary, #64748b) 14%, #fff);
        color: var(--nursery-primary, #475569);
    }
    .erp-actions-item-hover:hover {
        background: color-mix(in srgb, var(--nursery-primary, #64748b) 9%, #fff);
    }
</style>
@endonce
@once
<script>
(function () {
    function positionMenu(trigger, menu) {
        var rect = trigger.getBoundingClientRect();
        var gap = 8;
        var pad = 12;
        menu.style.position = 'fixed';
        menu.style.zIndex = '9999';
        var w = menu.offsetWidth || 0;
        if (w < 8) w = 220;
        var isRtl = window.getComputedStyle(document.documentElement).direction === 'rtl'
            || window.getComputedStyle(document.body).direction === 'rtl';
        var left;
        if (isRtl) {
            left = rect.left - gap - w;
            if (left < pad) {
                left = rect.right + gap;
            }
            if (left + w > window.innerWidth - pad) {
                left = window.innerWidth - pad - w;
            }
            if (left < pad) {
                left = pad;
            }
        } else {
            left = rect.right + gap;
            if (left + w > window.innerWidth - pad) {
                left = window.innerWidth - pad - w;
            }
            if (left < pad) {
                left = pad;
            }
        }
        menu.style.left = left + 'px';
        menu.style.right = 'auto';

        var h = menu.offsetHeight || 0;
        var spaceBelow = window.innerHeight - rect.bottom - gap - pad;
        var spaceAbove = rect.top - gap - pad;
        var openUp = h > 0 && h > spaceBelow && spaceAbove >= spaceBelow;
        if (openUp) {
            menu.style.top = 'auto';
            menu.style.bottom = (window.innerHeight - rect.top + gap) + 'px';
        } else {
            menu.style.top = (rect.bottom + gap) + 'px';
            menu.style.bottom = 'auto';
        }
    }

    function restoreMenuToCell(menu) {
        if (!menu._erpPlaceholder || !menu._erpPlaceholder.parentNode) return;
        menu._erpPlaceholder.parentNode.replaceChild(menu, menu._erpPlaceholder);
        delete menu._erpPlaceholder;
    }

    function closeAllMenus() {
        document.querySelectorAll('.erp-actions-menu').forEach(function (m) {
            m.classList.add('hidden');
            restoreMenuToCell(m);
            m.style.position = '';
            m.style.zIndex = '';
            m.style.top = '';
            m.style.left = '';
            m.style.right = '';
            m.style.bottom = '';
        });
        document.querySelectorAll('.erp-actions-trigger[aria-expanded="true"]').forEach(function (b) {
            b.setAttribute('aria-expanded', 'false');
        });
    }

    function openMenu(trigger, menu) {
        if (!menu._erpPlaceholder) {
            menu._erpPlaceholder = document.createComment('erp-menu-placeholder');
            menu.parentNode.insertBefore(menu._erpPlaceholder, menu);
        }
        document.body.appendChild(menu);
        menu.classList.remove('hidden');
        positionMenu(trigger, menu);
        trigger.setAttribute('aria-expanded', 'true');
    }

    var repositionScheduled = false;
    function repositionOpenMenus() {
        if (repositionScheduled) return;
        repositionScheduled = true;
        requestAnimationFrame(function () {
            repositionScheduled = false;
            document.querySelectorAll('.erp-actions-menu:not(.hidden)').forEach(function (menu) {
                var id = menu.id;
                var trigger = document.querySelector('[data-actions-menu="' + id + '"]');
                if (trigger) positionMenu(trigger, menu);
            });
        });
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('.erp-actions-menu [data-bs-toggle="modal"]')) {
            closeAllMenus();
        }
        var trigger = e.target.closest('.erp-actions-trigger');
        if (!trigger) {
            if (!e.target.closest('.erp-actions-menu')) closeAllMenus();
            return;
        }
        var menuId = trigger.getAttribute('data-actions-menu');
        var menu = menuId ? document.getElementById(menuId) : null;
        if (!menu) return;
        var isOpen = !menu.classList.contains('hidden');
        closeAllMenus();
        if (!isOpen) openMenu(trigger, menu);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAllMenus();
    });

    window.addEventListener('resize', repositionOpenMenus);
    window.addEventListener('scroll', repositionOpenMenus, true);

    document.addEventListener('show.bs.modal', closeAllMenus);

    window.closeErpActionMenus = closeAllMenus;
    window.coaCloseActionMenus = closeAllMenus;

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.account-copy-btn, [data-erp-copy-text]');
        if (!btn) return;
        var text = btn.getAttribute('data-copy-text') || btn.getAttribute('data-erp-copy-text') || '';
        if (!text) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).catch(function () {});
        } else {
            var input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            try { document.execCommand('copy'); } catch (err) {}
            document.body.removeChild(input);
        }
        closeAllMenus();
    });
})();
</script>
@endonce
