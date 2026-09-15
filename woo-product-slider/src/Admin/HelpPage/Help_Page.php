<?php
/**
 * The help page for the woo-product-slider
 *
 * @package woo-product-slider
 * @subpackage woo-product-slider/admin
 */

namespace ShapedPlugin\WooProductSlider\Admin\HelpPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}  // if direct access.

use ShapedPlugin\WooProductSlider\Admin\Notices\Rebrand_Notice;

/**
 * The help class for the woo-product-slider
 */
class Help_Page {

	/**
	 * Single instance of the class
	 *
	 * @var null
	 */
	protected static $_instance = null;

	/**
	 * Plugins Path variable.
	 *
	 * @var array
	 */
	protected static $plugins = array(
		'woo-product-slider'             => 'main.php',
		'gallery-slider-for-woocommerce' => 'woo-gallery-slider.php',
		'post-carousel'                  => 'main.php',
		'easy-accordion-free'            => 'plugin-main.php',
		'logo-carousel-free'             => 'main.php',
		'location-weather'               => 'main.php',
		'woo-quickview'                  => 'woo-quick-view.php',
		'wp-expand-tabs-free'            => 'plugin-main.php',

	);

	/**
	 * Welcome pages
	 *
	 * @var array
	 */
	public $pages = array(
		'wps_help',
	);


	/**
	 * Not show this plugin list.
	 *
	 * @var array
	 */
	protected static $not_show_plugin_list = array( 'aitasi-coming-soon', 'latest-posts', 'widget-post-slider', 'easy-lightbox-wp', 'woo-product-slider' );

	/**
	 * Help Page construct function.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'help_admin_menu' ), 80 );

        $page   = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';// @codingStandardsIgnoreLine
		if ( 'wps_help' !== $page ) {
			return;
		}
		add_action( 'admin_print_scripts', array( $this, 'disable_admin_notices' ) );
		add_action( 'spwps_enqueue', array( $this, 'help_page_enqueue_scripts' ) );
	}

	/**
	 * Help page Instance
	 *
	 * @static
	 * @return self Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Help_page_enqueue_scripts function.
	 *
	 * @return void
	 */
	public function help_page_enqueue_scripts() {
		wp_enqueue_style( 'sp-woo-product-slider-help', SP_WPS_URL . 'Admin/HelpPage/css/help-page.min.css', array(), SP_WPS_VERSION );
		wp_enqueue_style( 'sp-woo-product-slider-fontello', SP_WPS_URL . 'Admin/HelpPage/css/fontello.min.css', array(), SP_WPS_VERSION );

		wp_enqueue_script( 'sp-woo-product-slider-help', SP_WPS_URL . 'Admin/HelpPage/js/help-page.min.js', array(), SP_WPS_VERSION, true );
	}

