; (function ($) {
	'use strict';

	var $help = $('.sp-woo-product-slider-help');

	if (!$help.length) {
		return;
	}

	var $adminMenu = $('#menu-posts-sp_wps_shortcodes');

	// Hash (URL) to tab section id. Single source of truth for both directions.
	var tabs = {
		'get-start': 'get-start-tab',
		'lite-to-pro': 'lite-to-pro-tab',
		'recommended': 'recommended-tab',
		'about-us': 'about-us-tab'
	};

	// Tabs that own a dedicated admin submenu item. Everything else belongs to "Get Started".
	var adminMenuItem = {
		'lite-to-pro-tab': 'spwps-menu-lite-vs-pro'
	};

	function highlightAdminMenu(tabId) {
		var itemClass = adminMenuItem[tabId] || 'spwps-menu-get-started';

		$adminMenu.find('ul li').removeClass('current');
		$adminMenu.find('ul li.' + itemClass).addClass('current');
	}

	function activateTab(tabId) {
		var $section = $('#' + tabId);

		if (!tabId || !$section.length) {
			return;
		}

		$help.find('.spwps-header-nav-menu a').each(function () {
			var $link = $(this);

			$link.removeClass('active');
			$('#' + $link.attr('data-id')).hide();
		});

		$help.find('.spwps-header-nav-menu a[data-id="' + tabId + '"]').addClass('active');
		$section.show();
		highlightAdminMenu(tabId);
	}

	function activateFromHash() {
		var hash = window.location.hash.replace('#', '');

		if (tabs[hash]) {
			activateTab(tabs[hash]);
		}
	}

	// Help page tab menu script.
	$help.on('click', '.spwps-header-nav-menu a', function () {
		activateTab($(this).attr('data-id'));
	});

	// Keep the admin submenu links in sync without a full page reload.
	$adminMenu.on('click', 'ul li a', function (e) {
		var href = $(this).attr('href') || '';
		var hash = href.split('#')[1] || 'get-start';

		if (href.indexOf('page=wps_help') === -1 || !tabs[hash]) {
			return;
		}

		e.preventDefault();
		window.location.hash = hash;
		activateTab(tabs[hash]);
	});

	$(window).on('hashchange', activateFromHash);
	activateFromHash();

	// Load the intro video only after the poster is clicked.
	$help.on('click', '.spwps-video-player', function () {
		var $trigger = $(this);
		var src = $trigger.attr('data-video-src');

		if (!src) {
			return;
		}

		$trigger.replaceWith(
			$('<iframe />', {
				src: src,
				title: $trigger.attr('data-video-title') || '',
				frameborder: 0,
				allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
				allowfullscreen: 'allowfullscreen'
			})
		);
	});

	$('body').on('click', '.install-now', function (e) {
		var _this = $(this);
		var _href = _this.attr('href');

		_this.addClass('updating-message').html('Installing...');

		$.get(_href, function () {
			location.reload();
		});

		e.preventDefault();
	});

})(jQuery);
