/* WPZOOM Click-to-Chat — corner launcher toggle + AI Chat channel */
(function () {
	'use strict';

	var settings = window.wpzoomCtcSettings || {};
	var widget   = document.getElementById('wpzoom-ctc-widget');

	if (!widget) {
		return;
	}

	var launcher = widget.querySelector('.wpzoom-ctc-launcher');
	var buttons  = widget.querySelector('.wpzoom-ctc-buttons');

	// -- Corner launcher -----------------------------------------------------

	function open() {
		widget.classList.add('is-open');
		if (launcher) {
			launcher.setAttribute('aria-expanded', 'true');
		}
		if (buttons) {
			buttons.removeAttribute('aria-hidden');
		}
	}

	function close() {
		widget.classList.remove('is-open');
		if (launcher) {
			launcher.setAttribute('aria-expanded', 'false');
		}
		if (buttons) {
			buttons.setAttribute('aria-hidden', 'true');
		}
	}

	function toggle() {
		widget.classList.contains('is-open') ? close() : open();
	}

	if (launcher) {
		launcher.addEventListener('click', function (e) {
			e.stopPropagation();
			toggle();
		});

		document.addEventListener('click', function (e) {
			if (widget.classList.contains('is-open') && !widget.contains(e.target)) {
				close();
			}
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && widget.classList.contains('is-open')) {
				close();
				launcher.focus();
			}
		});
	}

	// -- AI Chat (Yamidoo) channel -------------------------------------------
	//
	// The chat lives in the launcher alongside WhatsApp & co, so Yamidoo's own
	// floating bubble would be a second button for the same thing. We hide it and
	// drive the panel through the widget's JS API instead.

	if (!settings.yamidoo) {
		return;
	}

	// The documented queue stub: commands called before widget.js finishes
	// loading are replayed once it does, so this works whatever the script order
	// and whichever plugin embeds the widget.
	var yamidoo = window.yamidoo || (window.yamidoo = function () {
		(window.yamidoo.q = window.yamidoo.q || []).push(arguments);
	});

	// Which command hides the bubble depends on the widget build the page loads.
	// Builds that mark <html> with `yamidoo-api-2` have hideLauncher, which
	// removes only the bubble and keeps floating messages working. Older builds
	// only have hide, which unmounts the whole widget, so on those the bubble
	// must be hidden again after every close or it would come back.
	function hideBubble() {
		if (document.documentElement.classList.contains('yamidoo-api-2')) {
			window.yamidoo('hideLauncher');
			return;
		}
		window.yamidoo('hide');
		window.yamidoo('on', 'close', function () {
			window.yamidoo('hide');
		});
	}

	// The widget boots asynchronously and adds `yamidoo-ready` when it has, so
	// the version check has to wait for that class rather than run once now.
	// Both commands are then dispatched before the widget's config arrives, so
	// the bubble never gets a chance to paint.
	if (document.documentElement.classList.contains('yamidoo-ready')) {
		hideBubble();
	} else if (typeof MutationObserver === 'function') {
		var watcher = new MutationObserver(function () {
			if (document.documentElement.classList.contains('yamidoo-ready')) {
				watcher.disconnect();
				hideBubble();
			}
		});
		watcher.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
	} else {
		// No MutationObserver: the command every build understands, queued now.
		yamidoo('hide');
		yamidoo('on', 'close', function () {
			window.yamidoo('hide');
		});
	}

	var chatButton = widget.querySelector('[data-wpzoom-ctc-action="yamidoo"]');

	if (chatButton) {
		chatButton.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			window.yamidoo('open');
			close();
		});
	}
})();
