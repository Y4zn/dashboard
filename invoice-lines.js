document.addEventListener('DOMContentLoaded', function() {
    // Initialize lineIndex based on existing lines
    let lineIndex = document.querySelectorAll('#invoice-lines .invoice-line').length;
    const linesContainer = document.getElementById('invoice-lines');
    const addBtn = document.getElementById('add-line');

    addBtn.addEventListener('click', function() {
        const div = document.createElement('div');
        div.className = 'invoice-line';
        div.innerHTML = `
            <input type="text" name="lines[${lineIndex}][omschrijving]" placeholder="Omschrijving">
            <input type="number" name="lines[${lineIndex}][aantal]" placeholder="Aantal" step="1" min="1">
            <input type="number" name="lines[${lineIndex}][stuksprijs]" placeholder="Stuksprijs" step="0.01" min="0">
            <input type="number" name="lines[${lineIndex}][korting]" placeholder="Korting %" step="0.01" min="0" max="100">
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
