( function () {
	'use strict';

	if ( typeof window.sssData === 'undefined' ) {
		return;
	}

	var data = window.sssData;
	var i18n = data.i18n || {};

	/**
	 * Compara dos satélites según una clave y un tipo de dato.
	 * Los valores nulos/desconocidos se ordenan siempre al final.
	 */
	function compareMoons( a, b, key, type, dir ) {
		var valA = a[ key ];
		var valB = b[ key ];

		var aEmpty = valA === null || valA === undefined || valA === '';
		var bEmpty = valB === null || valB === undefined || valB === '';

		if ( aEmpty && bEmpty ) {
			return 0;
		}
		if ( aEmpty ) {
			return 1;
		}
		if ( bEmpty ) {
			return -1;
		}

		var result;
		if ( 'number' === type ) {
			result = parseFloat( valA ) - parseFloat( valB );
		} else {
			result = String( valA ).localeCompare( String( valB ), 'es', { sensitivity: 'base' } );
		}

		return 'desc' === dir ? -result : result;
	}

	function formatNumber( value, decimals ) {
		if ( value === null || value === undefined || value === '' ) {
			return i18n.unknown || '—';
		}
		return Number( value ).toLocaleString( 'es-ES', {
			maximumFractionDigits: decimals,
			minimumFractionDigits: 0,
		} );
	}

	function formatText( value ) {
		return value ? value : ( i18n.unknown || '—' );
	}

	function initExplorer( wrapper ) {
		var select = wrapper.querySelector( '.sss-planet-select' );
		var table  = wrapper.querySelector( '.sss-table' );
		var tbody  = wrapper.querySelector( '.sss-moons-tbody' );
		var empty  = wrapper.querySelector( '.sss-empty-message' );

		if ( ! select || ! table || ! tbody ) {
			return;
		}

		var headers = Array.prototype.slice.call( table.querySelectorAll( 'thead th[data-key]' ) );
		var sortState = { key: 'name', dir: 'asc' };

		function updateHeaderIndicators() {
			headers.forEach( function ( th ) {
				if ( th.getAttribute( 'data-key' ) === sortState.key ) {
					th.setAttribute( 'aria-sort', 'asc' === sortState.dir ? 'ascending' : 'descending' );
				} else {
					th.setAttribute( 'aria-sort', 'none' );
				}
			} );
		}

		function renderRow( moon ) {
			var tr = document.createElement( 'tr' );

			var cells = [
				{ value: formatText( moon.name ) },
				{ value: formatText( moon.provisional_name ) },
				{ value: formatNumber( moon.distance_km, 0 ) },
				{ value: formatNumber( moon.diameter_km, 1 ) },
				{ value: formatNumber( moon.density, 2 ) },
				{ value: moon.discovery_year ? String( moon.discovery_year ) : ( i18n.unknown || '—' ) },
				{ value: formatText( moon.discoverer ) },
			];

			cells.forEach( function ( cell ) {
				var td = document.createElement( 'td' );
				td.textContent = cell.value;
				tr.appendChild( td );
			} );

			return tr;
		}

		function render() {
			var planetId = select.value;
			var moons = ( data.moonsByPlanet && data.moonsByPlanet[ planetId ] ) ? data.moonsByPlanet[ planetId ].slice() : [];
			var activeHeader = headers.filter( function ( th ) {
				return th.getAttribute( 'data-key' ) === sortState.key;
			} )[ 0 ];
			var type = activeHeader ? activeHeader.getAttribute( 'data-type' ) : 'string';

			moons.sort( function ( a, b ) {
				return compareMoons( a, b, sortState.key, type, sortState.dir );
			} );

			tbody.innerHTML = '';

			if ( 0 === moons.length ) {
				table.hidden = true;
				if ( empty ) {
					empty.textContent = i18n.noMoons || '';
					empty.hidden = false;
				}
				return;
			}

			table.hidden = false;
			if ( empty ) {
				empty.hidden = true;
			}

			var fragment = document.createDocumentFragment();
			moons.forEach( function ( moon ) {
				fragment.appendChild( renderRow( moon ) );
			} );
			tbody.appendChild( fragment );

			updateHeaderIndicators();
		}

		select.addEventListener( 'change', render );

		headers.forEach( function ( th ) {
			th.setAttribute( 'tabindex', '0' );
			th.setAttribute( 'role', 'button' );

			var handleSort = function () {
				var key = th.getAttribute( 'data-key' );
				if ( sortState.key === key ) {
					sortState.dir = 'asc' === sortState.dir ? 'desc' : 'asc';
				} else {
					sortState.key = key;
					sortState.dir = 'asc';
				}
				render();
			};

			th.addEventListener( 'click', handleSort );
			th.addEventListener( 'keydown', function ( event ) {
				if ( 'Enter' === event.key || ' ' === event.key ) {
					event.preventDefault();
					handleSort();
				}
			} );
		} );

		render();
	}

	function init() {
		var wrappers = document.querySelectorAll( '.sss-wrapper' );
		wrappers.forEach( function ( wrapper ) {
			initExplorer( wrapper );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
