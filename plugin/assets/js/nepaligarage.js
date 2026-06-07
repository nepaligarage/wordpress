/**
 * NepaliGarage Core — Frontend JavaScript
 * Version: 0.1.0
 *
 * Features:
 *  - Tab switching for comparison categories
 *  - Sticky comparison header on scroll
 *  - Price estimator AJAX submission
 *
 * No jQuery dependency — vanilla JS only.
 */

( function () {
    'use strict';

    // -----------------------------------------------------------------------
    // Utility
    // -----------------------------------------------------------------------

    /**
     * Add a listener that fires once the DOM is ready.
     * @param {function} fn
     */
    function onReady( fn ) {
        if ( document.readyState !== 'loading' ) {
            fn();
        } else {
            document.addEventListener( 'DOMContentLoaded', fn );
        }
    }

    /**
     * Format a number as a Nepal Rupee string.
     * @param {number} amount
     * @returns {string}
     */
    function formatNPR( amount ) {
        return 'NPR ' + amount.toLocaleString( 'en-IN', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        } );
    }

    // -----------------------------------------------------------------------
    // Tab switching
    // -----------------------------------------------------------------------

    function initTabs( wrap ) {
        const buttons = wrap.querySelectorAll( '.ng-tab-btn' );
        const panels  = wrap.querySelectorAll( '.ng-tab-panel' );

        if ( ! buttons.length ) return;

        buttons.forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                const target = btn.dataset.tab;

                // Update buttons.
                buttons.forEach( function ( b ) {
                    b.classList.toggle( 'ng-tab-btn--active', b.dataset.tab === target );
                } );

                // Update panels.
                panels.forEach( function ( panel ) {
                    const isActive = panel.dataset.tabPanel === target;
                    panel.classList.toggle( 'ng-tab-panel--active', isActive );
                } );
            } );
        } );
    }

    // -----------------------------------------------------------------------
    // Sticky header sentinel
    // -----------------------------------------------------------------------

    function initStickyHeader( wrap ) {
        const header = wrap.querySelector( '.ng-comparison-header' );
        if ( ! header ) return;

        // Only apply sticky behaviour on wider screens.
        if ( window.innerWidth <= 767 ) return;

        const sentinel = document.createElement( 'div' );
        sentinel.style.cssText = 'position:absolute;top:0;height:1px;width:1px;pointer-events:none;';
        wrap.insertBefore( sentinel, wrap.firstChild );

        const observer = new IntersectionObserver(
            function ( entries ) {
                entries.forEach( function ( entry ) {
                    header.classList.toggle( 'ng-comparison-header--floating', ! entry.isIntersecting );
                } );
            },
            { threshold: 0 }
        );

        observer.observe( sentinel );
    }

    // -----------------------------------------------------------------------
    // Price estimator AJAX
    // -----------------------------------------------------------------------

    function buildBreakdownHTML( data ) {
        if ( ! data || ! data.breakdown ) return '';

        const rows = data.breakdown.map( function ( item ) {
            return '<div class="ng-result-row">' +
                '<span class="ng-result-row__label">' + escapeHTML( item.label ) + '</span>' +
                '<span class="ng-result-row__value">' + escapeHTML( item.value ) + '</span>' +
                '</div>';
        } ).join( '' );

        return '<div class="ng-result-header">Price Breakdown</div>' +
            rows +
            '<div class="ng-result-total">' +
            '<span class="ng-result-total__label">Estimated On-Road Total</span>' +
            '<span class="ng-result-total__value">' + escapeHTML( data.total ) + '</span>' +
            '</div>';
    }

    /**
     * Minimal HTML escaping — we only ever put server-trusted strings here,
     * but belt-and-suspenders is always good.
     * @param {string} str
     * @returns {string}
     */
    function escapeHTML( str ) {
        return String( str )
            .replace( /&/g, '&amp;' )
            .replace( /</g, '&lt;' )
            .replace( />/g, '&gt;' )
            .replace( /"/g, '&quot;' );
    }

    function initPriceEstimator( wrap ) {
        const form   = wrap.querySelector( '#ng-estimator-form' );
        const result = wrap.querySelector( '#ng-estimator-result' );
        const btn    = form ? form.querySelector( '[type="submit"]' ) : null;

        if ( ! form || ! result ) return;

        form.addEventListener( 'submit', function ( e ) {
            e.preventDefault();

            const ngData = window.ngData || {};
            if ( ! ngData.ajaxUrl || ! ngData.nonce ) {
                console.warn( '[NepaliGarage] ngData not available.' );
                return;
            }

            // Disable button while fetching.
            if ( btn ) {
                btn.disabled = true;
                btn.textContent = 'Calculating…';
            }

            result.hidden = true;

            const formData = new FormData( form );
            formData.append( 'action', 'ng_calculate_price' );
            formData.append( 'nonce',  ngData.nonce );

            fetch( ngData.ajaxUrl, {
                method: 'POST',
                body:   formData,
                credentials: 'same-origin',
            } )
            .then( function ( response ) {
                if ( ! response.ok ) {
                    throw new Error( 'Network error: ' + response.status );
                }
                return response.json();
            } )
            .then( function ( json ) {
                if ( json.success && json.data ) {
                    result.innerHTML = buildBreakdownHTML( json.data );
                    result.hidden    = false;
                } else {
                    const msg = ( json.data && json.data.message ) ? json.data.message : 'An error occurred.';
                    result.innerHTML = '<p class="ng-error">' + escapeHTML( msg ) + '</p>';
                    result.hidden    = false;
                }
            } )
            .catch( function ( err ) {
                result.innerHTML = '<p class="ng-error">Could not calculate price. Please try again.</p>';
                result.hidden    = false;
                console.error( '[NepaliGarage] Price estimator error:', err );
            } )
            .finally( function () {
                if ( btn ) {
                    btn.disabled    = false;
                    btn.textContent = 'Calculate Nepal Price';
                }
            } );
        } );
    }

    // -----------------------------------------------------------------------
    // Bootstrap
    // -----------------------------------------------------------------------

    onReady( function () {

        // Init all comparison wraps on the page.
        document.querySelectorAll( '[data-ng-comparison]' ).forEach( function ( wrap ) {
            initTabs( wrap );
            initStickyHeader( wrap );
        } );

        // Init price estimator(s).
        document.querySelectorAll( '.ng-price-estimator' ).forEach( function ( wrap ) {
            initPriceEstimator( wrap );
        } );

    } );

} )();
