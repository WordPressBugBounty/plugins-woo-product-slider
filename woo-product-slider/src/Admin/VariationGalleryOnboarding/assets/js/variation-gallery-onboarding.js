/**
 * Variation Gallery Onboarding.
 *
 * Flow:
 *  1. User clicks "Add Gallery Images" on a variation → onboarding modal.
 *  2. "Create a Gallery" installs + activates WooGallery from WordPress.org.
 *  3. The clicked variation ID is stored in sessionStorage and the product edit
 *     page is reloaded so WooGallery's own scripts/handlers load normally.
 *  4. After reload (WooGallery now active) the handoff opens the Variations tab,
 *     waits for WooGallery to render + bind its variation gallery UI, then
 *     triggers a native click on WooGallery's "Add Gallery Images" button for
 *     the stored variation, and clears the flag.
 *
 * sessionStorage (not localStorage) is used deliberately: the flag must survive
 * exactly one reload of this tab and never leak to other tabs or persist beyond
 * the session.
 *
 * @package ShapedPlugin\VariationGalleryOnboarding
 */
( function ( $ ) {
	'use strict';

	var settings = window.spVGO || {};
	var i18n = settings.i18n || {};
	var actions = settings.actions || {};
	var isBusy = false;
	var lastFocused = null;

	var STORAGE_KEY = 'spVgoPendingVariation';
	var STORAGE_MAX_AGE = 5 * 60 * 1000; // 5 minutes.

	/* -- sessionStorage helpers -------------------------------------------- */

	/**
	 * Persist the pending variation ID (survives the upcoming reload).
	 *
	 * @param {string} variationId Variation post ID (may be empty).
	 */
	function setPending( variationId ) {
		try {
			window.sessionStorage.setItem(
				STORAGE_KEY,
				JSON.stringify( { id: String( variationId || '' ), ts: Date.now() } )
			);
		} catch ( e ) {} // eslint-disable-line no-empty
	}

	/**
	 * Read the pending flag, discarding it if stale.
	 *
	 * @return {Object|null} { id } or null.
	 */
	function getPending() {
		try {
			var raw = window.sessionStorage.getItem( STORAGE_KEY );
			if ( ! raw ) {
				return null;
			}
			var data = JSON.parse( raw );
			if ( ! data || ( data.ts && Date.now() - data.ts > STORAGE_MAX_AGE ) ) {
				clearPending();
				return null;
			}
			return data;
		} catch ( e ) {
			return null;
		}
	}

	/**
	 * Remove the pending flag.
	 */
	function clearPending() {
		try {
			window.sessionStorage.removeItem( STORAGE_KEY );
		} catch ( e ) {} // eslint-disable-line no-empty
	}

	/* -- Modal ------------------------------------------------------------- */

	/**
	 * Cache the modal element.
	 *
	 * @return {jQuery} Modal wrapper.
	 */
	function $modal() {
		return $( '#sp-vgo-modal' );
	}

	/**
	 * Focusable, non-decorative controls inside the modal (for the focus trap).
	 *
	 * @return {jQuery} Visible focusable elements, excluding the decorative
	 *                  (aria-hidden) preview subtree.
	 */
	function modalFocusables() {
		return $modal()
			.find( 'button, a[href], input, select, textarea, [tabindex]:not([tabindex="-1"])' )
			.filter( ':visible' )
			.filter( function () {
				return ! $( this ).closest( '[aria-hidden="true"]' ).length;
			} );
	}

	/**
	 * Show the onboarding modal and move focus into it.
	 */
	function openModal() {
		resetStatus();
		lastFocused = document.activeElement;
		$modal().prop( 'hidden', false ).addClass( 'is-open' );
		$( 'body' ).addClass( 'sp-vgo-modal-open' );
		modalFocusables().first().trigger( 'focus' );
	}

	/**
	 * Hide the onboarding modal and restore focus to the opener.
	 */
	function closeModal() {
		$modal().prop( 'hidden', true ).removeClass( 'is-open' );
		$( 'body' ).removeClass( 'sp-vgo-modal-open' );
		if ( lastFocused && typeof lastFocused.focus === 'function' ) {
			lastFocused.focus();
		}
		lastFocused = null;
	}

	/**
	 * Whether the modal is currently open.
	 *
	 * @return {boolean} True when visible.
	 */
	function isModalOpen() {
		return ! $modal().prop( 'hidden' );
	}

	/**
	 * Clear and hide the error area.
	 */
	function resetStatus() {
		$modal()
			.find( '.sp-vgo-status' )
			.prop( 'hidden', true )
			.find( '.sp-vgo-status-text' )
			.text( '' );
	}

	/**
	 * Show an error message. The status area is error-only; the loading state is
	 * conveyed entirely by the in-button spinner.
	 *
	 * @param {string} message Text to show.
	 */
	function showError( message ) {
		$modal()
			.find( '.sp-vgo-status' )
			.prop( 'hidden', false )
			.find( '.sp-vgo-status-text' )
			.text( message );
	}

	/**
	 * Toggle the busy state of the primary button.
	 *
	 * Disables the button (blocks duplicate clicks), shows the in-button spinner,
	 * and swaps its label to the "Preparing…" text while the install/activate
	 * flow runs. The default label is captured from the DOM so it stays the
	 * single source of truth and is restored on failure. All progress is conveyed
	 * by this single loading state — no separate status text.
	 *
	 * @param {boolean} busy Whether a request is in flight.
	 */
	function setBusy( busy ) {
		isBusy = busy;
		var $button = $modal().find( '.sp-vgo-create-gallery' );
		$button.prop( 'disabled', busy ).toggleClass( 'is-loading', busy );

		var $label = $button.find( '.sp-vgo-btn-label' );
		if ( busy ) {
			if ( undefined === $label.data( 'defaultLabel' ) ) {
				$label.data( 'defaultLabel', $label.text() );
			}
			if ( i18n.preparing ) {
				$label.text( i18n.preparing );
			}
		} else {
			var original = $label.data( 'defaultLabel' );
			if ( undefined !== original ) {
				$label.text( original );
			}
		}
	}

	/* -- Install / activate ------------------------------------------------ */

	/**
	 * Run one server step (install or activate).
	 *
	 * Emits no progress text — the in-button spinner covers the whole flow.
	 * Rejects with a human-readable message so the caller can surface errors.
	 *
	 * @param {string} action AJAX action name.
	 * @return {jQuery.Deferred} Resolves on success, rejects with a message.
	 */
	function runStep( action ) {
		return $.post( settings.ajaxUrl, {
			action: action,
			nonce: settings.nonce,
		} ).then(
			function ( response ) {
				if ( ! response || ! response.success ) {
					var message = response && response.data && response.data.message
						? response.data.message
						: ( i18n.genericError || 'Something went wrong.' );
					return $.Deferred().reject( message ).promise();
				}
				return response;
			},
			function () {
				return $.Deferred().reject( i18n.networkError || 'Network error.' ).promise();
			}
		);
	}

	/**
	 * Kick off the install → activate → (persist + reload) flow.
	 *
	 * The media library is intentionally NOT opened here. On this page
	 * WooGallery's scripts are not yet loaded, so opening media now would not be
	 * wired to WooGallery. Instead we persist the variation ID and reload; the
	 * handoff below resumes after WooGallery has fully initialised.
	 *
	 * @param {Event} event Click event.
	 */
	function createGallery( event ) {
		event.preventDefault();

		if ( isBusy ) {
			return;
		}
		resetStatus();
		setBusy( true );

		// Skip the install step when WooGallery is already installed.
		var chain = settings.isInstalled
			? $.Deferred().resolve().promise()
			: runStep( actions.install );

		chain
			.then( function () {
				return runStep( actions.activate );
			} )
			.then( function () {
				var variationId = $modal().data( 'variationId' ) || '';
				setPending( variationId );
				// Stay in the loading state; the reload resumes the flow. No
				// success text — integration continues seamlessly.
				window.location.reload();
			} )
			.fail( function ( message ) {
				showError( message );
				setBusy( false );
			} );
	}

	/* -- Post-reload handoff to WooGallery --------------------------------- */

	/**
	 * Find WooGallery's "Add Gallery Images" button for a variation.
	 *
	 * @param {string} variationId Stored variation post ID.
	 * @return {jQuery} The button (possibly empty).
	 */
	function locateWooGalleryButton( variationId ) {
		if ( variationId ) {
			var byId = document.getElementById( 'wcgs-upload-' + variationId );
			if ( byId ) {
				return $( byId );
			}

			var $variation = $( 'input.variable_post_id' )
				.filter( function () {
					return String( $( this ).val() ) === String( variationId );
				} )
				.closest( '.woocommerce_variation' );

			var $scoped = $variation.find( '.wcgs-upload-image' ).first();
			if ( $scoped.length ) {
				return $scoped;
			}
		}

		// Fallback: the first available WooGallery upload button.
		return $( '.wcgs-upload-image' ).first();
	}

	/**
	 * Expand the variation panel and fire a SINGLE click on WooGallery's button.
	 *
	 * WooGallery binds its handler via jQuery delegation
	 * (`$(document).on('click', '.wcgs-variation-gallery .wcgs-upload-image', …)`)
	 * and, on every click, builds and opens a brand-new `wp.media` frame with no
	 * reuse guard. Dispatching the click twice (native `el.click()` AND
	 * `$.trigger('click')`) therefore opens TWO stacked media frames: the user
	 * completes the top one, then the duplicate — still open underneath —
	 * resurfaces the moment it closes, which looks like "Insert Gallery did
	 * nothing and the onboarding modal reappeared". A native click already
	 * bubbles to document and is caught by jQuery's delegated listener, so one
	 * dispatch is both necessary and sufficient.
	 *
	 * @param {jQuery} $button WooGallery upload button.
	 */
	function fireWooGalleryClick( $button ) {
		var $variation = $button.closest( '.woocommerce_variation' );
		if ( $variation.length ) {
			$variation.removeClass( 'closed' );
			$variation.children( '.woocommerce_variable_attributes, .wc-metabox-content' ).show();
		}

		var el = $button.get( 0 );
		if ( el && typeof el.click === 'function' ) {
			el.click(); // Single native dispatch; jQuery delegation catches it too.
		} else {
			$button.trigger( 'click' );
		}
	}

	/**
	 * Resume the flow after reload: wait for WooGallery's variation UI, then
	 * click its upload button for the stored variation.
	 */
	function runHandoff() {
		var pending = getPending();

		// Only meaningful once WooGallery is active.
		if ( ! settings.wooGalleryActive ) {
			if ( pending ) {
				clearPending(); // Stale flag (activation never completed).
			}
			return;
		}
		if ( ! pending ) {
			return;
		}

		var maxTries = 40; // ~12s at 300ms between polls.
		var tries = 0;
		var done = false;
		var polling = false;
		var $data = $( '#woocommerce-product-data' );

		// A single polling chain (guarded by `polling`) so the immediate attempt
		// and the variations-loaded handler can't run two parallel loops that
		// would drain the retry budget twice as fast.
		function poll() {
			if ( done ) {
				polling = false;
				return;
			}
			var $button = locateWooGalleryButton( pending.id );
			if ( $button.length ) {
				done = true;
				polling = false;
				clearPending();
				$data.off( 'woocommerce_variations_loaded.spvgo' );
				// Give WooGallery a tick to finish binding its click handler.
				window.setTimeout( function () {
					fireWooGalleryClick( $button );
				}, 200 );
				return;
			}
			if ( ++tries >= maxTries ) {
				polling = false;
				clearPending();
				return;
			}
			window.setTimeout( poll, 300 );
		}

		function startPolling() {
			if ( polling || done ) {
				return;
			}
			polling = true;
			poll();
		}

		// Opening the Variations tab triggers WooCommerce's
		// `woocommerce_load_variations`, which renders WooGallery's block and
		// fires `woocommerce_variations_loaded`.
		var $tab = $( 'li.variations_tab a' ).first();
		if ( $tab.length ) {
			$tab.trigger( 'click' );
		}

		// When the panel actually renders, restart with a fresh retry budget —
		// the immediate poll below runs before the async load and may burn tries.
		$data.on( 'woocommerce_variations_loaded.spvgo', function () {
			if ( done ) {
				return;
			}
			tries = 0;
			window.setTimeout( startPolling, 150 );
		} );

		// Fallback in case the variations were already rendered.
		startPolling();
	}

	/* -- Button repositioning (onboarding UI) ------------------------------ */

	/**
	 * Move each onboarding block to right after the variation image row.
	 *
	 * WooCommerce exposes no PHP hook between the variation image and the
	 * Enabled/Downloadable/… options, so the block is rendered on
	 * `woocommerce_product_after_variable_attributes` (panel bottom) and lifted
	 * into place here. Runs while variation panels are still collapsed, so the
	 * move is not visible. Idempotent — safe to call on every WC event.
	 */
	function positionBlocks() {
		$( '.sp-vgo-variation-gallery' ).each( function () {
			var $block = $( this );
			var $variation = $block.closest( '.woocommerce_variation, .woocommerce_variable_attributes' );

			if ( ! $variation.length ) {
				return;
			}

			// Prefer the image + SKU flex row; fall back to the image paragraph.
			var $anchor = $variation.find( '.form-flex-box' ).first();
			if ( ! $anchor.length ) {
				$anchor = $variation.find( '.upload_image' ).first();
			}

			if ( $anchor.length && ! $anchor.next().is( $block ) ) {
				$block.insertAfter( $anchor );
			}
		} );
	}

	$( function () {
		// Resume a pending install after reload (WooGallery active branch).
		runHandoff();

		positionBlocks();

		// Reposition whenever WooCommerce (re)renders variation panels.
		$( '#woocommerce-product-data' ).on(
			'woocommerce_variations_loaded woocommerce_variations_added',
			function () {
				positionBlocks();
			}
		);

		$( document ).on( 'click', '.sp-vgo-open-modal', function ( event ) {
			event.preventDefault();
			// Capture which variation this button belongs to.
			var variationId = $( this )
				.closest( '.woocommerce_variation' )
				.find( '.variable_post_id' )
				.val() || '';
			$modal().data( 'variationId', variationId );
			openModal();
		} );

		$( document ).on( 'click', '.sp-vgo-modal-close, .sp-vgo-modal-backdrop', function () {
			if ( ! isBusy ) {
				closeModal();
			}
		} );

		$( document ).on( 'keyup', function ( event ) {
			if ( 27 === event.keyCode && isModalOpen() && ! isBusy ) {
				closeModal();
			}
		} );

		// Trap Tab within the open dialog (basic focus containment for a11y).
		$( document ).on( 'keydown', function ( event ) {
			if ( 9 !== event.keyCode || ! isModalOpen() ) {
				return;
			}
			var $f = modalFocusables();
			if ( ! $f.length ) {
				return;
			}
			var first = $f.get( 0 );
			var last = $f.get( $f.length - 1 );
			var active = document.activeElement;
			if ( event.shiftKey && active === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && active === last ) {
				event.preventDefault();
				first.focus();
			} else if ( -1 === $f.index( active ) ) {
				event.preventDefault();
				first.focus();
			}
		} );

		$( document ).on( 'click', '.sp-vgo-create-gallery', createGallery );
	} );
}( jQuery ) );
