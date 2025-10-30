/**
 * Clarity Consent Layer - Automatic consent injection
 * Version: 2.0.1
 * 
 * This script waits for Microsoft Clarity to load and then automatically
 * applies the configured consent settings without interfering with the
 * original Clarity implementation.
 */
(function () {
    'use strict';

    // Configuration check
    if (typeof clarityConsent === 'undefined') {
        console.warn('Clarity Consent Auto: Configuration not found - plugin may not be properly configured');
        return;
    }

    // Debug logging (can be disabled in production)
    const DEBUG = false;

    function debugLog(message, data) {
        if (DEBUG) {
            console.log('[Clarity Consent Auto]', message, data || '');
        }
    }

    /**
     * Wait for Clarity to be available and apply consent
     */
    function waitForClarityAndApplyConsent() {
        // Check if Clarity is already available
        if (window.clarity && typeof window.clarity === 'function') {
            applyConsent();
            return;
        }

        // If not available, wait and retry
        let attempts = 0;
        const maxAttempts = 100; // Max 10 seconds (100 * 100ms)

        const checkInterval = setInterval(function () {
            attempts++;

            if (window.clarity && typeof window.clarity === 'function') {
                clearInterval(checkInterval);
                applyConsent();
            } else if (attempts >= maxAttempts) {
                clearInterval(checkInterval);
                console.warn('Clarity Consent Auto: Microsoft Clarity not found after 10 seconds - consent not applied');
            }
        }, 100); // Check every 100ms
    }

    /**
     * Apply consent settings to Clarity
     */
    function applyConsent() {
        try {
            // Apply consent using Clarity's consentv2 API
            window.clarity('consentv2', {
                ad_Storage: clarityConsent.adStorage,
                analytics_Storage: clarityConsent.analyticsStorage
            });

            debugLog('Consent successfully applied', {
                ad_Storage: clarityConsent.adStorage,
                analytics_Storage: clarityConsent.analyticsStorage
            });

            // Optional: Fire custom event for other scripts
            if (document.createEvent) {
                const event = document.createEvent('CustomEvent');
                event.initCustomEvent('clarityConsentApplied', true, true, {
                    adStorage: clarityConsent.adStorage,
                    analyticsStorage: clarityConsent.analyticsStorage
                });
                document.dispatchEvent(event);
            }

        } catch (error) {
            console.error('Clarity Consent Auto: Error applying consent', error);
        }
    }

    /**
     * Initialize the consent layer
     */
    function init() {
        debugLog('Initializing Clarity Consent Layer');

        // Check if we're in the admin area (skip consent)
        if (document.body && document.body.classList.contains('wp-admin')) {
            debugLog('Admin area detected - skipping consent application');
            return;
        }

        // Start waiting for Clarity
        waitForClarityAndApplyConsent();
    }

    // Wait for DOM to be ready before initializing
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM is already ready
        init();
    }

    // Fallback: also listen for window load event
    if (document.readyState !== 'complete') {
        window.addEventListener('load', function () {
            // Double-check if consent was already applied
            if (!window.clarityConsentApplied) {
                debugLog('Window load fallback triggered');
                waitForClarityAndApplyConsent();
            }
        });
    }

})();
