/* global wpzoomIconLibrary */
/**
 * Icon library on the Settings screen: every icon of every set, in a dialog
 * modelled on the one of the WordPress Icon block. Each set is fetched the
 * first time it is shown; search filters the set (or all sets) in view.
 *
 * Shared with the PRO plugin through bin/sync-from-free.sh.
 */
( function () {
	'use strict';

	var cfg = window.wpzoomIconLibrary || {};
	var dialog = document.querySelector( '.wpzoom-icon-library' );
	var openers = document.querySelectorAll( '.wpzoom-icon-library-open' );
	if ( ! dialog || ! openers.length || typeof dialog.showModal !== 'function' ) {
		return;
	}

	var i18n = cfg.i18n || {};
	var search = dialog.querySelector( 'input[type="search"]' );
	var setButtons = dialog.querySelectorAll( '.wpzoom-icon-library__sets button' );
	var main = dialog.querySelector( '.wpzoom-icon-library__main' );
	var grid = dialog.querySelector( '.wpzoom-icon-library__grid' );
	var status = dialog.querySelector( '.wpzoom-icon-library__status' );

	var titles = {};
	var kits = [];
	Array.prototype.forEach.call( setButtons, function ( button ) {
		var kit = button.getAttribute( 'data-kit' );
		if ( kit ) {
			kits.push( kit );
			titles[ kit ] = button.querySelector( '.wpzoom-icon-library__name' ).textContent;
		}
	} );

	var current = kits[ 0 ];
	var cache = {};
	var token = 0;
	var timer;

	// One request per set, kept for the rest of the visit.
	function load( kit ) {
		if ( ! cache[ kit ] ) {
			var url = cfg.ajaxUrl + '?action=wpzoom_icon_library&kit=' + encodeURIComponent( kit ) + '&nonce=' + encodeURIComponent( cfg.nonce );
			cache[ kit ] = fetch( url, { credentials: 'same-origin' } )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( json ) {
					if ( ! json || ! json.success ) {
						throw new Error( 'icon library' );
					}
					return json.data;
				} )
				.catch( function ( error ) {
					delete cache[ kit ];
					throw error;
				} );
		}
		return cache[ kit ];
	}

	// "google-scholar" -> "Google Scholar".
	function label( name ) {
		return name.replace( /[-_]+/g, ' ' ).replace( /\b\w/g, function ( c ) {
			return c.toUpperCase();
		} );
	}

	function matches( icon, query ) {
		return ! query || icon.n.indexOf( query.replace( /\s+/g, '-' ) ) !== -1 || label( icon.n ).toLowerCase().indexOf( query ) !== -1;
	}

	function group( kit, icons, heading ) {
		var section = document.createElement( 'section' );
		section.className = 'wpzoom-icon-library__group';
		if ( heading ) {
			var h = document.createElement( 'h3' );
			h.textContent = titles[ kit ] + ' · ' + icons.length;
			section.appendChild( h );
		}
		var list = document.createElement( 'ul' );
		list.className = 'wpzoom-icon-library__icons';
		icons.forEach( function ( icon ) {
			var item = document.createElement( 'li' );
			item.title = icon.n;
			if ( icon.c ) {
				item.style.setProperty( '--wpzoom-icon-color', icon.c );
			}
			var glyph = document.createElement( 'span' );
			glyph.className = 'wpzoom-icon-library__glyph';
			// SVG markup from the plugin's own icon library files.
			glyph.innerHTML = icon.s;
			var name = document.createElement( 'span' );
			name.className = 'wpzoom-icon-library__label';
			name.textContent = label( icon.n );
			item.appendChild( glyph );
			item.appendChild( name );
			list.appendChild( item );
		} );
		section.appendChild( list );
		return section;
	}

	function render() {
		var mine = ++token;
		var query = search.value.trim().toLowerCase();
		var shown = current ? [ current ] : kits;

		Array.prototype.forEach.call( setButtons, function ( button ) {
			button.setAttribute( 'aria-pressed', String( ( button.getAttribute( 'data-kit' ) || '' ) === ( current || '' ) ) );
		} );

		status.textContent = i18n.loading || '';
		Promise.all( shown.map( load ) ).then(
			function ( sets ) {
				if ( mine !== token ) {
					return;
				}
				var fragment = document.createDocumentFragment();
				var total = 0;
				sets.forEach( function ( icons, i ) {
					var hits = icons.filter( function ( icon ) {
						return matches( icon, query );
					} );
					if ( hits.length ) {
						total += hits.length;
						fragment.appendChild( group( shown[ i ], hits, shown.length > 1 ) );
					}
				} );
				grid.textContent = '';
				grid.appendChild( fragment );
				status.textContent = total ? '' : ( i18n.none || '' ).replace( '%s', search.value.trim() );
				main.scrollTop = 0;
			},
			function () {
				if ( mine === token ) {
					grid.textContent = '';
					status.textContent = i18n.failed || '';
				}
			}
		);
	}

	Array.prototype.forEach.call( openers, function ( opener ) {
		opener.addEventListener( 'click', function () {
			dialog.showModal();
			render();
			search.focus();
		} );
	} );

	Array.prototype.forEach.call( setButtons, function ( button ) {
		button.addEventListener( 'click', function () {
			current = button.getAttribute( 'data-kit' ) || '';
			render();
		} );
	} );

	search.addEventListener( 'input', function () {
		clearTimeout( timer );
		timer = setTimeout( render, 150 );
	} );

	dialog.querySelector( '.wpzoom-icon-library__close' ).addEventListener( 'click', function () {
		dialog.close();
	} );

	// A click on the backdrop lands on the dialog element itself.
	dialog.addEventListener( 'click', function ( event ) {
		if ( event.target === dialog ) {
			dialog.close();
		}
	} );
} )();
