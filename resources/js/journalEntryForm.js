/**
 * Journal create/edit state on window — must load before Livewire runs Alpine.start().
 */
window.journalEntryForm = function (config) {
    config = config || {};
    const initial = config.initial;
    const headerDefaults = config.headerDefaults || {};
    const accountOptions = Array.isArray(config.accountOptions) ? config.accountOptions : [];

    let nextLineId = 1;

    const defaultLine = () => ({
        _lid: nextLineId++,
        account_id: '',
        description: '',
        cost_center: '',
        debit: 0,
        credit: 0,
    });

    const hasInitial = initial && initial.lines && initial.lines.length >= 2;

    const initialLines = hasInitial
        ? initial.lines.map((l) => ({
              _lid: nextLineId++,
              account_id: String(l.account_id || ''),
              description: l.description ?? '',
              cost_center: l.cost_center ?? '',
              debit: parseFloat(l.debit) || 0,
              credit: parseFloat(l.credit) || 0,
          }))
        : [defaultLine(), defaultLine()];

    return {
        header: hasInitial
            ? {
                  date: initial.date || '',
                  reference: initial.reference || '',
                  description: initial.description || '',
              }
            : {
                  date: headerDefaults.date != null ? String(headerDefaults.date) : '',
                  reference: headerDefaults.reference != null ? String(headerDefaults.reference) : '',
                  description: headerDefaults.description != null ? String(headerDefaults.description) : '',
              },
        lines: initialLines,
        addLine() {
            const row = defaultLine();
            this.lines = [...this.lines, row];
        },
        removeLine(index) {
            if (this.lines.length <= 2) {
                return;
            }
            this.lines = this.lines.filter((_, i) => i !== index);
        },
        syncCredit(index, changed) {
            const line = this.lines[index];
            if (changed === 'debit' && line.debit > 0 && line.credit > 0) {
                line.credit = 0;
            }
            if (changed === 'credit' && line.credit > 0 && line.debit > 0) {
                line.debit = 0;
            }
        },
        sumAmount(field) {
            return this.lines.reduce((sum, l) => sum + (parseFloat(l[field]) || 0), 0);
        },
        get totalDebit() {
            return this.sumAmount('debit');
        },
        get totalCredit() {
            return this.sumAmount('credit');
        },
        get difference() {
            return this.totalDebit - this.totalCredit;
        },
        get differenceDisplay() {
            const diff = this.difference;
            if (Math.abs(diff) < 0.0001) {
                return '0.00';
            }
            return diff.toFixed(2);
        },
        get balanced() {
            const debit = this.totalDebit;
            const credit = this.totalCredit;
            return debit > 0 && Math.abs(debit - credit) < 0.0001;
        },
        accountOptions,
    };
};

/**
 * قائمة حسابات قابلة للبحث لسطر قيد (Alpine) — تُستخدم داخل journalEntryForm.
 */
window.journalLineAccountSearch = function (line, items, lineIndex) {
    const accountItems =
        Array.isArray(items) && items.length
            ? items
            : [
                  { v: '', l: 'اختر الحساب' },
              ];

    return {
        line,
        lineIndex: lineIndex,
        accountItems,
        open: false,
        q: '',
        panelTop: 0,
        panelLeft: 0,
        panelWidth: 0,
        positionPanel() {
            const btn = this.$refs.jTrigger;
            const panel = this.$refs.jPanel;
            if (!btn || !panel) {
                return;
            }
            const r = btn.getBoundingClientRect();
            this.panelWidth = Math.max(r.width, 160);
            this.panelTop = r.bottom + 4;
            this.panelLeft = r.left;
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => {
                    this.positionPanel();
                    if (this.$refs.jq) {
                        this.$refs.jq.focus();
                    }
                });
            }
        },
        close() {
            this.open = false;
            this.q = '';
        },
        pick(v) {
            this.line.account_id = String(v);
            this.close();
        },
        clearSel() {
            this.line.account_id = '';
            this.q = '';
        },
        display() {
            const it = this.accountItems.find((i) => String(i.v) === String(this.line.account_id || ''));
            return it ? it.l : 'اختر الحساب';
        },
        get filtered() {
            const qq = (this.q || '').trim().toLowerCase();
            if (!qq) {
                return this.accountItems;
            }
            return this.accountItems.filter((i) => {
                if (i.v === '') {
                    return true;
                }
                const lab = (i.l || '').toLowerCase();
                const val = String(i.v || '').toLowerCase();
                return lab.includes(qq) || val.includes(qq);
            });
        },
    };
};
