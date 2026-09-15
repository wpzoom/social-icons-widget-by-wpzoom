/* global wpzoomAiChat */
/**
 * AI Chat card: run the Support Readiness Scan on this site from wp-admin and
 * render the report (5 answered questions, locked count, gaps, readiness).
 * Everything rendered comes from the API (third-party page text via the
 * model), so nodes are built with textContent — never innerHTML.
 */
( function () {
	'use strict';

	var cfg = window.wpzoomAiChat || {};
	var btn = document.getElementById( 'wpzoom-ai-chat-scan' );
	var out = document.getElementById( 'wpzoom-ai-chat-scan-result' );
	if ( ! btn || ! out ) {
		return;
	}

	function h( tag, cls, text ) {
		var el = document.createElement( tag );
		if ( cls ) {
			el.className = cls;
		}
		if ( text !== undefined && text !== null ) {
			el.textContent = text;
		}
		return el;
	}

	function fmt( tpl ) {
		var args = Array.prototype.slice.call( arguments, 1 );
		var i = 0;
		return tpl.replace( /%(\d+\$)?[ds]/g, function ( m, pos ) {
			var idx = pos ? parseInt( pos, 10 ) - 1 : i++;
			return args[ idx ];
		} );
	}

	function setStatus( text, spinning ) {
		out.hidden = false;
		out.replaceChildren();
		var row = h( 'div', 'wpzoom-ai-chat-status' );
		if ( spinning ) {
			row.appendChild( h( 'span', 'spinner is-active' ) );
		}
		row.appendChild( h( 'span', null, text ) );
		out.appendChild( row );
	}

	function chip( type ) {
		var label = cfg.i18n[ type ] || cfg.i18n.general;
		return h( 'span', 'wpzoom-ai-chat-chip wpzoom-ai-chat-chip--' + ( cfg.i18n[ type ] ? type : 'general' ), label );
	}

	function render( r ) {
		out.replaceChildren();
		var total = r.coverage && r.coverage.total ? r.coverage.total : 0;
		var pct = total ? Math.round( ( r.coverage.answered / total ) * 100 ) : 0;
		var tone = pct >= 80 ? 'good' : pct >= 55 ? 'ok' : 'low';

		var head = h( 'div', 'wpzoom-ai-chat-scan-head' );
		var ring = h( 'div', 'wpzoom-ai-chat-ring wpzoom-ai-chat-ring--' + tone );
		ring.style.setProperty( '--pct', pct );
		ring.appendChild( h( 'strong', null, pct + '%' ) );
		head.appendChild( ring );
		var headText = h( 'div', 'wpzoom-ai-chat-scan-headtext' );
		headText.appendChild( h( 'strong', null, r.coverage.answered + ' / ' + total + ' ' + cfg.i18n.readiness ) );
		if ( r.business ) {
			headText.appendChild( h( 'p', null, r.business ) );
		}
		headText.appendChild( h( 'span', 'wpzoom-ai-chat-scan-stats', fmt( cfg.i18n.stats, r.pages.length, ( r.timings.totalMs / 1000 ).toFixed( 1 ) ) ) );
		head.appendChild( headText );
		out.appendChild( head );

		var list = h( 'ol', 'wpzoom-ai-chat-qa' );
		r.questions.forEach( function ( q ) {
			var li = h( 'li' );
			var qRow = h( 'div', 'wpzoom-ai-chat-q' );
			qRow.appendChild( h( 'strong', null, q.q ) );
			qRow.appendChild( chip( q.type ) );
			li.appendChild( qRow );
			li.appendChild( h( 'p', 'wpzoom-ai-chat-a', q.answer || '' ) );
			if ( q.source ) {
				var src = h( 'span', 'wpzoom-ai-chat-src', cfg.i18n.answered + ' ' );
				var a = h( 'a', null, q.source.title || q.source.url );
				a.href = q.source.url;
				a.target = '_blank';
				a.rel = 'noopener';
				src.appendChild( a );
				li.appendChild( src );
			}
			list.appendChild( li );
		} );
		out.appendChild( list );

		if ( r.locked && r.locked.length ) {
			out.appendChild( h( 'p', 'wpzoom-ai-chat-more', fmt( cfg.i18n.more, r.locked.length ) ) );
		}

		if ( r.gaps && r.gaps.length ) {
			out.appendChild( h( 'h4', null, cfg.i18n.gaps ) );
			var gl = h( 'ul', 'wpzoom-ai-chat-gaps' );
			r.gaps.forEach( function ( g ) {
				var li = h( 'li' );
				li.appendChild( h( 'strong', null, g.q ) );
				li.appendChild( chip( g.type ) );
				if ( g.why ) {
					li.appendChild( h( 'span', 'wpzoom-ai-chat-why', g.why ) );
				}
				gl.appendChild( li );
			} );
			out.appendChild( gl );
		}
	}

	function readStream( res, onEvent ) {
		var reader = res.body.getReader();
		var dec = new TextDecoder();
		var buf = '';
		function pump() {
			return reader.read().then( function ( chunk ) {
				if ( chunk.done ) {
					if ( buf.trim() ) {
						onEvent( JSON.parse( buf ) );
					}
					return;
				}
				buf += dec.decode( chunk.value, { stream: true } );
				var nl;
				while ( ( nl = buf.indexOf( '\n' ) ) >= 0 ) {
					var line = buf.slice( 0, nl ).trim();
					buf = buf.slice( nl + 1 );
					if ( line ) {
						onEvent( JSON.parse( line ) );
					}
				}
				return pump();
			} );
		}
		return pump();
	}

	btn.addEventListener( 'click', function () {
		btn.disabled = true;
		setStatus( cfg.i18n.discover, true );
		fetch( cfg.apiBase + '/api/scan', {
			method: 'POST',
			headers: { 'content-type': 'application/json' },
			body: JSON.stringify( { url: cfg.siteUrl } ),
		} )
			.then( function ( res ) {
				var ct = res.headers.get( 'content-type' ) || '';
				if ( ct.indexOf( 'ndjson' ) < 0 ) {
					return res.json().then( function ( d ) {
						if ( d.error ) {
							throw new Error( d.error.message );
						}
						render( d.result );
					} );
				}
				var finished = false;
				return readStream( res, function ( ev ) {
					if ( ev.stage === 'read' ) {
						setStatus( fmt( cfg.i18n.read, ev.pages ), true );
					} else if ( ev.stage === 'answer' ) {
						setStatus( cfg.i18n.answer, true );
					} else if ( ev.error ) {
						throw new Error( ev.error.message );
					} else if ( ev.result ) {
						finished = true;
						render( ev.result );
					}
				} ).then( function () {
					if ( ! finished ) {
						throw new Error( cfg.i18n.failed );
					}
				} );
			} )
			.catch( function ( err ) {
				setStatus( ( err && err.message ) || cfg.i18n.failed, false );
			} )
			.then( function () {
				btn.disabled = false;
			} );
	} );
} )();
