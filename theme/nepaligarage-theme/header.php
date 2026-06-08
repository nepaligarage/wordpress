<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- ── Navigation ──────────────────────────────────────────────────────────── -->
<header class="ng-header" id="ng-header">
    <div class="ng-header__inner ng-container">

        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ng-logo" aria-label="NepaliGarage Home">
            <span class="ng-logo__mark">NG</span>
            <span class="ng-logo__text">Nepali<strong>Garage</strong></span>
        </a>

        <nav class="ng-nav" aria-label="Primary navigation">
            <ul class="ng-nav__list" id="ng-nav-list">
                <li><a href="<?php echo esc_url( home_url( '/compare/' ) ); ?>" class="ng-nav__link">Compare Cars</a></li>
                <li><a href="<?php echo esc_url( home_url( '/new-cars/' ) ); ?>" class="ng-nav__link">New Cars</a></li>
                <li><a href="<?php echo esc_url( home_url( '/electric-vehicles/' ) ); ?>" class="ng-nav__link ng-nav__link--ev">⚡ Electric</a></li>
                <li><a href="<?php echo esc_url( home_url( '/nepal-car-price-estimator/' ) ); ?>" class="ng-nav__link">Price Estimator</a></li>
                <li><a href="<?php echo esc_url( home_url( '/garages/' ) ); ?>" class="ng-nav__link">Garages</a></li>
                <li><a href="<?php echo esc_url( home_url( '/news/' ) ); ?>" class="ng-nav__link">News</a></li>
            </ul>
        </nav>

        <div class="ng-header__actions">
            <!-- Shown when logged out -->
            <button class="ng-btn ng-btn--primary ng-auth-trigger" id="ng-login-btn" data-tab="signin">
                Log In
            </button>
            <!-- Shown when logged in (hidden by default, revealed by auth.js) -->
            <div class="ng-user-menu" id="ng-user-menu" hidden>
                <button class="ng-user-menu__trigger" id="ng-user-trigger" aria-expanded="false" aria-haspopup="true">
                    <span class="ng-avatar" id="ng-avatar-initials">?</span>
                    <span class="ng-user-menu__name" id="ng-user-name">My Account</span>
                    <svg class="ng-user-menu__caret" width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </button>
                <ul class="ng-user-menu__dropdown" id="ng-user-dropdown" hidden>
                    <li><a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>">My Garage</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/dashboard/#profile' ) ); ?>">Profile</a></li>
                    <li><hr class="ng-divider"></li>
                    <li><button id="ng-logout-btn" class="ng-user-menu__logout">Log Out</button></li>
                </ul>
            </div>
            <button class="ng-hamburger" id="ng-hamburger" aria-label="Toggle menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>

    </div>
</header>

<!-- ── Login / Register Modal ─────────────────────────────────────────────── -->
<div class="ng-modal-overlay" id="ng-auth-overlay" aria-hidden="true">
    <div class="ng-modal" role="dialog" aria-modal="true" aria-labelledby="ng-modal-title">
        <button class="ng-modal__close" id="ng-modal-close" aria-label="Close">&times;</button>

        <div class="ng-modal__tabs">
            <button class="ng-modal__tab ng-modal__tab--active" data-tab="signin">Sign In</button>
            <button class="ng-modal__tab" data-tab="register">Create Account</button>
        </div>

        <!-- Sign In -->
        <form class="ng-auth-form" id="ng-signin-form" data-panel="signin">
            <h2 class="ng-modal__title" id="ng-modal-title">Welcome back</h2>
            <div class="ng-form-group">
                <label for="ng-signin-email">Email</label>
                <input type="email" id="ng-signin-email" name="email" autocomplete="email" required placeholder="you@example.com">
            </div>
            <div class="ng-form-group">
                <label for="ng-signin-password">Password</label>
                <input type="password" id="ng-signin-password" name="password" autocomplete="current-password" required placeholder="••••••••">
            </div>
            <div class="ng-auth-error" id="ng-signin-error" hidden></div>
            <button type="submit" class="ng-btn ng-btn--primary ng-btn--full">Sign In</button>
            <p class="ng-auth-switch">Don't have an account? <button type="button" class="ng-link" data-tab="register">Create one free</button></p>
        </form>

        <!-- Register -->
        <form class="ng-auth-form" id="ng-register-form" data-panel="register" hidden>
            <h2 class="ng-modal__title">Create your account</h2>
            <div class="ng-form-group">
                <label for="ng-reg-name">Full Name</label>
                <input type="text" id="ng-reg-name" name="name" autocomplete="name" required placeholder="Aarav Sharma">
            </div>
            <div class="ng-form-group">
                <label for="ng-reg-email">Email</label>
                <input type="email" id="ng-reg-email" name="email" autocomplete="email" required placeholder="you@example.com">
            </div>
            <div class="ng-form-group">
                <label for="ng-reg-password">Password</label>
                <input type="password" id="ng-reg-password" name="password" autocomplete="new-password" required placeholder="At least 8 characters" minlength="8">
            </div>
            <div class="ng-auth-error" id="ng-reg-error" hidden></div>
            <button type="submit" class="ng-btn ng-btn--primary ng-btn--full">Create Account — Free</button>
            <p class="ng-auth-switch">Already have an account? <button type="button" class="ng-link" data-tab="signin">Sign in</button></p>
        </form>

    </div>
</div>

<div id="page">
