/**
 * Market Management System v2 — main.js
 */

document.addEventListener('DOMContentLoaded', function () {
    initSidebar();
    initAlerts();
    initDeleteModal();
    initFormValidation();
    initLiveSearch();
    initPhoneFormat();
    initActiveNav();
    initRentHighlight();
    initToastContainer();
    initDateDefaults();
});

/* ── 1. SIDEBAR ──────────────────────────────────────────── */
function initSidebar() {
    window.openSidebar = function () {
        document.getElementById('sidebar')?.classList.add('open');
        document.getElementById('sidebar-overlay')?.classList.add('active');
        document.body.style.overflow = 'hidden';
    };
    window.closeSidebar = function () {
        document.getElementById('sidebar')?.classList.remove('open');
        document.getElementById('sidebar-overlay')?.classList.remove('active');
        document.body.style.overflow = '';
    };
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
}

/* ── 2. AUTO-DISMISS ALERTS ─────────────────────────────── */
function initAlerts() {
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-6px)';
            setTimeout(() => el.remove(), 500);
        }, 4500);
    });
}

/* ── 3. DELETE CONFIRMATION MODAL ───────────────────────── */
function initDeleteModal() {
    // Inject modal HTML once
    const m = document.createElement('div');
    m.id = 'del-modal';
    m.innerHTML = `
      <div class="modal-overlay" id="modal-overlay">
        <div class="modal-box">
          <div class="modal-icon">🗑️</div>
          <div class="modal-title">Delete Confirmation</div>
          <p class="modal-msg" id="modal-msg">Are you sure? This action cannot be undone.</p>
          <div class="modal-actions">
            <button class="btn btn-secondary" id="modal-cancel">Cancel</button>
            <button class="btn btn-danger" id="modal-confirm">Yes, Delete</button>
          </div>
        </div>
      </div>`;
    document.body.appendChild(m);

    let pendingHref = null;

    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[data-confirm]');
        if (!link) return;
        e.preventDefault();
        pendingHref = link.href;
        document.getElementById('modal-msg').textContent = link.dataset.confirm || 'Delete this item?';
        document.getElementById('modal-overlay').classList.add('active');
    });

    document.getElementById('modal-cancel').addEventListener('click', () => {
        document.getElementById('modal-overlay').classList.remove('active');
        pendingHref = null;
    });
    document.getElementById('modal-confirm').addEventListener('click', () => {
        if (pendingHref) window.location.href = pendingHref;
    });
    document.getElementById('modal-overlay').addEventListener('click', function (e) {
        if (e.target === this) { this.classList.remove('active'); pendingHref = null; }
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.getElementById('modal-overlay')?.classList.remove('active');
            pendingHref = null;
        }
    });
}

/* ── 4. FORM VALIDATION ─────────────────────────────────── */
function initFormValidation() {
    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            // Clear previous errors
            form.querySelectorAll('.field-error').forEach(el => el.remove());
            form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));

            let valid = true;

            form.querySelectorAll('[required]').forEach(function (field) {
                const val = field.value.trim();
                if (val === '') {
                    markError(field, 'This field is required.');
                    valid = false;
                    return;
                }
                if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                    markError(field, 'Enter a valid email address.');
                    valid = false;
                }
                if (field.type === 'password' && val.length < 6) {
                    markError(field, 'Password must be at least 6 characters.');
                    valid = false;
                }
                if (field.name === 'phone' && !/^[0-9+\-\s()]{7,20}$/.test(val)) {
                    markError(field, 'Enter a valid phone number (7–20 digits).');
                    valid = false;
                }
                if (field.type === 'number') {
                    const min = parseFloat(field.min ?? 0);
                    if (isNaN(parseFloat(val)) || parseFloat(val) < min) {
                        markError(field, 'Enter a valid positive number.');
                        valid = false;
                    }
                }
            });

            if (!valid) {
                e.preventDefault();
                const first = form.querySelector('.input-error');
                if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                const btn = form.querySelector('[type="submit"]');
                if (btn) { btn.disabled = true; btn.textContent = 'Please wait…'; }
            }
        });
    });
}

function markError(field, msg) {
    field.classList.add('input-error');
    const span = document.createElement('span');
    span.className = 'field-error';
    span.textContent = msg;
    field.insertAdjacentElement('afterend', span);
}

/* ── 5. LIVE TABLE SEARCH ───────────────────────────────── */
function initLiveSearch() {
    const inp = document.getElementById('live-search');
    if (!inp) return;
    const tableId = inp.dataset.table || 'data-table';
    const table = document.getElementById(tableId);
    if (!table) return;

    inp.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        let count = 0;
        table.querySelectorAll('tbody tr').forEach(function (row) {
            const match = row.textContent.toLowerCase().includes(q);
            row.style.display = match ? '' : 'none';
            if (match) count++;
        });
        // No-results row
        let noRow = table.querySelector('.no-results');
        if (count === 0) {
            if (!noRow) {
                const cols = table.querySelector('thead tr')?.children.length || 5;
                noRow = document.createElement('tr');
                noRow.className = 'no-results';
                noRow.innerHTML = `<td colspan="${cols}" style="text-align:center;padding:28px;color:rgba(255,255,255,0.35);">No results found for "<em>${escapeHtml(q)}</em>"</td>`;
                table.querySelector('tbody')?.appendChild(noRow);
            }
            noRow.style.display = '';
        } else if (noRow) {
            noRow.style.display = 'none';
        }
    });
}

function escapeHtml(s) {
    return s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

/* ── 6. PHONE FORMAT ────────────────────────────────────── */
function initPhoneFormat() {
    document.querySelectorAll('input[name="phone"]').forEach(function (inp) {
        inp.addEventListener('input', function () {
            this.value = this.value.replace(/[^\d+\-\s()]/g, '');
        });
    });
}

/* ── 7. ACTIVE NAV ──────────────────────────────────────── */
function initActiveNav() {
    const path = window.location.href;
    document.querySelectorAll('.nav-links a, #sidebar a').forEach(function (a) {
        try {
            if (a.href && path.includes(a.getAttribute('href').split('?')[0].replace(/^\//, ''))) {
                a.classList.add('nav-active');
            }
        } catch(e) {}
    });
}

/* ── 8. RENT ROW HIGHLIGHT ──────────────────────────────── */
function initRentHighlight() {
    document.querySelectorAll('td.due-cell').forEach(function (cell) {
        const val = parseFloat(cell.textContent.replace(/[^0-9.\-]/g, ''));
        const row = cell.closest('tr');
        if (!row) return;
        if (val > 0) row.classList.add('row-overdue');
        else row.classList.add('row-paid');
    });
}

/* ── 9. TOAST SYSTEM ────────────────────────────────────── */
function initToastContainer() {
    if (!document.getElementById('toast-container')) {
        const tc = document.createElement('div');
        tc.id = 'toast-container';
        tc.className = 'toast-container';
        document.body.appendChild(tc);
    }
}
window.showToast = function (msg, type = 'success') {
    const tc = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.innerHTML = `<span>${type === 'success' ? '✓' : '✕'}</span> ${escapeHtml(msg)}`;
    tc.appendChild(t);
    requestAnimationFrame(() => { requestAnimationFrame(() => t.classList.add('show')); });
    setTimeout(() => {
        t.classList.remove('show');
        setTimeout(() => t.remove(), 350);
    }, 3500);
};

/* ── 10. DATE DEFAULTS ──────────────────────────────────── */
function initDateDefaults() {
    document.querySelectorAll('input[type="date"]:not([value])').forEach(function (inp) {
        if (!inp.value) inp.value = new Date().toISOString().split('T')[0];
    });
}
