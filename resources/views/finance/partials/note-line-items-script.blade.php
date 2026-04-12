<script>
    (() => {
        const linesBody = document.getElementById(@json($tableBodyId));
        const addLineBtn = document.getElementById(@json($addButtonId));
        const subtotalText = document.getElementById(@json($subtotalId));
        const taxText = document.getElementById(@json($taxId));
        const grandTotalText = document.getElementById(@json($grandTotalId));
        const partySelect = document.getElementById(@json($partySelectId ?? ''));
        const invoiceSelect = document.getElementById(@json($invoiceSelectId ?? ''));
        const invoicePartyAttr = @json($invoicePartyAttr ?? 'data-party-id');
        const currencyLabel = @json($currencyLabel ?? 'SAR');
        const oldLines = @json($lineDefaults ?? []);

        if (!linesBody || !addLineBtn) return;

        function formatMoney(value) {
            return `${currencyLabel} ${Number(value || 0).toFixed(2)}`;
        }

        function recalcTotals() {
            let subtotal = 0;
            let totalTax = 0;

            linesBody.querySelectorAll('tr').forEach((row) => {
                const qty = Number(row.querySelector('[data-field="quantity"]')?.value || 0);
                const price = Number(row.querySelector('[data-field="unit_price"]')?.value || 0);
                const taxPercent = Number(row.querySelector('[data-field="tax_percent"]')?.value || 0);

                const lineSubtotal = qty * price;
                const lineTax = lineSubtotal * taxPercent / 100;
                const lineTotal = lineSubtotal + lineTax;

                subtotal += lineSubtotal;
                totalTax += lineTax;

                const lineTotalCell = row.querySelector('[data-line-total]');
                if (lineTotalCell) {
                    lineTotalCell.textContent = formatMoney(lineTotal);
                }
            });

            if (subtotalText) subtotalText.textContent = formatMoney(subtotal);
            if (taxText) taxText.textContent = formatMoney(totalTax);
            if (grandTotalText) grandTotalText.textContent = formatMoney(subtotal + totalTax);
        }

        function bindRowEvents(row) {
            row.querySelectorAll('input').forEach((input) => {
                input.addEventListener('input', recalcTotals);
            });

            const deleteBtn = row.querySelector('[data-delete-line]');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', () => {
                    if (linesBody.querySelectorAll('tr').length === 1) {
                        row.querySelectorAll('input').forEach((input) => {
                            input.value = input.dataset.field === 'quantity' ? '1' : '0';
                            if (input.dataset.field === 'description') input.value = '';
                        });
                    } else {
                        row.remove();
                    }
                    reindexRows();
                    recalcTotals();
                });
            }
        }

        function reindexRows() {
            linesBody.querySelectorAll('tr').forEach((row, index) => {
                row.querySelectorAll('input').forEach((input) => {
                    const field = input.dataset.field;
                    input.name = `lines[${index}][${field}]`;
                });
            });
        }

        function buildRow(line = {}) {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.innerHTML = `
                <td class="px-4 py-3">
                    <input data-field="description" type="text" value="${line.description ?? ''}" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </td>
                <td class="px-4 py-3">
                    <input data-field="quantity" type="number" inputmode="decimal" min="0.0001" step="any" value="${line.quantity ?? 1}" class="h-10 w-28 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </td>
                <td class="px-4 py-3">
                    <input data-field="unit_price" type="number" inputmode="decimal" min="0" step="any" value="${line.unit_price ?? 0}" class="h-10 w-32 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </td>
                <td class="px-4 py-3">
                    <input data-field="tax_percent" type="number" inputmode="decimal" min="0" max="100" step="any" value="${line.tax_percent ?? 15}" class="h-10 w-24 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                </td>
                <td class="px-4 py-3 font-semibold text-gray-800" data-line-total>${formatMoney(0)}</td>
                <td class="px-4 py-3">
                    <button type="button" data-delete-line class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-white text-red-500 hover:bg-red-50 hover:text-red-600" title="حذف السطر">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            linesBody.appendChild(row);
            bindRowEvents(row);
            reindexRows();
            recalcTotals();
        }

        function filterInvoicesByParty() {
            if (!partySelect || !invoiceSelect) return;
            const selectedParty = partySelect.value;
            Array.from(invoiceSelect.options).forEach((option, idx) => {
                if (idx === 0) return;
                const optionParty = option.getAttribute(invoicePartyAttr);
                option.hidden = Boolean(selectedParty) && optionParty !== selectedParty;
            });
            if (invoiceSelect.selectedOptions[0]?.hidden) {
                invoiceSelect.value = '';
            }
        }

        addLineBtn.addEventListener('click', () => buildRow());
        if (partySelect && invoiceSelect) {
            partySelect.addEventListener('change', filterInvoicesByParty);
        }

        linesBody.innerHTML = '';
        oldLines.forEach((line) => buildRow(line));
        if (!oldLines.length) {
            buildRow();
        }
        filterInvoicesByParty();
    })();
</script>

