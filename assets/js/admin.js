/**
 * Nudge — admin settings enhancements (progressive, dependency-free).
 *
 * 1. Inline help: each "?" button is wired to an accessible popover. Where the
 *    native Popover API exists it is used; otherwise a small show/hide fallback
 *    keeps it keyboard- and screen-reader-operable via aria-expanded.
 * 2. Live preview: reflects the merchant's colours and progress message in real
 *    time so they can see the result before saving.
 *
 * Loaded with `defer`; degrades gracefully (settings still save without JS).
 */
( function () {
	'use strict';

	var root = document.querySelector( '.nudge-admin' );

	if ( ! root ) {
		return;
	}

	var supportsPopover =
		typeof HTMLElement !== 'undefined' &&
		HTMLElement.prototype.hasOwnProperty( 'popover' );

	/* ---- Inline help popovers (fallback only) ------------------------ */

	function closeAllFallback( except ) {
		root.querySelectorAll( '.nudge-help[aria-expanded="true"]' ).forEach(
			function ( btn ) {
				if ( btn === except ) {
					return;
				}
				btn.setAttribute( 'aria-expanded', 'false' );
				var tip = document.getElementById(
					btn.getAttribute( 'aria-describedby' )
				);
				if ( tip ) {
					tip.hidden = true;
				}
			}
		);
	}

	root.addEventListener( 'click', function ( event ) {
		var btn = event.target.closest( '.nudge-help' );

		if ( ! btn || supportsPopover ) {
			return;
		}

		var tip = document.getElementById( btn.getAttribute( 'aria-describedby' ) );
		if ( ! tip ) {
			return;
		}

		var open = btn.getAttribute( 'aria-expanded' ) === 'true';
		closeAllFallback( btn );
		btn.setAttribute( 'aria-expanded', String( ! open ) );
		tip.hidden = open;
	} );

	if ( ! supportsPopover ) {
		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' ) {
				closeAllFallback( null );
			}
		} );
		document.addEventListener( 'click', function ( event ) {
			if ( ! event.target.closest( '.nudge-help, .nudge-tip' ) ) {
				closeAllFallback( null );
			}
		} );
	}

	/* ---- Live preview ------------------------------------------------ */

	var preview = root.querySelector( '[data-nudge-preview]' );

	if ( ! preview ) {
		return;
	}

	function field( name ) {
		return root.querySelector( '[name="nudge_settings[' + name + ']"]' );
	}

	function render() {
		var fill = field( 'bar_color' );
		var track = field( 'bar_bg_color' );
		var success = field( 'success_color' );
		var progress = field( 'message_progress' );

		if ( fill ) {
			preview.style.setProperty( '--nudge-fill', fill.value );
		}
		if ( track ) {
			preview.style.setProperty( '--nudge-track', track.value );
		}
		if ( success ) {
			preview.style.setProperty( '--nudge-success', success.value );
		}

		var messageEl = preview.querySelector( '[data-nudge-preview-message]' );
		if ( messageEl && progress ) {
			messageEl.textContent = progress.value.replace( '{amount}', '$15.00' );
		}
	}

	root.addEventListener( 'input', render );
	root.addEventListener( 'change', render );

	render();
} )();
