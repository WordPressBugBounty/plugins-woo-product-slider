<?php
/**
 * Variation Gallery Onboarding controller.
 *
 * @package ShapedPlugin\VariationGalleryOnboarding
 */

namespace ShapedPlugin\VariationGalleryOnboarding;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Renders the "Add Gallery Images" onboarding UI inside WooCommerce product
 * variation panels and handles installing / activating the free WooGallery
 * plugin from WordPress.org.
 *
 * The class is a singleton and is booted at most once per request by the shared
 * loader (see load.php), even when several plugins bundle this module.
 *
 * This file is loaded by an explicit require_once in load.php, never by an
 * autoloader: the namespace and filename intentionally sit outside the host
 * plugin's PSR-4 root so the module stays portable across plugins, and so the
 * loader's "highest bundled version wins" selection stays authoritative. Do not
 * reference this class without booting through load.php.
 *
 * If WooGallery (free or pro) is already active the module renders nothing —
 * WooGallery itself owns the variation gallery UI at that point.
 */
class Variation_Gallery_Onboarding {

	/**
	 * Module version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * WordPress.org slug of the free WooGallery plugin.
	 *
	 * @var string
	 */
	const SLUG = 'gallery-slider-for-woocommerce';

	/**
	 * Plugin basename of the free WooGallery plugin.
	 *
	 * @var string
	 */
	const FREE_BASENAME = 'gallery-slider-for-woocommerce/woo-gallery-slider.php';

	/**
	 * Plugin basename of the pro WooGallery plugin.
	 *
	 * @var string
	 */
	const PRO_BASENAME = 'woo-gallery-slider-pro/woo-gallery-slider-pro.php';

	/**
	 * Shared AJAX / nonce action key.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'sp_vgo_onboarding';

	/**
	 * Singleton instance.
	 *
	 * @var Variation_Gallery_Onboarding|null
	 */
	protected static $instance = null;

	/**
	 * Base URL of the (booted) module directory, trailing-slashed.
	 *
	 * @var string
	 */
	protected $assets_url = '';

	/**
	 * Version of the booted copy.
	 *
	 * @var string
	 */
	protected $booted_version = self::VERSION;

	/**
	 * Cached WooGallery active state for the current request.
	 *
	 * @var bool|null
	 */
	protected $woogallery_active = null;