	/**
	 * Add admin menu.
	 *
	 * @return void
	 */
	public function help_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=sp_wps_shortcodes',
			__( 'Product Slider', 'woo-product-slider' ),
			__( 'Lite vs Pro', 'woo-product-slider' ),
			'manage_options',
			'edit.php?post_type=sp_wps_shortcodes&page=wps_help#lite-to-pro'
		);
		add_submenu_page(
			'edit.php?post_type=sp_wps_shortcodes',
			__( 'Reno Product Slider Help', 'woo-product-slider' ),
			__( 'Get Started', 'woo-product-slider' ),
			'manage_options',
			'wps_help',
			array(
				$this,
				'help_page_callback',
			)
		);

		$this->add_submenu_classes();
	}

	/**
	 * Tag the plugin submenu items with dedicated CSS classes.
	 *
	 * WordPress has no argument for this on add_submenu_page(), so the class
	 * slot (index 4) of the registered items is filled in directly. It keeps
	 * the admin menu styling off brittle :nth-child() selectors.
	 *
	 * @return void
	 */
	private function add_submenu_classes() {
		global $submenu;

		$parent = 'edit.php?post_type=sp_wps_shortcodes';

		if ( empty( $submenu[ $parent ] ) ) {
			return;
		}

		$classes = array(
			'edit.php?post_type=sp_wps_shortcodes&page=wps_help#lite-to-pro' => 'spwps-menu-lite-vs-pro',
			'wps_help' => 'spwps-menu-get-started',
		);

		foreach ( $submenu[ $parent ] as $index => $item ) {
			if ( isset( $item[2], $classes[ $item[2] ] ) ) {
				$submenu[ $parent ][ $index ][4] = isset( $item[4] ) ? trim( $item[4] . ' ' . $classes[ $item[2] ] ) : $classes[ $item[2] ]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}
	}

	/**
	 * Build the URL of a help page tab.
	 *
	 * @param string $hash Tab hash without the leading "#".
	 * @return string
	 */
	private function tab_url( $hash = '' ) {
		$url = admin_url( 'edit.php?post_type=sp_wps_shortcodes&page=wps_help' );

		return $hash ? $url . '#' . $hash : $url;
	}

	/**
	 * Spwps_ajax_help_page function.
	 *
	 * @return void
	 */
	public function spwps_plugins_info_api_help_page() {
		$plugins_arr = get_transient( 'spwps_plugins' );
		if ( false === $plugins_arr ) {
			$args = array(
				'author'   => 'shapedplugin',
				'per_page' => '120',
				'page'     => '1',
				'fields'   => array(
					'slug',
					'name',
					'version',
					'downloaded',
					'active_installs',
					'last_updated',
					'rating',
					'num_ratings',
					'short_description',
					'author',
					'icons',
				),
			);

			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}

			$plugin_info = plugins_api( 'query_plugins', $args );

			if ( ! is_wp_error( $plugin_info ) ) {

				$plugins_arr = array();
				if ( isset( $plugin_info->plugins ) && ( count( $plugin_info->plugins ) > 0 ) ) {
					foreach ( $plugin_info->plugins as $pl ) {
						if ( ! in_array( $pl['slug'], self::$not_show_plugin_list, true ) ) {
							$plugins_arr[] = array(
								'slug'              => $pl['slug'],
								'name'              => $pl['name'],
								'version'           => $pl['version'],
								'downloaded'        => $pl['downloaded'],
								'active_installs'   => $pl['active_installs'],
								'last_updated'      => strtotime( $pl['last_updated'] ),
								'rating'            => $pl['rating'],
								'num_ratings'       => $pl['num_ratings'],
								'short_description' => $pl['short_description'],
								'icons'             => $pl['icons']['2x'],
							);
						}
					}
				}

				set_transient( 'spwps_plugins', $plugins_arr, 24 * HOUR_IN_SECONDS );
			}
		}

		if ( is_array( $plugins_arr ) && ( count( $plugins_arr ) > 0 ) ) {
			$active_installs = array_column( $plugins_arr, 'active_installs' );
			array_multisort( $active_installs, SORT_DESC, $plugins_arr );

			foreach ( $plugins_arr as $plugin ) {
				$plugin_slug = $plugin['slug'];
				$plugin_icon = $plugin['icons'];
				if ( isset( self::$plugins[ $plugin_slug ] ) ) {
					$plugin_file = self::$plugins[ $plugin_slug ];
				} else {
					$plugin_file = $plugin_slug . '.php';
				}

				$details_link = network_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=' . $plugin['slug'] . '&amp;TB_iframe=true&amp;width=772&amp;height=550' );
				?>
				<div class="plugin-card <?php echo esc_attr( $plugin_slug ); ?>" id="<?php echo esc_attr( $plugin_slug ); ?>">
					<div class="plugin-card-top">
						<div class="name column-name">
							<h3>
								<a class="thickbox" title="<?php echo esc_attr( $plugin['name'] ); ?>" href="<?php echo esc_url( $details_link ); ?>">
						<?php echo esc_html( $plugin['name'] ); ?>
									<img src="<?php echo esc_url( $plugin_icon ); ?>" class="plugin-icon"/>
								</a>
							</h3>
						</div>
						<div class="action-links">
							<ul class="plugin-action-buttons">
								<li>
						<?php
						if ( $this->is_plugin_installed( $plugin_slug, $plugin_file ) ) {
							if ( $this->is_plugin_active( $plugin_slug, $plugin_file ) ) {
								?>
										<button type="button" class="button button-disabled" disabled="disabled">Active</button>
									<?php
							} else {
								?>
											<a href="<?php echo esc_url( $this->activate_plugin_link( $plugin_slug, $plugin_file ) ); ?>" class="button button-primary activate-now">
									<?php esc_html_e( 'Activate', 'woo-product-slider' ); ?>
											</a>
									<?php
							}
						} else {
							?>
										<a href="<?php echo esc_url( $this->install_plugin_link( $plugin_slug ) ); ?>" class="button install-now">
								<?php esc_html_e( 'Install Now', 'woo-product-slider' ); ?>
										</a>
								<?php } ?>
								</li>
								<li>
									<a href="<?php echo esc_url( $details_link ); ?>" class="thickbox open-plugin-details-modal" aria-label="<?php echo esc_attr( 'More information about ' . $plugin['name'] ); ?>" title="<?php echo esc_attr( $plugin['name'] ); ?>">
								<?php esc_html_e( 'More Details', 'woo-product-slider' ); ?>
									</a>
								</li>
							</ul>
						</div>
						<div class="desc column-description">
							<p><?php echo esc_html( isset( $plugin['short_description'] ) ? $plugin['short_description'] : '' ); ?></p>
							<p class="authors"> <cite>By <a href="https://shapedplugin.com/">ShapedPlugin LLC</a></cite></p>
						</div>
					</div>
					<?php
					echo '<div class="plugin-card-bottom">';

					if ( isset( $plugin['rating'], $plugin['num_ratings'] ) ) {
						?>
						<div class="vers column-rating">
							<?php
							wp_star_rating(
								array(
									'rating' => $plugin['rating'],
									'type'   => 'percent',
									'number' => $plugin['num_ratings'],
								)
							);
							?>
							<span class="num-ratings">(<?php echo esc_html( number_format_i18n( $plugin['num_ratings'] ) ); ?>)</span>
						</div>
						<?php
					}
					if ( isset( $plugin['version'] ) ) {
						?>
						<div class="column-updated">
							<strong><?php esc_html_e( 'Version:', 'woo-product-slider' ); ?></strong>
							<span><?php echo esc_html( $plugin['version'] ); ?></span>
						</div>
							<?php
					}

					if ( isset( $plugin['active_installs'] ) ) {
						?>
						<div class="column-downloaded">
						<?php echo esc_html( number_format_i18n( $plugin['active_installs'] ) ) . esc_html__( '+ Active Installations', 'woo-product-slider' ); ?>
						</div>
									<?php
					}

					if ( isset( $plugin['last_updated'] ) ) {
						?>
						<div class="column-compatibility">
							<strong><?php esc_html_e( 'Last Updated:', 'woo-product-slider' ); ?></strong>
							<span><?php echo esc_html( human_time_diff( $plugin['last_updated'] ) ) . ' ' . esc_html__( 'ago', 'woo-product-slider' ); ?></span>
						</div>
									<?php
					}

					echo '</div>';
					?>
				</div>
				<?php
			}
		}
	}

	/**
	 * Check plugins installed function.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @param string $plugin_file Plugin file.
	 * @return boolean
	 */
	public function is_plugin_installed( $plugin_slug, $plugin_file ) {
		return file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug . '/' . $plugin_file );
	}

	/**
	 * Check active plugin function
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @param string $plugin_file Plugin file.
	 * @return boolean
	 */
	public function is_plugin_active( $plugin_slug, $plugin_file ) {
		return is_plugin_active( $plugin_slug . '/' . $plugin_file );
	}

	/**
	 * Install plugin link.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return string
	 */
	public function install_plugin_link( $plugin_slug ) {
		return wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug ), 'install-plugin_' . $plugin_slug );
	}

	/**
	 * Active Plugin Link function
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @param string $plugin_file Plugin file.
	 * @return string
	 */
	public function activate_plugin_link( $plugin_slug, $plugin_file ) {
		return wp_nonce_url( admin_url( 'edit.php?post_type=sp_wps_shortcodes&page=wps_help&action=activate&plugin=' . $plugin_slug . '/' . $plugin_file . '#recommended' ), 'activate-plugin_' . $plugin_slug . '/' . $plugin_file );
	}

	/**
	 * Making page as clean as possible
	 */
	public function disable_admin_notices() {

		global $wp_filter;

		if ( isset( $_GET['post_type'] ) && isset( $_GET['page'] ) && 'sp_wps_shortcodes' === wp_unslash( $_GET['post_type'] ) && in_array( wp_unslash( $_GET['page'] ), $this->pages ) ) { // @codingStandardsIgnoreLine

			if ( isset( $wp_filter['user_admin_notices'] ) ) {
				unset( $wp_filter['user_admin_notices'] );
			}
			if ( isset( $wp_filter['admin_notices'] ) ) {
				unset( $wp_filter['admin_notices'] );
			}
			if ( isset( $wp_filter['all_admin_notices'] ) ) {
				unset( $wp_filter['all_admin_notices'] );
			}

			// The purge above drops every notice, third-party ones included. Put the
			// rebrand announcement back: it is a one-off, site-wide dismissible bar
			// that has to reach people who land straight on this page. Its own
			// stylesheet and dismiss script are already enqueued by then, because
			// this runs on admin_print_scripts, after admin_enqueue_scripts.
			add_action( 'admin_notices', array( Rebrand_Notice::instance(), 'render_rebrand_notice' ) );
		}
	}

	/**
	 * The Reno Product Slider Help Callback.
	 *
	 * @return void
	 */
	public function help_page_callback() {
		add_thickbox();

		$action   = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		$plugin   = isset( $_GET['plugin'] ) ? sanitize_text_field( wp_unslash( $_GET['plugin'] ) ) : '';
		$_wpnonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( isset( $action, $plugin ) && ( 'activate' === $action ) && wp_verify_nonce( $_wpnonce, 'activate-plugin_' . $plugin ) && current_user_can( 'activate_plugins' ) ) {
			activate_plugin( $plugin, '', false, true );
		}

		if ( isset( $action, $plugin ) && ( 'deactivate' === $action ) && wp_verify_nonce( $_wpnonce, 'deactivate-plugin_' . $plugin ) && current_user_can( 'deactivate_plugins' ) ) {
			deactivate_plugins( $plugin, '', false );
		}

		$img         = SP_WPS_URL . 'Admin/HelpPage/img/';
		$review_url  = 'https://wordpress.org/support/plugin/woo-product-slider/reviews/';
		$support_url = 'https://shapedplugin.com/create-new-ticket/';
		?>
		<div class="sp-woo-product-slider-help">
			<div class="spwps-topbar">
				<div class="spwps-topbar__inner">
					<div class="spwps-topbar__start">
						<div class="spwps-topbar__brand">
							<img class="spwps-topbar__logo" src="<?php echo esc_url( $img . 'reno-product-slider-logo.svg' ); ?>" alt="<?php esc_attr_e( 'Reno Product Slider', 'woo-product-slider' ); ?>" width="122" height="24">
							<span class="spwps-topbar__version">
								<img src="<?php echo esc_url( $img . 'version-icon.svg' ); ?>" alt="" width="12" height="12">
								<?php echo esc_html( SP_WPS_VERSION ); ?>
							</span>
						</div>
						<nav class="spwps-header-nav-menu" aria-label="<?php esc_attr_e( 'Reno Product Slider help navigation', 'woo-product-slider' ); ?>">
							<ul>
								<li><a class="active" data-id="get-start-tab" href="<?php echo esc_url( $this->tab_url( 'get-start' ) ); ?>"><?php esc_html_e( 'Get Started', 'woo-product-slider' ); ?></a></li>
								<li class="spwps-header-nav-menu__separator" aria-hidden="true"></li>
								<li><a data-id="lite-to-pro-tab" href="<?php echo esc_url( $this->tab_url( 'lite-to-pro' ) ); ?>"><?php esc_html_e( 'Lite vs Pro', 'woo-product-slider' ); ?></a></li>
								<li><a data-id="recommended-tab" href="<?php echo esc_url( $this->tab_url( 'recommended' ) ); ?>"><i class="spwps-nav-icon spwps-nav-icon--plugins" aria-hidden="true"></i><?php esc_html_e( 'Our Plugins', 'woo-product-slider' ); ?></a></li>
								<li><a data-id="about-us-tab" href="<?php echo esc_url( $this->tab_url( 'about-us' ) ); ?>"><?php esc_html_e( 'About Us', 'woo-product-slider' ); ?></a></li>
							</ul>
						</nav>
					</div>
					<a class="spwps-topbar__support" target="_blank" href="<?php echo esc_url( $support_url ); ?>">
						<img src="<?php echo esc_url( $img . 'get-help-icon.svg' ); ?>" alt="" width="15" height="15">
						<?php esc_html_e( 'Get Help', 'woo-product-slider' ); ?>
					</a>
				</div>
			</div>
			<!-- Header section end -->

			<div class="spwps-help-content">
			<!-- Start Page -->
			<section class="spwps__help start-page" id="get-start-tab">
				<div class="spwps-start-page-wrap">
					<div class="spwps-welcome-card">
						<div class="spwps-welcome-card__intro">
							<h2 class="spwps-section-title-help"><?php esc_html_e( 'Welcome Reno Product Slider!', 'woo-product-slider' ); ?></h2>
							<p><?php esc_html_e( 'Thank you for installing Product Slider! This video will help you get started with the plugin. Enjoy!', 'woo-product-slider' ); ?></p>
						</div>
						<div class="spwps-welcome-card__actions">
							<a class="spwps-btn spwps-btn--primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=sp_wps_shortcodes' ) ); ?>">
								<img src="<?php echo esc_url( $img . 'plus-icon.svg' ); ?>" alt="" width="14" height="14">
								<?php esc_html_e( 'Create New Product Slider', 'woo-product-slider' ); ?>
							</a>
							<a class="spwps-btn spwps-btn--outline" target="_blank" href="https://demo.renoproductslider.com/lite-version-demo/"><?php esc_html_e( 'Live Demo', 'woo-product-slider' ); ?></a>
						</div>
						<div class="spwps-welcome-card__video">
							<button type="button" class="spwps-video-player" data-video-src="https://www.youtube.com/embed/lqe8SKiG_Ns?rel=0&amp;autoplay=1" data-video-title="<?php esc_attr_e( 'Getting started with Reno Product Slider', 'woo-product-slider' ); ?>">
								<img class="spwps-video-player__poster" src="<?php echo esc_url( $img . 'video-poster.jpg' ); ?>" alt="" width="804" height="452">
								<span class="spwps-video-player__play"></span>
								<span class="screen-reader-text"><?php esc_html_e( 'Play the Reno Product Slider getting started video', 'woo-product-slider' ); ?></span>
							</button>
						</div>
					</div>
					<div class="spwps-cta-cards">
						<?php
						$this->render_cta_card(
							$img . 'card-doc-icon.svg',
							__( 'Documentation', 'woo-product-slider' ),
							__( 'Explore clear, well-organized documentation to understand features, settings, and get the most out of the plugin.', 'woo-product-slider' ),
							__( 'Browse Now', 'woo-product-slider' ),
							'https://docs.renoproductslider.com/guide/getting-started/introduction'
						);
						$this->render_cta_card(
							$img . 'card-support-icon.svg',
							__( 'Technical Support', 'woo-product-slider' ),
							__( 'Need assistance? Reach out to our expert support team for fast, reliable help with any issues or questions.', 'woo-product-slider' ),
							__( 'Ask Now', 'woo-product-slider' ),
							$support_url
						);
						$this->render_cta_card(
							$img . 'card-community-icon.svg',
							__( 'Show Your Love', 'woo-product-slider' ),
							__( 'Join the official ShapedPlugin community to connect with other users, share ideas, and stay updated with the latest news.', 'woo-product-slider' ),
							__( 'Rate Us', 'woo-product-slider' ),
							$review_url
						);
						?>
					</div>
				</div>
			</section>

			<!-- Lite To Pro Page -->
			<section class="spwps__help lite-to-pro-page" id="lite-to-pro-tab">
				<div class="spwps-container">
					<div class="spwps-call-to-action-top">
						<h2 class="spwps-section-title-help">Lite vs Pro Comparison</h2>
						<a target="_blank" href="https://renoproductslider.com/pricing/?ref=1" class='spwps-big-btn'>Upgrade to Pro Now!</a>
					</div>
					<div class="spwps-lite-to-pro-wrap">
						<div class="spwps-features">
							<ul>
								<li class='spwps-header'>
									<span class='spwps-title'>FEATURES</span>
									<span class='spwps-free'>Lite</span>
									<span class='spwps-pro'><i class='spwps-icon-pro'></i> PRO</span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>All Free Version Features</span>
									<span class='spwps-free spwps-check-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Beautiful Layout Presets (Slider, Ticker, Multi-row, Grid, Live Filter, Masonry, List, and Table)</span>
									<span class='spwps-free'><b>2</b></span>
									<span class='spwps-pro'><b>8</b></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Pre-designed Professional Templates</span>
									<span class='spwps-free'><b>3</b></span>
									<span class='spwps-pro'><b>28+</b></span>
								</li>
																<li class='spwps-body'>
									<span class='spwps-title'>Frontend Live Filter By Product types (New Arrivals, Featured, Best Selling, Top Rated, and On Sale)<i class="spwps-new">New</i> <i class="spwps-hot">Hot</i></span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Frontend Live Filter By Product Taxonomies (Categories, Tags, and Brands) <i class="spwps-new">New</i><i class="spwps-hot">Hot</i></span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Create Product Custom Card/Template <i class="spwps-new">New</i> <i class="spwps-hot">Hot</i></span>
									<span class='spwps-free'><b>1</b></span>
									<span class='spwps-pro'><b>5+</b></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Advanced Product Filtering Types (Category, Tag, Brands, Multiple Taxonomies, Best Selling, Related, Top Rated, Upsells, Cross-sells, etc.)</span>
									<span class='spwps-free'>2</span>
									<span class='spwps-pro'>17+</span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Show Products from a Specific Data Type (Simple, Group, External/Affiliate, and Variable)</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Show Variation as Individual Product <i class="spwps-new">New</i></span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Show On Sale, Free, and Hidden Products, Hide Out of Stock Products</span>
									<span class='spwps-free spwps-check-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Hide Product without Thumbnail from the Product Sliders</span>
									<span class='spwps-free spwps-check-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Order Product by Price and Drag and Drop</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Product Content Position (Bottom, Top, Right, Left, Overlay) <i class="spwps-new">New</i></span>
									<span class='spwps-free'><b>1</b></span>
									<span class='spwps-pro'><b>5</b></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Product Item Border, Radius, BoxShadow, Background, Inner Padding, etc.</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Configure Overlay Content Position, Visibility, Color Type, etc.</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Ajax Product Search and Equalize Product Height</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Multiple Ajax Paginations (Number, Load More, Infinite) and Show Per Page /Click</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Product Name Length Limit Type (Word, Character, Line)</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Show/Hide Product Description (Full, Short, Limit, Read More, etc.)</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Show Hide Product Category, Review Count, Quantities, Wishlist, and Compare</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Show Product Price, Rating, Add To Cart, Brands, Quick View</span>
									<span class='spwps-free spwps-check-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Strip All HTML Tags from the Description Content</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Product Image Custom Dimensions and Retina Ready Supported</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Enable Product Image Flipping and Lazy Load</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Powerful Lightbox Options for Product Image</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Zoom In, Zoom Out, and Grayscale Modes for Product Images</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Vertical Carousel Orientation, Fade effect, Slide to Scroll, Multi-row Carousel</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Powerful Carousel Settings (AutoPlay, AutoPlay Delay, Direction, Navigation, Pagination, etc.)</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Upsells and Crosssells Products Slider to Boost sales <i class="spwps-hot">Hot</i></span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Create Engaging Related Product Slider <i class="spwps-hot">Hot</i></span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Replace the Shop/Product, Category, Tag, and Search Pages default layout with the Customized Product Sliders/Grids/Tables</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Template Modification from the Theme Directory</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Export or Import Product Sliders/Grids/Tables</span>
									<span class='spwps-free spwps-check-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Stylize your Product Slider/Grid Typography with 1500+ Google Fonts</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>All Premium Features, Security Enhancements, and Compatibility</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
								<li class='spwps-body'>
									<span class='spwps-title'>Priority Top-notch Support</span>
									<span class='spwps-free spwps-close-icon'></span>
									<span class='spwps-pro spwps-check-icon'></span>
								</li>
							</ul>
						</div>
						<div class="spwps-upgrade-to-pro">
							<h2 class='spwps-section-title-help'>Upgrade To PRO & Enjoy Advanced Features!</h2>
							<span class='spwps-section-subtitle'>Already, <b>30,000+</b> people are using Reno Product Slider on their websites to create beautiful showcase, why won’t you!</span>
							<div class="spwps-upgrade-to-pro-btn">
								<div class="spwps-action-btn">
									<a target="_blank" href="https://renoproductslider.com/pricing/?ref=1" class='spwps-big-btn'>Upgrade to Pro Now!</a>
									<span class='spwps-small-paragraph'>14-Day No-Questions-Asked <a target="_blank" href="https://shapedplugin.com/refund-policy/">Refund Policy</a></span>
								</div>
								<a target="_blank" href="https://renoproductslider.com/" class='spwps-big-btn-border'>See All Features</a>
								<a target="_blank" href="https://demo.renoproductslider.com/products-carousel-slider/" class='spwps-big-btn-border spwps-live-pro-demo'>Pro Live Demo</a>
							</div>
						</div>
					</div>
					<div class="spwps-testimonial">
						<div class="spwps-testimonial-title-section">
							<span class='spwps-testimonial-subtitle'>NO NEED TO TAKE OUR WORD FOR IT</span>
							<h2 class="spwps-section-title-help">Our Users Love Reno Product Slider Pro!</h2>
						</div>
						<div class="spwps-testimonial-wrap">
							<div class="spwps-testimonial-area">
								<div class="spwps-testimonial-content">
									<p>Self explaining, easy to understand, well organized: all thumbs up for this wonderful plugin. Treat yourself with the pro version, it makes life easier – at a reasonable cost. Not to be underestimat...</p>
								</div>
								<div class="spwps-testimonial-info">
									<div class="spwps-img">
										<img src="<?php echo esc_url( SP_WPS_URL . 'Admin/HelpPage/img/stefan.png' ); ?>" alt="">
									</div>
									<div class="spwps-info">
										<h3>Stefan</h3>
										<div class="spwps-star">
											<i>★★★★★</i>
										</div>
									</div>
								</div>
							</div>
							<div class="spwps-testimonial-area">
								<div class="spwps-testimonial-content">
									<p>We purchased the pro version because we needed the extra features and it’s wonderful. So many creative options! Needed support at the outset over what turned out to be a plugin conflict, and su...</p>
								</div>
								<div class="spwps-testimonial-info">
									<div class="spwps-img">
										<img src="<?php echo esc_url( SP_WPS_URL . 'Admin/HelpPage/img/global.png' ); ?>" alt="">
									</div>
									<div class="spwps-info">
										<h3>Global Exposures</h3>
										<div class="spwps-star">
											<i>★★★★★</i>
										</div>
									</div>
								</div>
							</div>
							<div class="spwps-testimonial-area">
								<div class="spwps-testimonial-content">
									<p>Very happy with the pro version of WooCommerce Slider Pro and with the prompt response from the support team. Highly recommended. The definition of excellent independent software.</p>
								</div>
								<div class="spwps-testimonial-info">
									<div class="spwps-img">
										<img src="<?php echo esc_url( SP_WPS_URL . 'Admin/HelpPage/img/patboran.png' ); ?>" alt="">
									</div>
									<div class="spwps-info">
										<h3>Patboran</h3>
										<div class="spwps-star">
											<i>★★★★★</i>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>

			<!-- Recommended Page -->
			<section id="recommended-tab" class="spwps-recommended-page">
				<div class="spwps-container">
					<h2 class="spwps-section-title-help">Enhance your Website with our Free Robust Plugins</h2>
					<div class="spwps-wp-list-table plugin-install-php">
						<div class="spwps-recommended-plugins" id="the-list">
							<?php
								$this->spwps_plugins_info_api_help_page();
							?>
						</div>
					</div>
				</div>
			</section>

			<!-- About Page -->
			<section id="about-us-tab" class="spwps__help about-page">
				<div class="spwps-container">
					<div class="spwps-about-box">
						<div class="spwps-about-info">
							<h3>The Best WooCommerce Product Slider plugin by ShapedPlugin Team</h3>
							<p>At <b>ShapedPlugin LLC</b>, we are committed to helping WooCommerce store owners increase their sales with the help of various easy sales booster plugins. However, we understand the importance of highlighting specific products in strategic positions of the shop to make it easier for customers to find them.</p>
							<p>Our plugin provides a simple and convenient solution to display unlimited, visually captivating product sliders, grids, masonry, and tables for WooCommerce shops. We're confident you'll find it a game-changer!</p>
							<div class="spwps-about-btn">
								<a target="_blank" href="https://renoproductslider.com/" class='spwps-medium-btn'>Explore Reno Product Slider</a>
								<a target="_blank" href="https://shapedplugin.com/about-us/" class='spwps-medium-btn spwps-arrow-btn'>More About Us <i class="spwps-icon-button-arrow-icon"></i></a>
							</div>
						</div>
						<div class="spwps-about-img">
							<img src="<?php echo esc_url( SP_WPS_URL . 'Admin/HelpPage/img/shapedplugin-team.jpg' ); ?>" alt="ShapedPlugin Team">
							<span>Team ShapedPlugin LLC at WordCamp Sylhet</span>
						</div>
					</div>
					<?php
					$plugins_arr = get_transient( 'spwps_plugins' );
					$plugin_icon = array();
					if ( is_array( $plugins_arr ) && ( count( $plugins_arr ) > 0 ) ) {
						foreach ( $plugins_arr as $plugin ) {
							$plugin_icon[ $plugin['slug'] ] = $plugin['icons'];
						}
					}

					// Smart Post ships an animated icon that the query_plugins payload
					// has been seen to omit; fall back to the WordPress.org asset
					// (unpinned, so it tracks the current revision) rather than
					// hardcoding the entry out of the transient-backed lookup.
					$smart_post_icon = isset( $plugin_icon['post-carousel'] )
						? $plugin_icon['post-carousel']
						: '';

					if ( isset( $plugin_icon['wp-expand-tabs-free'] ) ) :
						?>
					<div class="spwps-our-plugin-list">
						<h3 class="spwps-section-title-help">Upgrade your Website with our High-quality Plugins!</h3>
						<div class="spwps-our-plugin-list-wrap">
							<a target="_blank" class="spwps-our-plugin-list-box" href="https://wpcarousel.io/?ref=1">
								<i class="spwps-icon-button-arrow-icon"></i>
								<img src="<?php echo esc_url( $plugin_icon['wp-carousel-free'] ); ?>" alt="WP Carousel">
								<h4>WP Carousel</h4>
								<p>The most powerful and user-friendly multi-purpose carousel, slider, & gallery plugin for WordPress.</p>
							</a>
							<a target="_blank" class="spwps-our-plugin-list-box" href="https://realtestimonials.io/?ref=1">
								<i class="spwps-icon-button-arrow-icon"></i>
								<img src="<?php echo esc_url( $plugin_icon['testimonial-free'] ); ?>" alt="Real Testimonials">
								<h4>Real Testimonials</h4>
								<p>Simply collect, manage, and display Testimonials on your website and boost conversions.</p>
							</a>
							<a target="_blank" class="spwps-our-plugin-list-box" href="https://smartpostshow.com/?ref=1">
								<i class="spwps-icon-button-arrow-icon"></i>
								<img src="<?php echo esc_url( $smart_post_icon ); ?>" alt="Smart Post">
								<h4>Smart Post</h4>
								<p>Filter and display posts (any post types), pages, taxonomy, custom taxonomy, and custom field, in beautiful layouts.</p>
							</a>
							<a target="_blank" class="spwps-our-plugin-list-box" href="https://renoproductgallery.com/?ref=1">
								<i class="spwps-icon-button-arrow-icon"></i>
								<img src="<?php echo esc_url( $plugin_icon['gallery-slider-for-woocommerce'] ); ?>" alt="Reno Product Gallery">
								<h4>Reno Product Gallery</h4>
								<p>Product gallery slider and additional variation images gallery for WooCommerce and boost your sales.</p>
							</a>
							<a target="_blank" class="spwps-our-plugin-list-box" href="https://getwpteam.com/?ref=1">
								<i class="spwps-icon-button-arrow-icon"></i>
								<img src="<?php echo esc_url( $plugin_icon['team-free'] ); ?>" alt="Smart Team">
								<h4>Smart Team</h4>
								<p>Display your team members smartly who are at the heart of your company or organization!</p>
							</a>
							<a target="_blank" class="spwps-our-plugin-list-box" href="https://logocarousel.com/?ref=1">
								<i class="spwps-icon-button-arrow-icon"></i>
								<img src="<?php echo esc_url( $plugin_icon['logo-carousel-free'] ); ?>" alt="Logo Carousel">
								<h4>Logo Carousel</h4>
								<p>Showcase a group of logo images with Title, Description, Tooltips, Links, and Popup as a grid or in a carousel.</p>
							</a>
							<a target="_blank" class="spwps-our-plugin-list-box" href="https://easyaccordion.io/?ref=1">
								<i class="spwps-icon-button-arrow-icon"></i>
								<img src="<?php echo esc_url( $plugin_icon['easy-accordion-free'] ); ?>" alt="Easy Accordion">
								<h4>Easy Accordion</h4>
								<p>Minimize customer support by offering comprehensive FAQs and increasing conversions.</p>
							</a>
							<a target="_blank" class="spwps-our-plugin-list-box" href="https://shapedplugin.com/reno-product-category/?ref=1">
								<i class="spwps-icon-button-arrow-icon"></i>
								<img src="<?php echo esc_url( $plugin_icon['woo-category-slider-grid'] ); ?>" alt="Reno Product Category">
								<h4>Reno Product Category</h4>
								<p>Display by filtering the list of categories aesthetically and boosting sales.</p>
							</a>
							<a target="_blank" class="spwps-our-plugin-list-box" href="https://wptabs.com/?ref=1">
								<i class="spwps-icon-button-arrow-icon"></i>
								<img src="<?php echo esc_url( $plugin_icon['wp-expand-tabs-free'] ); ?>" alt="Smart Tabs">
								<h4>Smart Tabs</h4>
								<p>Display tabbed content smartly & quickly on your WordPress site without coding skills.</p>
							</a>
							<a target="_blank" class="spwps-our-plugin-list-box" href="https://shapedplugin.com/reno-quick-view/?ref=1">
								<i class="spwps-icon-button-arrow-icon"></i>
								<img src="<?php echo esc_url( $plugin_icon['woo-quickview'] ); ?>" alt="Reno Quick View">
								<h4>Reno Quick View</h4>
								<p>Quickly view product information with smooth animation via AJAX in a nice Modal without opening the product page.</p>
							</a>
							<a target="_blank" class="spwps-our-plugin-list-box" href="https://shapedplugin.com/smart-brands/?ref=1">
								<i class="spwps-icon-button-arrow-icon"></i>
								<img src="<?php echo esc_url( $plugin_icon['smart-brands-for-woocommerce'] ); ?>" alt="Smart Brands for WooCommerce">
								<h4>Smart Brands for WooCommerce</h4>
								<p>Smart Brands for WooCommerce Pro helps you display product brands in an attractive way on your online store.</p>
							</a>
							<a target="_blank" class="spwps-our-plugin-list-box" href="https://shapedplugin.com/smart-swatches-for-woocommerce/?ref=1">
								<i class="spwps-icon-button-arrow-icon"></i>
								<img src="<?php echo esc_url( $plugin_icon['smart-swatches'] ); ?>" alt="Smart Swatches for WooCommerce">
								<h4>Smart Swatches for WooCommerce</h4>
								<p>Appealing color, image, and button variation swatches on your WooCommerce Shop and Product pages in minutes to increase sales.</p>
							</a>
						</div>
					</div>
					<?php endif; ?>
				</div>
			</section>
			</div><!-- /.spwps-help-content -->

			<!-- Footer Section -->
			<footer class="spwps-footer">
				<div class="spwps-footer__inner">
					<div class="spwps-footer__credit">
						<span><?php esc_html_e( 'Made with', 'woo-product-slider' ); ?></span>
						<img src="<?php echo esc_url( $img . 'footer-heart.svg' ); ?>" alt="" width="16" height="16">
						<span><?php esc_html_e( 'by the', 'woo-product-slider' ); ?></span>
						<a target="_blank" href="https://shapedplugin.com/about-us/"><?php esc_html_e( 'ShapedPlugin LLC Team', 'woo-product-slider' ); ?></a>
					</div>
					<div class="spwps-footer__social">
						<span><?php esc_html_e( 'Get Connected with', 'woo-product-slider' ); ?></span>
						<ul>
							<li><a target="_blank" href="https://www.linkedin.com/company/shapedplugin/" aria-label="<?php esc_attr_e( 'LinkedIn', 'woo-product-slider' ); ?>"><i class="spwps-icon-linkedin"></i></a></li>
							<li><a target="_blank" href="https://twitter.com/intent/follow?screen_name=ShapedPlugin" aria-label="<?php esc_attr_e( 'X (Twitter)', 'woo-product-slider' ); ?>"><i class="spwps-icon-x"></i></a></li>
							<li><a target="_blank" href="https://profiles.wordpress.org/shapedplugin/#content-plugins" aria-label="<?php esc_attr_e( 'WordPress.org', 'woo-product-slider' ); ?>"><i class="spwps-icon-wp-icon"></i></a></li>
							<li><a target="_blank" href="https://www.facebook.com/ShapedPlugin/" aria-label="<?php esc_attr_e( 'Facebook', 'woo-product-slider' ); ?>"><i class="spwps-icon-fb"></i></a></li>
							<li><a target="_blank" href="https://youtube.com/@ShapedPlugin?sub_confirmation=1" aria-label="<?php esc_attr_e( 'YouTube', 'woo-product-slider' ); ?>"><i class="spwps-icon-youtube-play"></i></a></li>
						</ul>
					</div>
					<div class="spwps-footer__rating">
						<span>
							<?php
							printf(
								/* translators: %s: plugin name. */
								esc_html__( 'Enjoying %s?', 'woo-product-slider' ),
								'<strong>' . esc_html__( 'Reno Product Slider', 'woo-product-slider' ) . '</strong>'
							);
							?>
							<a target="_blank" href="<?php echo esc_url( $review_url ); ?>"><?php esc_html_e( 'Rate us!', 'woo-product-slider' ); ?></a>
						</span>
						<a class="spwps-footer__stars" target="_blank" href="<?php echo esc_url( $review_url ); ?>" aria-label="<?php esc_attr_e( 'Rate Reno Product Slider five stars on WordPress.org', 'woo-product-slider' ); ?>">
							<img src="<?php echo esc_url( $img . 'footer-rating-stars.svg' ); ?>" alt="" width="74" height="14">
						</a>
					</div>
				</div>
			</footer>
		</div>
		<?php
	}

	/**
	 * Render a help page CTA card.
	 *
	 * @param string $icon        Absolute URL of the card icon.
	 * @param string $title       Card title.
	 * @param string $description Card description.
	 * @param string $button_text Button label.
	 * @param string $button_url  Button URL.
	 * @return void
	 */
	private function render_cta_card( $icon, $title, $description, $button_text, $button_url ) {
		?>
		<div class="spwps-cta-card">
			<div class="spwps-cta-card__body">
				<div class="spwps-cta-card__title">
					<img src="<?php echo esc_url( $icon ); ?>" alt="" width="24" height="24">
					<h3><?php echo esc_html( $title ); ?></h3>
				</div>
				<p><?php echo esc_html( $description ); ?></p>
			</div>
			<a class="spwps-btn spwps-btn--ghost" target="_blank" href="<?php echo esc_url( $button_url ); ?>"><?php echo esc_html( $button_text ); ?></a>
		</div>
		<?php
	}
}
