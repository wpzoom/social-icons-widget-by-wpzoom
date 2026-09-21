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

	// Hide the bubble, and hide it again whenever the visitor closes the panel —
	// otherwise closing the chat would leave two buttons in the corner.
	yamidoo('hide');
	yamidoo('on', 'close', function () {
		window.yamidoo('hide');
	});

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
