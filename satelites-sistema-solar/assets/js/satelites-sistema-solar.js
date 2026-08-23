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

	/**
	 * El campo "descubridor" puede traer varios nombres separados por comas
	 * (p. ej. lunas galileanas). Se muestra solo el primero y, si hay más,
	 * el resto queda disponible al pasar el ratón (atributo title).
	 */
	function renderDiscovererCell( td, value ) {
		if ( ! value ) {
			td.textContent = i18n.unknown || '—';
			return;
		}

		var names = value.split( ',' ).map( function ( name ) {
			return name.trim();
		} ).filter( Boolean );

		td.textContent = names[ 0 ] || ( i18n.unknown || '—' );

		if ( names.length > 1 ) {
			td.textContent += ' …';
			td.title = value;
			td.classList.add( 'sss-has-more' );
		}
	}

	function initExplorer( wrapper ) {
		var buttons      = Array.prototype.slice.call( wrapper.querySelectorAll( '.sss-planet-button' ) );
		var explorer     = wrapper.querySelector( '.sss-explorer' );
		var title        = wrapper.querySelector( '.sss-explorer-title' );
		var hint         = wrapper.querySelector( '.sss-explorer-hint' );
		var tableWrapper = wrapper.querySelector( '.sss-table-wrapper' );
		var table        = wrapper.querySelector( '.sss-table' );
		var tbody        = wrapper.querySelector( '.sss-moons-tbody' );
		var empty        = wrapper.querySelector( '.sss-empty-message' );

		if ( ! buttons.length || ! explorer || ! table || ! tbody ) {
			return;
		}

		var headers = Array.prototype.slice.call( table.querySelectorAll( 'thead th[data-key]' ) );
		var sortState = { key: 'name', dir: 'asc' };
		var currentPlanetId = null;

		function planetName( planetId ) {
			var planet = ( data.planets || [] ).filter( function ( p ) {
				return p.id === planetId;
			} )[ 0 ];
			return planet ? planet.name : '';
		}

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

			headers.forEach( function ( th ) {
				var key = th.getAttribute( 'data-key' );
				var td = document.createElement( 'td' );
				td.className = th.className;

				if ( 'discoverer' === key ) {
					renderDiscovererCell( td, moon.discoverer );
				} else if ( 'distance_km' === key ) {
					td.textContent = formatNumber( moon.distance_km, 0 );
				} else if ( 'diameter_km' === key ) {
					td.textContent = formatNumber( moon.diameter_km, 1 );
				} else if ( 'density' === key ) {
					td.textContent = formatNumber( moon.density, 2 );
				} else if ( 'discovery_year' === key ) {
					td.textContent = moon.discovery_year ? String( moon.discovery_year ) : ( i18n.unknown || '—' );
				} else {
					td.textContent = formatText( moon[ key ] );
				}

				tr.appendChild( td );
			} );

			return tr;
		}

		function render() {
			var moons = ( data.moonsByPlanet && data.moonsByPlanet[ currentPlanetId ] ) ? data.moonsByPlanet[ currentPlanetId ].slice() : [];
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

		function showPlanet( planetId ) {
			currentPlanetId = planetId;

			if ( hint ) {
				hint.hidden = true;
			}
			if ( title ) {
				title.hidden = false;
				var template = i18n.explorerTitle || '%s';
				title.textContent = template.replace( '%s', planetName( planetId ) );
			}
			if ( tableWrapper ) {
				tableWrapper.hidden = false;
			}

			buttons.forEach( function ( button ) {
				var isActive = button.getAttribute( 'data-planet-id' ) === planetId;
				button.setAttribute( 'aria-expanded', isActive ? 'true' : 'false' );
				button.closest( '.sss-summary-item' ).classList.toggle( 'is-active', isActive );
			} );

			render();
			explorer.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
		}

		function hidePlanets() {
			currentPlanetId = null;

			if ( hint ) {
				hint.hidden = false;
			}
			if ( title ) {
				title.hidden = true;
			}
			if ( tableWrapper ) {
				tableWrapper.hidden = true;
			}
			if ( empty ) {
				empty.hidden = true;
			}

			buttons.forEach( function ( button ) {
				button.setAttribute( 'aria-expanded', 'false' );
				button.closest( '.sss-summary-item' ).classList.remove( 'is-active' );
			} );
		}

		buttons.forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var planetId = button.getAttribute( 'data-planet-id' );
				if ( planetId === currentPlanetId ) {
					hidePlanets();
				} else {
					showPlanet( planetId );
				}
			} );
		} );

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
