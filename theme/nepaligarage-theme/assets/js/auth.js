/* NepaliGarage — Supabase Auth */
(function () {
    'use strict';

    if (!window.ngConfig || !window.ngConfig.supabaseUrl || !window.ngConfig.supabaseKey) {
        return;
    }

    const { createClient } = window.supabase;
    window.ngSupabase = createClient(ngConfig.supabaseUrl, ngConfig.supabaseKey);

    const sb = window.ngSupabase;

    // ── DOM refs ──────────────────────────────────────────────────────────────

    const overlay      = document.getElementById('ng-auth-overlay');
    const loginBtn     = document.getElementById('ng-login-btn');
    const userMenu     = document.getElementById('ng-user-menu');
    const userNameEl   = document.getElementById('ng-user-name');
    const avatarEl     = document.getElementById('ng-avatar-initials');
    const logoutBtn    = document.getElementById('ng-logout-btn');
    const modalClose   = document.getElementById('ng-modal-close');

    const signinForm   = document.getElementById('ng-signin-form');
    const registerForm = document.getElementById('ng-register-form');
    const signinErr    = document.getElementById('ng-signin-error');
    const regErr       = document.getElementById('ng-reg-error');

    const modalTabs    = document.querySelectorAll('.ng-modal__tab');

    // ── Helpers ───────────────────────────────────────────────────────────────

    function openModal(tab) {
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (tab) switchTab(tab);
    }

    function closeModal() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        clearErrors();
    }

    function switchTab(tab) {
        modalTabs.forEach(function (t) {
            const isActive = t.dataset.tab === tab;
            t.classList.toggle('ng-modal__tab--active', isActive);
        });
        if (signinForm)   signinForm.hidden   = tab !== 'signin';
        if (registerForm) registerForm.hidden = tab !== 'register';
        clearErrors();
    }

    function clearErrors() {
        if (signinErr) { signinErr.hidden = true; signinErr.textContent = ''; }
        if (regErr)    { regErr.hidden = true; regErr.textContent = ''; }
    }

    function showError(el, msg) {
        if (!el) return;
        el.textContent = msg;
        el.hidden = false;
    }

    function setAuthState(user) {
        if (user) {
            if (loginBtn) loginBtn.hidden = true;
            if (userMenu) userMenu.hidden = false;

            const name = user.user_metadata?.display_name || user.email.split('@')[0];
            if (userNameEl) userNameEl.textContent = name;
            if (avatarEl)   avatarEl.textContent   = name.charAt(0).toUpperCase();

            // Store session for dashboard.js
            window.ngUser = user;
        } else {
            if (loginBtn) loginBtn.hidden = false;
            if (userMenu) userMenu.hidden = true;
            window.ngUser = null;
        }
    }

    // ── Auth state listener ───────────────────────────────────────────────────

    sb.auth.onAuthStateChange(function (event, session) {
        setAuthState(session ? session.user : null);

        // Fire custom event so dashboard.js can react
        document.dispatchEvent(new CustomEvent('ngAuthState', {
            detail: { event, session, user: session ? session.user : null }
        }));
    });

    // Init: check current session on page load
    sb.auth.getSession().then(function ({ data: { session } }) {
        setAuthState(session ? session.user : null);
    });

    // ── Open modal triggers ───────────────────────────────────────────────────

    document.querySelectorAll('.ng-auth-trigger').forEach(function (el) {
        el.addEventListener('click', function () {
            openModal(el.dataset.tab || 'signin');
        });
    });

    // Hash-based open (e.g. redirect from /dashboard when logged out)
    if (window.location.hash === '#login') {
        openModal('signin');
        history.replaceState(null, '', window.location.pathname);
    }

    // ── Close modal ───────────────────────────────────────────────────────────

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });

    // ── Tab switching ─────────────────────────────────────────────────────────

    modalTabs.forEach(function (tab) {
        tab.addEventListener('click', function () { switchTab(tab.dataset.tab); });
    });

    // ── Sign in ───────────────────────────────────────────────────────────────

    if (signinForm) {
        signinForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submit = signinForm.querySelector('[type=submit]');
            submit.disabled = true;
            submit.textContent = 'Signing in…';
            clearErrors();

            const email    = signinForm.querySelector('#ng-signin-email').value.trim();
            const password = signinForm.querySelector('#ng-signin-password').value;

            const { error } = await sb.auth.signInWithPassword({ email, password });

            if (error) {
                showError(signinErr, error.message);
                submit.disabled = false;
                submit.textContent = 'Sign In';
            } else {
                closeModal();
                submit.disabled = false;
                submit.textContent = 'Sign In';
            }
        });
    }

    // ── Register ──────────────────────────────────────────────────────────────

    if (registerForm) {
        registerForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submit = registerForm.querySelector('[type=submit]');
            submit.disabled = true;
            submit.textContent = 'Creating account…';
            clearErrors();

            const name     = registerForm.querySelector('#ng-reg-name').value.trim();
            const email    = registerForm.querySelector('#ng-reg-email').value.trim();
            const password = registerForm.querySelector('#ng-reg-password').value;

            const { data, error } = await sb.auth.signUp({
                email,
                password,
                options: { data: { display_name: name } },
            });

            if (error) {
                showError(regErr, error.message);
                submit.disabled = false;
                submit.textContent = 'Create Account — Free';
                return;
            }

            // Create profile row
            if (data.user) {
                await sb.from('user_profiles').upsert({
                    user_id:      data.user.id,
                    display_name: name,
                }, { onConflict: 'user_id' });
            }

            closeModal();
            submit.disabled = false;
            submit.textContent = 'Create Account — Free';
        });
    }

    // ── Log out ───────────────────────────────────────────────────────────────

    if (logoutBtn) {
        logoutBtn.addEventListener('click', async function () {
            await sb.auth.signOut();
            if (window.location.pathname.includes('dashboard')) {
                window.location.href = ngConfig.siteUrl + '#login';
            }
        });
    }

})();
