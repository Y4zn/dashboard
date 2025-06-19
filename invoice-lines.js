document.addEventListener('DOMContentLoaded', function() {
    // Initialize lineIndex based on existing lines
    let lineIndex = document.querySelectorAll('#invoice-lines .invoice-line').length;
    const linesContainer = document.getElementById('invoice-lines');
    const addBtn = document.getElementById('add-line');

    addBtn.addEventListener('click', function() {
        const div = document.createElement('div');
        div.className = 'invoice-line';
        div.innerHTML = `
            <input type="text" name="lines[${lineIndex}][omschrijving]" required>
            <input type="number" name="lines[${lineIndex}][aantal]" step="1" min="1" required>
            <input type="number" name="lines[${lineIndex}][stuksprijs]" step="1" min="0" required>
            <input type="number" name="lines[${lineIndex}][korting]" step="0.01" min="0" max="100">
            <button type="button" class="remove-line">Verwijder</button>`;
        linesContainer.appendChild(div);
        lineIndex++;
    });

    linesContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-line')) {
            e.target.parentElement.remove();
        }
    });

    // Show remove button if more than one line
    const observer = new MutationObserver(() => {
        const lines = linesContainer.querySelectorAll('.invoice-line');
        lines.forEach((line, idx) => {
            const btn = line.querySelector('.remove-line');
            btn.style.display = (lines.length > 1) ? 'inline-block' : 'none';
        });
    });
    observer.observe(linesContainer, { childList: true, subtree: false });

    // --- Live calculation of invoice total ---
    function calculateInvoiceTotal() {
        let total = 0;
        linesContainer.querySelectorAll('.invoice-line').forEach(function(line) {
            const qty = parseFloat(line.querySelector('input[name*="[aantal]"]')?.value || 0);
            const price = parseFloat(line.querySelector('input[name*="[stuksprijs]"]')?.value || 0);
            const discount = parseFloat(line.querySelector('input[name*="[korting]"]')?.value || 0);
            if (!isNaN(qty) && !isNaN(price)) {
                let lineTotal = qty * price * (1 - (isNaN(discount) ? 0 : discount) / 100);
                total += lineTotal;
            }
        });
        const totalField = document.getElementById('invoice-total');
        if (totalField) {
            totalField.value = total.toLocaleString('nl-NL', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }
    // Listen for changes on all invoice line inputs
    linesContainer.addEventListener('input', function(e) {
        if (e.target.matches('input')) {
            calculateInvoiceTotal();
        }
    });
    // Also recalculate when lines are added/removed
    const recalcObserver = new MutationObserver(calculateInvoiceTotal);
    recalcObserver.observe(linesContainer, { childList: true, subtree: false });
    // Initial calculation
    calculateInvoiceTotal();

    // Find the invoice form (edit or new)
    var invoiceForm = document.querySelector('form.card[action=""], form.card[action="dashboard.php"], form.card[action="/dashboard.php"]');
    if (invoiceForm && document.getElementById('invoice-lines')) {
        // Remove the submit handler here; reindexing will be handled in the confirmation modal logic in dashboard.js
    }
});

function reindexInvoiceLines() {
    const lines = document.querySelectorAll('#invoice-lines .invoice-line');
    lines.forEach((line, idx) => {
        line.querySelectorAll('input, select, textarea').forEach(input => {
            input.name = input.name.replace(/lines\[\d+\]/g, `lines[${idx}]`);
        });
    });
    // Reset lineIndex to the next available index
    if (typeof lineIndex !== 'undefined') {
        lineIndex = lines.length;
    }
}
