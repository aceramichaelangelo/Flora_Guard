        </main>
    </div>
</div>
<?php echo $extraScripts ?? ''; ?>
<?php require __DIR__ . '/partials/image_preview_modal.php'; ?>
<?php $jsV = (string)@filemtime(__DIR__ . '/../assets/js/app.js'); ?>
<script src="assets/js/app.js?v=<?= h($jsV) ?>"></script>

<!-- Logout confirmation modal (inserted before closing body so JS can control it) -->
<div id="logoutModal" class="modal-overlay" aria-hidden="true" onclick="if(event.target===this) closeLogoutModal()">
    <div class="modal modal-modern" role="dialog" aria-modal="true" aria-labelledby="logoutTitle">
        <div class="modal-hero">
            <div class="modal-icon-wrap">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </div>
            <div class="modal-hero-copy">
                <span class="modal-kicker">Session control</span>
                <h3 id="logoutTitle">Confirm Logout</h3>
                <p class="modal-desc">You will end your current session and return to the sign-in screen.</p>
            </div>
        </div>
        <div class="modal-summary">
            <div class="modal-summary-item">
                <strong>Secure sign-out</strong>
                <span>Protects the dashboard session on shared devices.</span>
            </div>
            <div class="modal-summary-item">
                <strong>Quick return</strong>
                <span>You can log back in anytime with your credentials.</span>
            </div>
        </div>
        <div class="modal-error alert alert-error" id="logoutError" role="alert" aria-live="polite" style="display:none"></div>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline" id="logoutCancel" onclick="closeLogoutModal()">Stay Logged In</button>
            <button type="button" class="btn btn-danger" id="logoutConfirm" onclick="handleLogout(this)">Logout Now</button>
        </div>
    </div>
</div>

<script>
function openLogoutModal(e) {
    if (e) e.preventDefault();
    var modal = document.getElementById('logoutModal');
    var confirm = document.getElementById('logoutConfirm');
    var errBox = document.getElementById('logoutError');
    if (!modal || !confirm) return;

    if (errBox) {
        errBox.style.display = 'none';
        errBox.textContent = '';
    }
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    confirm.disabled = false;
    confirm.textContent = 'Logout';
}

function closeLogoutModal() {
    var modal = document.getElementById('logoutModal');
    if (modal) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }
}

function handleLogout(btn) {
    btn.disabled = true;
    btn.textContent = 'Logging out...';
    var errBox = document.getElementById('logoutError');
    if (errBox) errBox.style.display = 'none';

    fetch('logout.php', { method: 'GET', credentials: 'same-origin' })
        .then(function (resp) {
            if (!resp.ok) {
                throw new Error('Server error: ' + resp.status);
            }
            window.location.href = 'login.php';
        })
        .catch(function (err) {
            if (errBox) {
                errBox.textContent = 'Could not logout — ' + (err && err.message ? err.message : 'network error');
                errBox.style.display = 'block';
            }
            btn.disabled = false;
            btn.textContent = 'Logout';
        });
}
</script>
</body>
</html>
