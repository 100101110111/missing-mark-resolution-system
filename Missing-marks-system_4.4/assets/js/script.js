/* ============================================================
   Missing Marks Resolution System — client-side interactivity
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    /* ---- Auto-dismiss alerts after 6s ---- */
    document.querySelectorAll('.alert').forEach(function (alertEl) {
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
            if (bsAlert) bsAlert.close();
        }, 6000);
    });

    /* ---- Sidebar toggle (mobile, admin layout) ---- */
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebar = document.querySelector('.kt-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
        document.addEventListener('click', function (e) {
            if (window.innerWidth < 992 && sidebar.classList.contains('show') &&
                !sidebar.contains(e.target) && e.target !== sidebarToggle && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });
    }

    /* ---- Password show/hide toggles ---- */
    document.querySelectorAll('.kt-toggle-pass').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.querySelector(btn.dataset.target);
            if (!target) return;
            var icon = btn.querySelector('i');
            if (target.type === 'password') {
                target.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                target.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    /* ---- Live password match check on register page ---- */
    var pass = document.getElementById('password');
    var confirm = document.getElementById('confirm_password');
    var matchHint = document.getElementById('passMatchHint');
    if (pass && confirm && matchHint) {
        var check = function () {
            if (confirm.value.length === 0) {
                matchHint.textContent = '';
                return;
            }
            if (pass.value === confirm.value) {
                matchHint.textContent = 'Passwords match ✓';
                matchHint.className = 'form-text text-success';
            } else {
                matchHint.textContent = 'Passwords do not match';
                matchHint.className = 'form-text text-danger';
            }
        };
        pass.addEventListener('input', check);
        confirm.addEventListener('input', check);
    }

    /* ---- Password strength meter ---- */
    var pwField = document.getElementById('password');
    var meter = document.getElementById('pwStrengthBar');
    if (pwField && meter) {
        pwField.addEventListener('input', function () {
            var val = pwField.value;
            var score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 10) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            var pct = Math.min(100, (score / 5) * 100);
            meter.style.width = pct + '%';
            var colors = ['#d92b3a', '#d92b3a', '#f2a70b', '#f2a70b', '#0b6e4f', '#0b6e4f'];
            meter.style.background = colors[score];
        });
    }

    /* ---- Bootstrap form validation ---- */
    document.querySelectorAll('.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    /* ---- Animated stat counters ---- */
    document.querySelectorAll('.kt-count').forEach(function (el) {
        var target = parseInt(el.dataset.count || el.textContent, 10) || 0;
        var duration = 900;
        var start = null;
        var startVal = 0;
        function step(ts) {
            if (!start) start = ts;
            var progress = Math.min((ts - start) / duration, 1);
            el.textContent = Math.floor(startVal + (target - startVal) * progress);
            if (progress < 1) window.requestAnimationFrame(step);
            else el.textContent = target;
        }
        window.requestAnimationFrame(step);
    });

    /* ---- Populate "View report" modal from data attributes (student & admin) ---- */
    var viewModal = document.getElementById('viewReportModal');
    if (viewModal) {
        viewModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;
            var fields = ['ref', 'student', 'admission', 'trainer', 'unit', 'unitcode',
                          'semester', 'year', 'examtype', 'catmarks', 'catgrade', 'examgrade', 'status', 'description', 'remarks', 'created'];
            fields.forEach(function (f) {
                var el = viewModal.querySelector('[data-field="' + f + '"]');
                if (el) el.textContent = btn.dataset[f] || '—';
            });
            var statusBadge = viewModal.querySelector('[data-field="statusbadge"]');
            if (statusBadge) {
                statusBadge.className = 'badge-status-' + (btn.dataset.statusclass || 'pending');
                statusBadge.textContent = btn.dataset.status || 'Pending';
            }
            var idField = viewModal.querySelector('#modal_report_id');
            if (idField) idField.value = btn.dataset.id || '';

            var statusSelect = viewModal.querySelector('#status_select');
            if (statusSelect) statusSelect.value = btn.dataset.status || 'Pending';

            var remarksInput = viewModal.querySelector('#admin_remarks_input');
            if (remarksInput) remarksInput.value = (btn.dataset.remarks && btn.dataset.remarks !== '—') ? btn.dataset.remarks : '';
        });
    }

    /* ---- Simple client-side table search (student + admin report tables) ---- */
    var searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            var q = searchInput.value.toLowerCase();
            document.querySelectorAll('#reportsTable tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    /* ---- Trainer "add new" toggle on the report form ---- */
    var trainerSelect = document.getElementById('trainer_select');
    var trainerCustom = document.getElementById('trainer_custom_wrap');
    if (trainerSelect && trainerCustom) {
        trainerSelect.addEventListener('change', function () {
            if (trainerSelect.value === '__other__') {
                trainerCustom.classList.remove('d-none');
                trainerCustom.querySelector('input').setAttribute('required', 'required');
            } else {
                trainerCustom.classList.add('d-none');
                trainerCustom.querySelector('input').removeAttribute('required');
            }
        });
    }
});
