// DARK MODE TOGGLE
(function() {
    const btn = document.getElementById('darkModeToggle');
    if (!btn) return;
    function setDarkMode(on) {
        document.body.classList.toggle('dark-mode', on);
        if (on) {
            btn.innerHTML = '☀️ Licht';
            btn.style.color = '#18181b';
            btn.style.background = '#f3f4f6';
            localStorage.setItem('darkMode', '1');
        } else {
            btn.innerHTML = '🌙 Donker';
            btn.style.color = '#f3f4f6';
            btn.style.background = '#23232a';
            localStorage.removeItem('darkMode');
        }
    }
    btn.onclick = () => setDarkMode(!document.body.classList.contains('dark-mode'));
    setDarkMode(!!localStorage.getItem('darkMode'));
})();

// DELETE CONFIRMATION MODAL
(function() {
    document.querySelectorAll('.delete-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var modal = document.getElementById('confirmModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.dataset.href = this.dataset.href;
            }
        });
    });
    var yes = document.getElementById('confirmYes');
    var no = document.getElementById('confirmNo');
    if (yes && no) {
        yes.onclick = function() {
            var modal = document.getElementById('confirmModal');
            window.location.href = modal.dataset.href;
        };
        no.onclick = function() {
            document.getElementById('confirmModal').style.display = 'none';
        };
    }
})();

// ADVANCED FILTERS
(function() {
    function getPage() {
        return document.body.dataset.page || '';
    }
    function getTable() {
        // Use IDs for tables if possible, fallback to first .main table
        const page = getPage();
        const tableIds = {
            'invoices': 'invoicesTable',
            'hours': 'hoursTable',
            'projects': 'projectsTable',
            'clients': 'clientsTable'
        };
        if (tableIds[page]) {
            return document.getElementById(tableIds[page]) || document.querySelector('.main table');
        }
        return document.querySelector('.main table');
    }
    function filterTable() {
        var search = (document.getElementById('advSearch')?.value || '').toLowerCase();
        var advCol = document.getElementById('advCol')?.value || 'all';
        var status = document.getElementById('advStatus')?.value || '';
        var dateFrom = document.getElementById('advDateFrom')?.value || '';
        var dateTo = document.getElementById('advDateTo')?.value || '';
        var page = getPage();
        var table = getTable();
        if (!table) return;
        var rows = table.querySelectorAll('tbody tr, tr');
        rows.forEach(function(row) {
            if (row.querySelectorAll('th').length) return; // skip header
            var cells = row.querySelectorAll('td');
            var show = true;

            // Search filter with column selection
            if (search) {
                if (advCol === 'all') {
                    if (row.textContent.toLowerCase().indexOf(search) === -1) show = false;
                } else {
                    var colIdx = parseInt(advCol, 10);
                    if (!cells[colIdx] || cells[colIdx].textContent.toLowerCase().indexOf(search) === -1) show = false;
                }
            }
            // Status filter
            if (status) {
                if (page === 'invoices') {
                    var statusText = cells[5] ? cells[5].textContent.toLowerCase() : '';
                    if (status === 'paid' && statusText.indexOf('betaald') === -1) show = false;
                    if (status === 'unpaid' && statusText.indexOf('open') === -1) show = false;
                }
                if (page === 'projects') {
                    var statusText = cells[2] ? cells[2].textContent.toLowerCase() : '';
                    if (status === 'ongoing' && statusText.indexOf('lopend') === -1) show = false;
                    if (status === 'finished' && statusText.indexOf('afgerond') === -1) show = false;
                }
            }
            // Date filters
            var dateCol = null;
            if (page === 'invoices') dateCol = 4;
            if (page === 'hours') dateCol = 2;
            if (page === 'projects') dateCol = null;
            if (page === 'clients') dateCol = null;
            if (dateFrom && dateCol !== null && cells[dateCol]) {
                var rowDate = cells[dateCol].textContent.trim();
                if (rowDate && rowDate < dateFrom) show = false;
            }
            if (dateTo && dateCol !== null && cells[dateCol]) {
                var rowDate = cells[dateCol].textContent.trim();
                if (rowDate && rowDate > dateTo) show = false;
            }
            row.style.display = show ? '' : 'none';
        });
    }
    // Attach listeners if elements exist
    ['advSearch', 'advCol', 'advStatus', 'advDateFrom', 'advDateTo'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', filterTable);
        if (el && (id === 'advCol' || id === 'advStatus' || id === 'advDateFrom' || id === 'advDateTo')) {
            el.addEventListener('change', filterTable);
        }
    });
    // Expose for manual trigger
    window.filterTable = filterTable;
})();

