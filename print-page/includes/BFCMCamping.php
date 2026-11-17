<?php

namespace PPB\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( __NAMESPACE__ . '\PPB_PromoBanner' ) ) {

    class PPB_PromoBanner {
        const environment     = 'production'; // 'development' or 'production'
        const ENVIRONMENT     = self::environment;
        const DISMISSED_KEY   = 'ppb_promo_banner_dismissed_permanent';
        const EXPIRATION_DATE = '2026-12-05 23:59:59';

        /** Target landing URL */
        const COUPON_URL      = 'https://bplugins.com/products/print-page/pricing/?utm_source=admin&utm_medium=dashboard&utm_campaign=bfcm2026';

        private static $instance = null;
        private static $styles_rendered = false;
        private static $scripts_rendered = false;
        private static $banner_rendered = false;

        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct() {
            if ( $this->is_development() ) {
                add_action( 'init', function() {
                    if ( get_current_user_id() && isset( $_GET['reset_promo'] ) ) {
                        delete_user_meta( get_current_user_id(), self::DISMISSED_KEY );
                    }
                } );
            }
            add_action( 'admin_notices',                     [ $this, 'render_admin_notices' ] );
            add_action( 'admin_head',                        [ $this, 'enqueue_styles' ] );
            add_action( 'admin_footer',                      [ $this, 'enqueue_scripts' ] );
            add_action( 'wp_ajax_ppb_dismiss_promo_banner',  [ $this, 'ajax_dismiss' ] );
        }

        /**
         * Static helper to render directly on dashboard page
         */
        public static function render_banner(): void {
            self::instance()->render();
        }

        /**
         * Render banner via admin_notices on "All Print Page Buttons" and "Account" pages.
         * The "Help & Demo" dashboard page is handled directly by AdminMenu.php::ppb_render_demo_page().
         */
        public function render_admin_notices(): void {
            if ( isset( $_GET['page'] ) && 'ppb_demo_page' === $_GET['page'] ) {
                return;
            }
            $this->render();
        }

        /* ------------------------------------------------------------------ */
        /*  Screen, Expiration, Dismissal & Pro Helpers                       */
        /* ------------------------------------------------------------------ */

        /**
         * Check if the current admin screen is one of the target Print Page screens:
         * - All Print Page Buttons (edit.php?post_type=print_page)
         * - Help & Demo (ppb_demo_page)
         * - Account (print_page-account or print-page-account)
         * Excludes: "Add New" and post editors.
         */
        private function is_target_screen(): bool {
            global $pagenow;
            // 1. Explicitly exclude post creation and post editing
            if ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) {
                return false;
            }
            if ( function_exists( 'get_current_screen' ) ) {
                $screen = get_current_screen();
                if ( $screen && 'post' === $screen->base ) {
                    return false;
                }
            }

            // 2. Check for "All Print Page Buttons" list table
            if ( isset( $_GET['post_type'] ) && 'print_page' === $_GET['post_type'] && ! isset( $_GET['page'] ) ) {
                return true;
            }

            // 3. Check for "Help & Demo"
            if ( isset( $_GET['page'] ) && 'ppb_demo_page' === $_GET['page'] ) {
                return true;
            }

