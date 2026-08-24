@stack('modals')

<div id="info-hint-popup" aria-hidden="true"></div>
{{-- حضانة: بدون Livewire/Filament — أخف وأسرع في كل تنقّل --}}
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(function (form) {
            var method = (form.getAttribute('method') || 'get').toLowerCase();
            if (method !== 'post') {
                return;
            }
            if (!form.hasAttribute('data-html5-validate')) {
                form.setAttribute('novalidate', 'novalidate');
            }
        });

        document.querySelectorAll('[data-auto-dismiss-success]').forEach(function (el) {
            window.setTimeout(function () {
                el.style.opacity = '0';
                window.setTimeout(function () { el.remove(); }, 500);
            }, 5000);
        });

        var popup = document.getElementById('info-hint-popup');
        if (popup) {
            function showHint(el) {
                var text = el.getAttribute('data-hint');
                if (!text) return;
                popup.textContent = text;
                popup.classList.add('is-visible');
                var rect = el.getBoundingClientRect();
                popup.style.top = (rect.top - 88) + 'px';
                popup.style.left = (rect.left + rect.width / 2) + 'px';
                popup.style.transform = 'translate(-50%, 0)';
            }
            function hideHint() {
                popup.classList.remove('is-visible');
            }
            document.addEventListener('mouseover', function (e) {
                var t = e.target.closest('.info-hint-trigger');
                if (t) showHint(t); else hideHint();
            });
            document.addEventListener('mouseout', function (e) {
                var from = e.target.closest('.info-hint-trigger');
                var to = e.relatedTarget && e.relatedTarget.closest('.info-hint-trigger');
                if (from && !to) hideHint();
            });
        }

        document.querySelectorAll('[data-erp-module-launcher]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                var target = btn.getAttribute('data-bs-target');
                if (!target) return;
                var modalEl = document.querySelector(target);
                if (!modalEl || !window.bootstrap || !bootstrap.Modal) return;
                e.preventDefault();
                e.stopPropagation();
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            });
        });

        var moduleModal = document.getElementById('erpModuleLauncherModal');
        if (moduleModal) {
            moduleModal.addEventListener('click', function (e) {
                var a = e.target.closest('a.erp-module-quick-card');
                if (!a || !a.getAttribute('href')) return;
                var href = a.getAttribute('href');
                if (href === '#' || href === '') return;
                e.preventDefault();
                if (window.bootstrap && bootstrap.Modal) {
                    var inst = bootstrap.Modal.getInstance(moduleModal);
                    if (inst) inst.hide();
                }
                window.location.assign(href);
            });
        }
    });
</script>
@stack('scripts')
