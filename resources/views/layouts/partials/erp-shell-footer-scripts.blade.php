    @stack('modals')

    <div id="info-hint-popup" aria-hidden="true"></div>
    @livewire('notifications')
    @livewireScripts
    @filamentScripts
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // تعطيل تنبيهات المتصفح الإنجليزية (HTML5) — الاعتماد على التحقق من الخادم والتنبيه الموحّد
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
                    var popupHeight = 80;
                    var gap = 8;
                    popup.style.top = (rect.top - popupHeight - gap) + 'px';
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

            // أزرار الاستيراد: فتح المودالات يدوياً لضمان العمل حتى لو تعطل أو تعارض سكربت آخر
            document.querySelectorAll('button[data-import-modal]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    var target = btn.getAttribute('data-bs-target');
                    if (!target) {
                        return;
                    }
                    var modalEl = document.querySelector(target);
                    if (!modalEl || !window.bootstrap || !bootstrap.Modal) {
                        return;
                    }
                    e.preventDefault();
                    var instance = bootstrap.Modal.getOrCreateInstance(modalEl);
                    instance.show();
                });
            });

            // أيقونة الشبكة: الوصول السريع للوحدات
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

            // روابط الوحدات داخل المودال: تنقل صريح (يعمل حتى لو منع dismiss التنقل)
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

            // أزرار التصدير: إذا كان الزر يحمل data-export-url يتم التوجيه إليه
            document.querySelectorAll('[data-export-url]').forEach(function(el) {
                el.addEventListener('click', function(e) {
                    var url = this.getAttribute('data-export-url');
                    if (url) { e.preventDefault(); window.location.href = url; }
                });
            });
        });
    </script>
    <x-erp-action-menus-script />
    @stack('scripts')