            // 4. Check for Freemius "Account"
            if ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'account' ) !== false ) {
                if ( ( isset( $_GET['post_type'] ) && 'print_page' === $_GET['post_type'] ) || strpos( $_GET['page'], 'print' ) !== false ) {
                    return true;
                }
            }

            // 5. Fallback via $screen object
            if ( function_exists( 'get_current_screen' ) ) {
                $screen = get_current_screen();
                if ( $screen ) {
                    if ( 'edit' === $screen->base && 'print_page' === $screen->post_type ) {
                        return true;
                    }
                    $id = $screen->id ?? '';
                    if ( strpos( $id, 'ppb_demo_page' ) !== false || strpos( $id, 'print_page' ) !== false || strpos( $id, 'print-page' ) !== false ) {
                        return true;
                    }
                }
            }

            return false;
        }

        /**
         * Check if current date is past December 5, 2026
         */
        private function is_expired(): bool {
            $expiry_timestamp = strtotime( self::EXPIRATION_DATE );
            $current_time     = time();
            return ( $current_time > $expiry_timestamp );
        }

        /**
         * Check if current environment is development
         */
        public static function is_development(): bool {
            $env = strtolower( trim( (string) self::environment ) );
            return in_array( $env, [ 'development', 'developmet', 'dev' ], true );
        }

        /**
         * Check if current user has permanently dismissed the banner
         */
        private function is_dismissed(): bool {
            // In development, never consider it permanently dismissed
            if ( $this->is_development() ) {
                return false;
            }

            if ( isset( $_GET['reset_promo'] ) ) {
                delete_user_meta( get_current_user_id(), self::DISMISSED_KEY );
                return false;
            }
            return (bool) get_user_meta( get_current_user_id(), self::DISMISSED_KEY, true );
        }

        /**
         * Determine if the banner should be omitted
         */
        private function should_skip(): bool {
            // Must be one of the target screens (All Print Page Buttons, Help & Demo, Account)
            if ( ! $this->is_target_screen() ) {
                return true;
            }

            // In development environment, reload will always make it reappear (never skip)
            if ( $this->is_development() ) {
                return false;
            }

            // Production checks:
            // 1. Automatically remove after Dec 5, 2026
            if ( $this->is_expired() ) {
                return true;
            }

            // 2. Never display again once dismissed by the user
            if ( $this->is_dismissed() ) {
                return true;
            }

            // 3. Hide for Pro / Premium users
            if ( function_exists( 'ppbIsPremium' ) && ppbIsPremium() ) {
                return true;
            }
            if ( function_exists( 'pp_fs' ) && pp_fs()->can_use_premium_code() ) {
                return true;
            }

            return false;
        }

        /* ------------------------------------------------------------------ */
        /*  CSS Styles                                                        */
        /* ------------------------------------------------------------------ */

        public function enqueue_styles(): void {
            if ( self::$styles_rendered || $this->should_skip() ) { return; }
            self::$styles_rendered = true;
            ?>
            <style id="ppb-campaign-card-css">
            #ppb-campaign-card {
                --ppb-primary:        #2167b6ff;
                --ppb-primary-hover:  #0d55a8ff;
                --ppb-accent:         #6366f1;
                --ppb-dark:           #041d8aff;
                --ppb-text:           #0f172a;
                --ppb-muted:          #64748b;

                background: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 8px !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03) !important;
                display: flex !important;
                position: relative !important;
                top: auto !important;
                left: auto !important;
                width: auto !important;
                height: auto !important;
                opacity: 1 !important;
                visibility: visible !important;
                z-index: 99 !important;
                align-items: stretch !important;
                min-height: 84px;
                overflow: hidden;
                margin: 16px 20px 16px 0 !important;
                padding: 0 !important;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                box-sizing: border-box;
            }

            /* Left Full-Height Brand Icon Block */
            #ppb-campaign-card .ppb-brand-icon-box {
                width: 140px;
                min-width: 140px;
                background: linear-gradient(135deg, var(--ppb-primary) 0%, var(--ppb-dark) 100%);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 12px 10px;
                gap: 6px;
                box-sizing: border-box;
                flex-shrink: 0;
            }
            #ppb-campaign-card .ppb-brand-icon-box img {
                width: 58px;
                height: 58px;
                object-fit: contain;
                border-radius: 6px;
                filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.35));
            }
            #ppb-campaign-card .ppb-brand-icon-box svg {
                width: 36px;
                height: 36px;
                fill: #ffffff;
            }
            #ppb-campaign-card .ppb-brand-icon-box .ppb-brand-title {
                font-size: 11px;
                font-weight: 800;
                letter-spacing: .7px;
                text-transform: uppercase;
                color: #ffffff;
                text-align: center;
                line-height: 1.2;
            }

            /* Main Content Zone */
            #ppb-campaign-card .ppb-main-content {
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
                gap: 5px;
                padding: 16px 22px !important;
                padding-right: 42px !important;
            }

            /* Title Line */
            #ppb-campaign-card .ppb-title-line {
                font-size: 14.5px;
                font-weight: 700;
                color: var(--ppb-text);
                display: flex;
                align-items: center;
                gap: 8px;
                margin: 0;
                line-height: 1.35;
                flex-wrap: wrap;
            }

            /* "Save 45%" Badge beside Title */
            #ppb-campaign-card .ppb-offer-tag {
                background: #ffedd5;
                color: #c2410c;
                border: 1px solid #fed7aa;
                font-size: 11px;
                font-weight: 700;
                padding: 2px 8px;
                border-radius: 12px;
                letter-spacing: .2px;
                display: inline-flex;
                align-items: center;
            }

            /* Proper Description */
            #ppb-campaign-card .ppb-description {
                font-size: 13px;
                font-weight: 500;
                color: var(--ppb-muted);
                line-height: 1.5;
                margin: 0;
            }

            /* 3 Highlighted Pro Features */
            #ppb-campaign-card .ppb-features-row {
                display: flex;
                align-items: center;
                gap: 14px;
                font-size: 12px;
                color: var(--ppb-muted);
                flex-wrap: wrap;
                margin: 2px 0;
            }
            #ppb-campaign-card .ppb-features-row span {
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            #ppb-campaign-card .ppb-features-row .check-icon {
                color: #10b981;
                font-weight: 800;
            }

            /* Right Action Zone: Single CTA Button */
            #ppb-campaign-card .ppb-action-zone {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 14px 38px 14px 12px;
                flex-shrink: 0;
            }
            #ppb-campaign-card .ppb-action-link {
                background: var(--ppb-primary);
                color: #ffffff !important;
                padding: 9px 20px;
                border-radius: 5px;
                font-size: 12.5px;
                font-weight: 600;
                text-decoration: none !important;
                box-shadow: 0 1px 3px rgba(33, 103, 182, 0.3);
                transition: background-color .15s ease, transform .15s ease, box-shadow .15s ease;
                white-space: nowrap;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            #ppb-campaign-card .ppb-action-link:hover {
                background: var(--ppb-primary-hover);
                color: #ffffff !important;
                transform: translateY(-1px);
                box-shadow: 0 3px 10px rgba(33, 110, 182, 0.45);
            }

            /* Close Button (×) */
            #ppb-campaign-card .ppb-close-trigger {
                position: absolute;
                top: 8px;
                right: 8px;
                background: none;
                border: none;
                cursor: pointer;
                color: #94a3b8;
                font-size: 20px;
                line-height: 1;
                padding: 6px;
                transition: color .15s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10;
            }
            #ppb-campaign-card .ppb-close-trigger:hover {
                color: var(--ppb-text);
            }

            /* Responsive */
            @media (max-width: 960px) {
                #ppb-campaign-card .ppb-brand-icon-box {
                    width: 100px;
                    min-width: 100px;
                }
            }
            @media (max-width: 782px) {
                #ppb-campaign-card {
                    flex-direction: column;
                    align-items: stretch !important;
                }
                #ppb-campaign-card .ppb-brand-icon-box {
                    width: 100%;
                    min-height: 52px;
                    flex-direction: row;
                    gap: 8px;
                    padding: 8px 16px;
                    justify-content: flex-start;
                }
                #ppb-campaign-card .ppb-main-content {
                    padding: 14px 16px !important;
                }
                #ppb-campaign-card .ppb-action-zone {
                    padding: 0 16px 14px 16px;
                    justify-content: flex-start;
                }
            }
            </style>
            <?php
        }

        /* ------------------------------------------------------------------ */
        /*  Dismiss Script (AJAX + Animation)                                 */
        /* ------------------------------------------------------------------ */

        public function enqueue_scripts(): void {
            if ( self::$scripts_rendered || $this->should_skip() ) { return; }
            self::$scripts_rendered = true;
            $dismiss_nonce = wp_create_nonce( 'ppb_dismiss_promo' );
            $ajax_url      = admin_url( 'admin-ajax.php' );
            ?>
            <script id="ppb-campaign-card-js">
            (function () {
                var dismissTriggers = document.querySelectorAll('.ppb-dismiss-action');
                if (!dismissTriggers.length) return;

                dismissTriggers.forEach(function(trigger) {
                    trigger.addEventListener('click', function(e) {
                        // Only respond to trusted user clicks, ignore extension/synthetic triggers
                        if (e && !e.isTrusted) return;
                        e.preventDefault();
                        var card = document.getElementById('ppb-campaign-card');
                        if (card) {
                            card.style.transition = 'opacity .25s ease, transform .25s ease';
                            card.style.opacity    = '0';
                            card.style.transform  = 'translateY(-6px)';
                            setTimeout(function() { card.remove(); }, 260);
                        }
                        var fd = new FormData();
                        fd.append('action', 'ppb_dismiss_promo_banner');
                        fd.append('nonce',  '<?php echo esc_js( $dismiss_nonce ); ?>');
                        fetch('<?php echo esc_url( $ajax_url ); ?>', {
                            method: 'POST',
                            credentials: 'same-origin',
                            body: fd
                        });
                    });
                });
            })();
            </script>
            <?php
        }

        /* ------------------------------------------------------------------ */
        /*  Render Single Banner                                              */
        /* ------------------------------------------------------------------ */

        public function render(): void {
            if ( self::$banner_rendered || $this->should_skip() ) { return; }
            self::$banner_rendered = true;

            $this->enqueue_styles();

            $coupon_url = esc_url( self::COUPON_URL );
            $icon_url   = esc_url( 'https://ps.w.org/print-page/assets/icon-128x128.png' );
            ?>
            <div id="ppb-campaign-card">

                <!-- Left Brand Icon Block -->
                <div class="ppb-brand-icon-box">
                    <img src="<?php echo $icon_url; ?>" alt="Print Page" onerror="this.style.display='none';this.nextElementSibling.style.display='block';" />
                    <svg style="display:none;" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/>
                    </svg>
                    <span class="ppb-brand-title"><?php esc_html_e( 'Print Page', 'print-page' ); ?></span>
                </div>

                <!-- Main Content Zone -->
                <div class="ppb-main-content">
                    <div class="ppb-title-line">
                        <span><?php esc_html_e( 'Black Friday Special: Get an Exclusive Discount on Print Page Pro!', 'print-page' ); ?></span>
                        <span class="ppb-offer-tag"><?php esc_html_e( 'Save 45%', 'print-page' ); ?></span>
                    </div>

                    <p class="ppb-description">
                        <?php esc_html_e( 'Get 45% off on Print Page Pro this Black Friday! Adds modal printing, precision section selectors, and advanced styling controls.', 'print-page' ); ?>
                    </p>

                    <!-- 3 Highlighted Pro Features -->
                    <div class="ppb-features-row">
                        <span><strong class="check-icon">✓</strong> <?php esc_html_e( 'Advanced Print & Modal Controls', 'print-page' ); ?></span>
                        <span><strong class="check-icon">✓</strong> <?php esc_html_e( '10 Button Variants & Custom Icons', 'print-page' ); ?></span>
                        <span><strong class="check-icon">✓</strong> <?php esc_html_e( 'Typography, Spacing & Shadow Controls', 'print-page' ); ?></span>
                    </div>

                </div>

                <!-- Right Action Zone: Single CTA Button -->
                <div class="ppb-action-zone">
                    <a href="<?php echo $coupon_url; ?>" target="_blank" rel="noopener noreferrer" class="ppb-action-link">
                        <?php esc_html_e( 'Unlock PRO Features →', 'print-page' ); ?>
                    </a>
                </div>

                <!-- Dismiss Button (×) -->
                <button type="button"
                        class="ppb-close-trigger ppb-dismiss-action"
                        title="<?php esc_attr_e( 'Close', 'print-page' ); ?>"
                        aria-label="<?php esc_attr_e( 'Close', 'print-page' ); ?>">
                    &times;
                </button>

            </div>
            <?php
            $this->enqueue_scripts();
        }

        /* ------------------------------------------------------------------ */
        /*  AJAX Handler: Permanent Dismissal                                 */
        /* ------------------------------------------------------------------ */

        public function ajax_dismiss(): void {
            check_ajax_referer( 'ppb_dismiss_promo', 'nonce' );
            // In production, permanently dismiss for this user across all pages
            if ( ! $this->is_development() ) {
                update_user_meta( get_current_user_id(), self::DISMISSED_KEY, 1 );
            }
            wp_send_json_success();
        }
    }

    PPB_PromoBanner::instance();
}