	/**
	 * Retrieve / create the singleton.
	 *
	 * @param string $assets_url     Base URL of the booted module directory.
	 * @param string $booted_version Version string of the booted copy.
	 * @return Variation_Gallery_Onboarding
	 */
	public static function instance( $assets_url = '', $booted_version = self::VERSION ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $assets_url, $booted_version );
		}
		return self::$instance;
	}

	/**
	 * Constructor. Wires up hooks (once).
	 *
	 * @param string $assets_url     Base URL of the booted module directory.
	 * @param string $booted_version Version string of the booted copy.
	 */
	protected function __construct( $assets_url, $booted_version ) {
		$this->assets_url     = trailingslashit( $assets_url );
		$this->booted_version = $booted_version ? $booted_version : self::VERSION;

		// AJAX endpoints (install / activate) — admin-ajax context only.
		add_action( 'wp_ajax_sp_vgo_install', array( $this, 'ajax_install' ) );
		add_action( 'wp_ajax_sp_vgo_activate', array( $this, 'ajax_activate' ) );

		// UI only makes sense inside the admin product editor.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		// Same hook + priority (10) WooGallery uses, so the onboarding button
		// lands in the identical native position within the variation panel.
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'render_button' ), 10 );
		add_action( 'admin_footer', array( $this, 'render_modal' ) );
	}

	/**
	 * Prevent cloning of the singleton.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing of the singleton.
	 */
	public function __wakeup() {}

	// -- Detection helpers. --------------------------------------------------

	/**
	 * Whether WooGallery (free or pro) is active.
	 *
	 * @return bool
	 */
	public function is_woogallery_active() {
		// Memoized: called once per variation row via should_render(), plus in
		// enqueue/modal. Active state is constant within a single request.
		if ( null === $this->woogallery_active ) {
			$this->load_plugin_functions();
			$this->woogallery_active = is_plugin_active( self::FREE_BASENAME ) || is_plugin_active( self::PRO_BASENAME );
		}
		return $this->woogallery_active;
	}

	/**
	 * Whether WooGallery (free or pro) is installed (regardless of active state).
	 *
	 * @return bool
	 */
	public function is_woogallery_installed() {
		$this->load_plugin_functions();
		$installed = array_keys( get_plugins() );
		return in_array( self::FREE_BASENAME, $installed, true ) || in_array( self::PRO_BASENAME, $installed, true );
	}

	/**
	 * Whether the current user may drive the onboarding flow.
	 *
	 * @return bool
	 */
	protected function user_can_onboard() {
		return current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' );
	}

	/**
	 * Whether the current admin screen is the product editor.
	 *
	 * @return bool
	 */
	protected function is_product_editor_screen() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}
		$screen = get_current_screen();
		return $screen && 'product' === $screen->post_type && in_array( $screen->base, array( 'post' ), true );
	}

	/**
	 * Whether all conditions to render the onboarding UI are met.
	 *
	 * @return bool
	 */
	protected function should_render() {
		return $this->user_can_onboard() && ! $this->is_woogallery_active();
	}

	/**
	 * Lazily require WordPress plugin admin functions.
	 *
	 * @return void
	 */
	protected function load_plugin_functions() {
		if ( ! function_exists( 'is_plugin_active' ) || ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	// -- Rendering. ----------------------------------------------------------

	/**
	 * Enqueue module assets on the product editor screen.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		// Note: this deliberately does NOT gate on should_render(). The script is
		// also needed AFTER WooGallery becomes active, to run the post-reload
		// handoff (click WooGallery's own "Add Gallery Images" button). By then
		// our button/modal no longer render, but the handoff still must fire.
		if ( ! $this->is_product_editor_screen() || ! $this->user_can_onboard() ) {
			return;
		}

		$woogallery_active = $this->is_woogallery_active();

		wp_enqueue_script(
			'sp-vgo',
			$this->assets_url . 'assets/js/variation-gallery-onboarding.js',
			array( 'jquery', 'wp-util' ),
			$this->booted_version,
			true
		);

		$data = array(
			'wooGalleryActive' => $woogallery_active,
			// Not HTML-escaped: wp_localize_script() JSON-encodes the payload and
			// the JS prints these through .text(), so entity-escaping here would
			// surface literal &#039; in any translation containing an apostrophe.
			'i18n'             => array(
				'preparing'    => __( 'Preparing to Add Variation Gallery Images', 'woo-product-slider' ),
				'genericError' => __( 'Something went wrong. Please try again.', 'woo-product-slider' ),
				'networkError' => __( 'Network error. Please check your connection and try again.', 'woo-product-slider' ),
			),
		);

		// Onboarding-only assets: the button, modal, install/activate flow. Not
		// needed once WooGallery is active (WooGallery owns the UI + media then).
		if ( ! $woogallery_active ) {
			wp_enqueue_style(
				'sp-vgo',
				$this->assets_url . 'assets/css/variation-gallery-onboarding.css',
				array(),
				$this->booted_version
			);

			$data['ajaxUrl']     = admin_url( 'admin-ajax.php' );
			$data['nonce']       = wp_create_nonce( self::NONCE_ACTION );
			$data['isInstalled'] = $this->is_woogallery_installed();
			$data['actions']     = array(
				'install'  => 'sp_vgo_install',
				'activate' => 'sp_vgo_activate',
			);
		}

		wp_localize_script( 'sp-vgo', 'spVGO', $data );
	}

	/**
	 * Render the "Add Gallery Images" button inside a variation panel.
	 *
	 * Mirrors WooGallery's own variation markup exactly (same
	 * `woocommerce_product_after_variable_attributes` hook, same wrapper /
	 * heading / button classes) so the onboarding entry point sits in the
	 * native WooGallery position with matching styling. WooGallery itself hooks
	 * this at priority 10 — see gallery-slider-for-woocommerce
	 * `Woo_Gallery_Slider_Admin::woocommerce_add_gallery_product_variation()`.
	 *
	 * Hooked with zero accepted args — the button is a generic onboarding entry
	 * point (it opens the shared modal) and needs no per-variation context.
	 *
	 * @return void
	 */
	public function render_button() {
		if ( ! $this->should_render() ) {
			return;
		}
		?>
		<div class="sp-vgo-variation-gallery form-row form-row-full">
			<p>
				<button type="button" class="button sp-vgo-open-modal">
					<?php esc_html_e( 'Add Gallery Images', 'woo-product-slider' ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the onboarding modal once in the admin footer.
	 *
	 * @return void
	 */
	public function render_modal() {
		if ( ! $this->is_product_editor_screen() || ! $this->should_render() ) {
			return;
		}

		$img = $this->assets_url . 'assets/images/';

		// Escaped once, late, at output (see the loop below). Escaping here as well
		// would render "Zoom &amp; Lightbox" to the user.
		$benefits = array(
			__( 'Multiple Images for Every Product Variation', 'woo-product-slider' ),
			__( 'Add Videos to the Variation Gallery Images', 'woo-product-slider' ),
			__( 'Zoom & Lightbox for Product and Variation Gallery', 'woo-product-slider' ),
		);
		?>
		<div class="sp-vgo-modal-wrap" id="sp-vgo-modal" role="dialog" aria-modal="true" aria-labelledby="sp-vgo-title" hidden>
			<div class="sp-vgo-modal-backdrop"></div>
			<div class="sp-vgo-modal" role="document">

				<div class="sp-vgo-modal-media">
					<button type="button" class="sp-vgo-modal-close" aria-label="<?php echo esc_attr__( 'Close', 'woo-product-slider' ); ?>">
						<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
					</button>

					<?php // Decorative variation-gallery preview. Inline styles are kept verbatim from the source design so the tuned cursor/gallery motion (see @keyframes vg2c-* in the stylesheet) stays pixel-accurate. ?>
					<div class="sp-vgo-vg" aria-hidden="true">
						<div class="sp-vgo-vg-card">
							<div class="sp-vgo-vg-left">
								<div class="sp-vgo-vg-feature">
									<img src="<?php echo esc_url( $img . 'blue1.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; width:100%; height:100%; object-fit:contain; padding:8px; box-sizing:border-box; animation:vg2c-blue 8s ease-in-out 1s infinite backwards;">
									<img src="<?php echo esc_url( $img . 'cyan1.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; width:100%; height:100%; object-fit:contain; padding:8px; box-sizing:border-box; opacity:0; animation:vg2c-cyan 8s ease-in-out 1s infinite backwards;">
									<img src="<?php echo esc_url( $img . 'yellow1.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; width:100%; height:100%; object-fit:contain; padding:20px; box-sizing:border-box; opacity:0; animation:vg2c-yellow 8s ease-in-out 1s infinite backwards;">
									<img src="<?php echo esc_url( $img . 'yellow4.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; width:100%; height:100%; object-fit:contain; padding:20px; box-sizing:border-box; opacity:0; animation:vg2c-feat2 8s ease-in-out 1s infinite backwards;">
									<span style="position:absolute; inset:0; margin:auto; width:38px; height:38px; border-radius:50%; background:rgba(47,82,240,.85); display:flex; align-items:center; justify-content:center; opacity:0; animation:vg2c-feat2 8s ease-in-out 1s infinite backwards;"><svg width="17" height="17" viewBox="0 0 24 24"><path d="M8 5 L19 12 L8 19 Z" fill="#fff"></path></svg></span>
									<img src="<?php echo esc_url( $img . 'yellow3.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; width:100%; height:100%; object-fit:contain; padding:20px; box-sizing:border-box; opacity:0; animation:vg2c-feat3 8s ease-in-out 1s infinite backwards;">
									<span style="position:absolute; left:6px; top:50%; transform:translateY(-50%); width:26px; height:26px; border-radius:50%; background:rgba(255,255,255,.88); box-shadow:0 2px 7px rgba(0,0,0,.16); display:flex; align-items:center; justify-content:center; opacity:0; animation:vg2c-nav 8s ease-in-out 1s infinite backwards;"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M15 6 L9 12 L15 18" stroke="#3a3d44" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"></path></svg></span>
									<span style="position:absolute; right:6px; top:50%; transform:translateY(-50%); width:26px; height:26px; border-radius:50%; background:rgba(255,255,255,.88); box-shadow:0 2px 7px rgba(0,0,0,.16); display:flex; align-items:center; justify-content:center; opacity:0; animation:vg2c-nav 8s ease-in-out 1s infinite backwards;"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M9 6 L15 12 L9 18" stroke="#3a3d44" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"></path></svg></span>
								</div>
								<div class="sp-vgo-vg-thumbs">
									<div class="sp-vgo-vg-thumb"><img src="<?php echo esc_url( $img . 'blue2.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; margin:auto; max-width:80%; max-height:80%; object-fit:contain; animation:vg2c-bt 8s ease-in-out 1s infinite backwards;"><img src="<?php echo esc_url( $img . 'cyan2.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; margin:auto; max-width:80%; max-height:80%; object-fit:contain; opacity:0; animation:vg2c-t1 8s ease-in-out 1s infinite backwards;"><img src="<?php echo esc_url( $img . 'yellow2.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; margin:auto; max-width:80%; max-height:80%; object-fit:contain; opacity:0; animation:vg2c-y1 8s ease-in-out 1s infinite backwards;"></div>
									<div class="sp-vgo-vg-thumb"><img src="<?php echo esc_url( $img . 'blue4.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; margin:auto; max-width:80%; max-height:80%; object-fit:contain; animation:vg2c-bt 8s ease-in-out 1s infinite backwards;"><img src="<?php echo esc_url( $img . 'cyan4.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; margin:auto; max-width:80%; max-height:80%; object-fit:contain; opacity:0; animation:vg2c-t2 8s ease-in-out 1s infinite backwards;"><img src="<?php echo esc_url( $img . 'yellow4.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; margin:auto; max-width:80%; max-height:80%; object-fit:contain; opacity:0; animation:vg2c-y2 8s ease-in-out 1s infinite backwards;"><span style="position:absolute; inset:0; margin:auto; width:19px; height:19px; border-radius:50%; background:#2f52f0; display:flex; align-items:center; justify-content:center; opacity:0; animation:vg2c-vid2 8s ease-in-out 1s infinite backwards;"><span class="dashicons dashicons-controls-play" style="color:#fff; font-size:12px; width:12px; height:12px; line-height:12px; margin-left:1px;"></span></span><span style="position:absolute; inset:0; border:2px solid #2f52f0; border-radius:6px; opacity:0; animation:vg2c-act2 8s ease-in-out 1s infinite backwards;"></span></div>
									<div class="sp-vgo-vg-thumb"><img src="<?php echo esc_url( $img . 'blue3.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; margin:auto; max-width:80%; max-height:80%; object-fit:contain; animation:vg2c-bt 8s ease-in-out 1s infinite backwards;"><img src="<?php echo esc_url( $img . 'cyan3.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; margin:auto; max-width:80%; max-height:80%; object-fit:contain; opacity:0; animation:vg2c-t3 8s ease-in-out 1s infinite backwards;"><img src="<?php echo esc_url( $img . 'yellow3.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; margin:auto; max-width:80%; max-height:80%; object-fit:contain; opacity:0; animation:vg2c-y3 8s ease-in-out 1s infinite backwards;"><span style="position:absolute; inset:0; margin:auto; width:19px; height:19px; border-radius:50%; background:#2f52f0; display:flex; align-items:center; justify-content:center; animation:vg2c-vid3 8s ease-in-out 1s infinite backwards;"><span class="dashicons dashicons-controls-play" style="color:#fff; font-size:12px; width:12px; height:12px; line-height:12px; margin-left:1px;"></span></span><span style="position:absolute; inset:0; border:2px solid #2f52f0; border-radius:6px; opacity:0; animation:vg2c-act3 8s ease-in-out 1s infinite backwards;"></span></div>
									<div class="sp-vgo-vg-thumb"><img src="<?php echo esc_url( $img . 'blue5.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; margin:auto; max-width:80%; max-height:80%; object-fit:contain; animation:vg2c-bt 8s ease-in-out 1s infinite backwards;"><img src="<?php echo esc_url( $img . 'cyan5.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; margin:auto; max-width:80%; max-height:80%; object-fit:contain; opacity:0; animation:vg2c-t4 8s ease-in-out 1s infinite backwards;"><img src="<?php echo esc_url( $img . 'yellow5.png' ); ?>" alt="" loading="lazy" decoding="async" style="position:absolute; inset:0; margin:auto; max-width:80%; max-height:80%; object-fit:contain; opacity:0; animation:vg2c-y4 8s ease-in-out 1s infinite backwards;"></div>
								</div>
							</div>
							<div class="sp-vgo-vg-right">
								<div class="sp-vgo-vg-line" style="width:80%;"></div>
								<div class="sp-vgo-vg-line" style="width:55%; margin-top:7px;"></div>
								<div class="sp-vgo-vg-line sp-vgo-vg-line--title" style="width:40%; margin-top:12px;"></div>
								<div class="sp-vgo-vg-swatches">
									<div class="sp-vgo-vg-swatch"><span style="width:22px; height:22px; border-radius:50%; background:#2447d6; display:block;"></span><span style="position:absolute; inset:0; border-radius:50%; box-shadow:0 0 0 2px #fff,0 0 0 4px #7f54b3; animation:vg2c-r1 8s ease-in-out 1s infinite backwards;"></span></div>
									<div class="sp-vgo-vg-swatch"><span style="width:22px; height:22px; border-radius:50%; background:#12abe6; display:block;"></span><span style="position:absolute; inset:0; border-radius:50%; box-shadow:0 0 0 2px #fff,0 0 0 4px #7f54b3; opacity:0; animation:vg2c-r2 8s ease-in-out 1s infinite backwards;"></span></div>
									<div class="sp-vgo-vg-swatch"><span style="width:22px; height:22px; border-radius:50%; background:#f4d21e; display:block;"></span><span style="position:absolute; inset:0; border-radius:50%; box-shadow:0 0 0 2px #fff,0 0 0 4px #7f54b3; opacity:0; animation:vg2c-r3 8s ease-in-out 1s infinite backwards;"></span></div>
									<div style="position:absolute; left:0; top:-2px; z-index:40; pointer-events:none; opacity:0; animation:vg2c-entry 1s ease-out forwards, vg2c-loop 8s ease-in-out 1s infinite;"><svg width="17" height="23" viewBox="0 0 24 32" fill="none"><path d="M3 2 L3 25 L9.5 19 L13.5 29 L17 27.3 L13 17.5 L21 17.5 Z" fill="#1f2937" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"></path></svg></div>
								</div>
								<div class="sp-vgo-vg-cart"><?php echo esc_html__( 'Add to cart', 'woo-product-slider' ); ?></div>
							</div>
						</div>
					</div>
				</div>

				<div class="sp-vgo-modal-body">
					<h2 class="sp-vgo-modal-title" id="sp-vgo-title">
						<?php echo esc_html__( 'Show Multiple Images Per Variation', 'woo-product-slider' ); ?>
					</h2>
					<p class="sp-vgo-modal-subtitle">
						<?php echo esc_html__( 'Add images, video, zoom & lightbox to your product variation — and boost sales.', 'woo-product-slider' ); ?>
					</p>

					<ul class="sp-vgo-benefits">
						<?php foreach ( $benefits as $benefit ) : ?>
							<li>
								<span class="sp-vgo-benefit-icon" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
								<span class="sp-vgo-benefit-text"><?php echo esc_html( $benefit ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>

					<div class="sp-vgo-status is-error" role="alert" aria-live="assertive" hidden>
						<span class="sp-vgo-status-text"></span>
					</div>

					<div class="sp-vgo-actions">
						<button type="button" class="button button-primary button-hero sp-vgo-create-gallery">
							<span class="sp-vgo-spinner" aria-hidden="true"></span>
							<span class="sp-vgo-btn-label"><?php echo esc_html__( 'Add Variation Gallery Images', 'woo-product-slider' ); ?></span>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	// -- AJAX handlers. ------------------------------------------------------

	/**
	 * AJAX: install the free WooGallery plugin from WordPress.org.
	 *
	 * @return void
	 */
	public function ajax_install() {
		$this->verify_ajax( 'install_plugins' );

		if ( $this->is_woogallery_installed() ) {
			wp_send_json_success(
				array( 'message' => esc_html__( 'Reno Product Gallery is already installed.', 'woo-product-slider' ) )
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => self::SLUG,
				'fields' => array( 'sections' => false ),
			)
		);

		if ( is_wp_error( $api ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: error message from WordPress.org. */
						esc_html__( 'Could not fetch Reno Product Gallery from WordPress.org: %s', 'woo-product-slider' ),
						$api->get_error_message()
					),
				)
			);
		}

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( is_wp_error( $skin->result ) ) {
			wp_send_json_error( array( 'message' => $skin->result->get_error_message() ) );
		}

		// WP_Error::has_errors() is WordPress 5.1+; this plugin supports 5.0, so the
		// error-code list is checked instead. get_error_codes() is used rather than
		// get_error_code() because it is exact parity: has_errors() is a non-empty
		// check, whereas a single code of '0' or 0 would read as falsy. Note also
		// that get_error_messages() returns an ARRAY — join it, because the JS
		// consumer prints the value verbatim and would otherwise show jQuery's
		// comma-joined coercion.
		$skin_errors = $skin->get_errors();
		if ( is_wp_error( $skin_errors ) && $skin_errors->get_error_codes() ) {
			wp_send_json_error( array( 'message' => implode( ' ', $skin_errors->get_error_messages() ) ) );
		}

		if ( false === $result ) {
			// Typically means direct filesystem access was unavailable.
			wp_send_json_error(
				array( 'message' => esc_html__( 'Installation failed. Unable to access the filesystem — please install Reno Product Gallery manually.', 'woo-product-slider' ) )
			);
		}

		wp_send_json_success(
			array( 'message' => esc_html__( 'Reno Product Gallery installed successfully.', 'woo-product-slider' ) )
		);
	}

	/**
	 * AJAX: activate the installed WooGallery plugin.
	 *
	 * @return void
	 */
	public function ajax_activate() {
		$this->verify_ajax( 'activate_plugins' );

		$this->load_plugin_functions();
		$installed = array_keys( get_plugins() );

		// Prefer pro if present, otherwise free.
		if ( in_array( self::PRO_BASENAME, $installed, true ) ) {
			$basename = self::PRO_BASENAME;
		} elseif ( in_array( self::FREE_BASENAME, $installed, true ) ) {
			$basename = self::FREE_BASENAME;
		} else {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Reno Product Gallery is not installed.', 'woo-product-slider' ) )
			);
		}

		if ( is_plugin_active( $basename ) ) {
			wp_send_json_success(
				array( 'message' => esc_html__( 'Reno Product Gallery is already active.', 'woo-product-slider' ) )
			);
		}

		$result = activate_plugin( $basename );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: activation error message. */
						esc_html__( 'Activation failed: %s', 'woo-product-slider' ),
						$result->get_error_message()
					),
				)
			);
		}

		wp_send_json_success(
			array( 'message' => esc_html__( 'Reno Product Gallery activated successfully.', 'woo-product-slider' ) )
		);
	}

	/**
	 * Shared AJAX guard: nonce + capability. Sends a JSON error and dies on failure.
	 *
	 * @param string $capability Capability required for the action.
	 * @return void
	 */
	protected function verify_ajax( $capability ) {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Security check failed. Please reload the page and try again.', 'woo-product-slider' ) ),
				403
			);
		}

		if ( ! current_user_can( $capability ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'You do not have permission to perform this action.', 'woo-product-slider' ) ),
				403
			);
		}
	}
}