// PROJECT SELECT BY CLIENT (for invoices)
(function() {
    if (typeof allProjects === 'undefined') return;
    var clientSel = document.getElementById('clientSelect');
    var projectSel = document.getElementById('projectSelect');
    if (!clientSel || !projectSel) return;
    function updateProjectOptions() {
        var clientId = clientSel.value;
        var current = typeof selectedProjectId !== 'undefined' ? selectedProjectId : null;
        projectSel.innerHTML = '<option value="">-- Kies project --</option>';
        allProjects.forEach(function(proj) {
            if (!clientId || String(proj.client_id) === String(clientId)) {
                var opt = document.createElement('option');
                opt.value = proj.id;
                opt.textContent = proj.name;
                if (current && proj.id == current) {
                    opt.selected = true;
                }
                projectSel.appendChild(opt);
            }
        });
    }
    clientSel.addEventListener('change', function() {
        selectedProjectId = null;
        updateProjectOptions();
    });
    updateProjectOptions();
})();

// EDIT CONFIRMATION MODAL
(function() {
    document.querySelectorAll('form.card').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (form.querySelector('input[name="edit_id"]')) {
                e.preventDefault();
                var modal = document.getElementById('saveConfirmModal');
                if (!modal) return;
                modal.style.display = 'flex';
                document.getElementById('saveConfirmYes').onclick = function() {
                    modal.style.display = 'none';
                    form.submit();
                };
                document.getElementById('saveConfirmNo').onclick = function() {
                    modal.style.display = 'none';
                };
            }
        });
    });
})();

// CHARTS (Chart.js)
(function() {
    if (typeof Chart === 'undefined') return;
    // Data from PHP must be set as global variables
    if (typeof monthsLabels !== 'undefined' && typeof monthsData !== 'undefined' && document.getElementById('hoursPerMonthChart')) {
        new Chart(document.getElementById('hoursPerMonthChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: monthsLabels,
                datasets: [{
                    label: 'Uren',
                    data: monthsData,
                    backgroundColor: '#2563eb'
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { color: '#888' } },
                    x: { ticks: { color: '#888' } }
                }
            }
        });
    }
    if (typeof projectStatusLabels !== 'undefined' && typeof projectStatusData !== 'undefined' && document.getElementById('projectStatusChart')) {
        new Chart(document.getElementById('projectStatusChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: projectStatusLabels,
                datasets: [{
                    data: projectStatusData,
                    backgroundColor: ['#2563eb', '#22c55e']
                }]
            },
            options: {
                plugins: {
                    legend: {
                        labels: {
                            color: '#888',
                            font: { size: 14 },
                            boxWidth: 14,
                            boxHeight: 10,
                            padding: 8
                        }
                    }
                }
            }
        });
    }
})();

// COLUMN SORTING
(function() {
    document.querySelectorAll('.sortable').forEach(function(header) {
        header.style.cursor = 'pointer';
        header.onclick = function() {
            var table = header.closest('table');
            var col = parseInt(header.dataset.col, 10);
            var rows = Array.from(table.querySelectorAll('tr')).slice(1); // skip header
            var asc = header.dataset.asc === "1" ? false : true;
            rows.sort(function(a, b) {
                var aText = a.children[col]?.textContent.trim() || '';
                var bText = b.children[col]?.textContent.trim() || '';
                var aNum = parseFloat(aText.replace(/[^\d.-]/g, ''));
                var bNum = parseFloat(bText.replace(/[^\d.-]/g, ''));
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return asc ? aNum - bNum : bNum - aNum;
                }
                return asc ? aText.localeCompare(bText) : bText.localeCompare(aText);
            });
            rows.forEach(function(row) { table.appendChild(row); });
            header.dataset.asc = asc ? "1" : "0";
        };
    });
})();

// NIEUWST/OUDST TOGGLE
(function() {
    document.querySelectorAll('.sort-order-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            var th = toggle.closest('th');
            var table = th.closest('table');
            var rows = Array.from(table.querySelectorAll('tr')).slice(1); // skip header
            // Try to sort by date column if available, else by ID (first col)
            var dateCol = null;
            var page = document.body.dataset.page || '';
            if (page === 'invoices') dateCol = 4;
            if (page === 'hours') dateCol = 2;
            if (page === 'projects') dateCol = null;
            if (page === 'clients') dateCol = null;
            if (dateCol !== null && rows[0] && rows[0].children[dateCol]) {
                rows.sort(function(a, b) {
                    var aDate = a.children[dateCol].textContent.trim();
                    var bDate = b.children[dateCol].textContent.trim();
                    if (toggle.checked) {
                        return bDate.localeCompare(aDate);
                    } else {
                        return aDate.localeCompare(bDate);
                    }
                });
            } else {
                var idCol = 0;
                rows.sort(function(a, b) {
                    var aId = parseInt(a.children[idCol].textContent.replace(/\D/g, ''), 10);
                    var bId = parseInt(b.children[idCol].textContent.replace(/\D/g, ''), 10);
                    if (toggle.checked) {
                        return bId - aId;
                    } else {
                        return aId - bId;
                    }
                });
            }
            rows.forEach(function(row) { table.appendChild(row); });
        });
    });
})();

// HOURS FORM: SHOW BOTH PROJECT AND CLIENT SELECTS (no toggle needed)
// If you still have the old linkType toggle, remove its JS!

console.log('clientId:', clientId, 'allProjects:', allProjects);