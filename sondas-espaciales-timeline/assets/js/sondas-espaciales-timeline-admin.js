( function ( $ ) {
	'use strict';

	$( function () {
		// Confirmación antes de borrar una sonda o un destino.
		$( '.set-admin-delete-link' ).on( 'click', function ( event ) {
			var message = $( this ).data( 'confirm' );

			if ( message && ! window.confirm( message ) ) { // eslint-disable-line no-alert
				event.preventDefault();
			}
		} );

		// Selector de color para el formulario de destinos.
		if ( $.fn.wpColorPicker ) {
			$( '.set-admin-color-field' ).wpColorPicker();
		}

		// En el formulario de sondas, ocultar/exigir el año de fin según el estado.
		var statusField  = $( '#set-status' );
		var endYearRow   = $( '.set-admin-end-year-row' );
		var endYearField = $( '#set-end-year' );

		function toggleEndYear() {
			if ( ! statusField.length ) {
				return;
			}

			var isActive = 'activa' === statusField.val();

			endYearRow.toggle( ! isActive );
			endYearField.prop( 'required', ! isActive );

			if ( isActive ) {
				endYearField.val( '' );
			}
		}

		statusField.on( 'change', toggleEndYear );
		toggleEndYear();

		// Autorrelleno del identificador a partir del nombre/etiqueta,
		// solo mientras el usuario no haya tocado el campo a mano.
		var DIACRITICS_REGEX = new RegExp( '[̀-ͯ]', 'g' );

		function slugify( text ) {
			return text
				.toString()
				.toLowerCase()
				.normalize( 'NFD' )
				.replace( DIACRITICS_REGEX, '' )
				.replace( /[^a-z0-9]+/g, '-' )
				.replace( /^-+|-+$/g, '' );
		}

		function wireAutoSlug( sourceSelector, idSelector ) {
			var source  = $( sourceSelector );
			var idField = $( idSelector );

			if ( ! source.length || ! idField.length || idField.prop( 'disabled' ) ) {
				return;
			}

			var idTouched = idField.val().length > 0;

			idField.on( 'input', function () {
				idTouched = true;
			} );

			source.on( 'input', function () {
				if ( ! idTouched ) {
					idField.val( slugify( source.val() ) );
				}
			} );
		}

		wireAutoSlug( '#set-name', '#set-id' );
		wireAutoSlug( '#set-dest-label', '#set-dest-id' );
	} );
} )( window.jQuery );
