( function () {
	'use strict';

	var MIN_YEAR_WIDTH = 18;
	var MAX_YEAR_WIDTH = 90;
	var ZOOM_STEP = 8;

	function rowMatchesState( row, state ) {
		var matchesSearch = ! state.search || row.dataset.search.indexOf( state.search ) !== -1;
		var matchesDestination = ! state.destination || row.dataset.destination === state.destination;
		var matchesActive = ! state.activeOnly || row.dataset.status === 'activa';

		return matchesSearch && matchesDestination && matchesActive;
	}

	function initTimeline( wrapper ) {
		var searchInput = wrapper.querySelector( '.set-search-input' );
		var destinationSelect = wrapper.querySelector( '.set-destination-select' );
		var activeOnlyCheckbox = wrapper.querySelector( '.set-active-only' );
		var legendItems = wrapper.querySelectorAll( '.set-legend-item' );
		var zoomInBtn = wrapper.querySelector( '.set-zoom-in' );
		var zoomOutBtn = wrapper.querySelector( '.set-zoom-out' );
		var viewButtons = wrapper.querySelectorAll( '.set-view-btn' );
		var rows = wrapper.querySelectorAll( '.set-row' );
		var tableRows = wrapper.querySelectorAll( '.set-table-row' );
		var visibleCountEl = wrapper.querySelector( '.set-count-visible' );

		var state = {
			search: '',
			destination: '',
			activeOnly: false,
		};

		function applyFilters() {
			var visible = 0;

			rows.forEach( function ( row ) {
				var isVisible = rowMatchesState( row, state );

				row.classList.toggle( 'set-is-hidden', ! isVisible );

				if ( isVisible ) {
					visible++;
				}
			} );

			tableRows.forEach( function ( row ) {
				row.classList.toggle( 'set-is-hidden', ! rowMatchesState( row, state ) );
			} );

			if ( visibleCountEl ) {
				visibleCountEl.textContent = String( visible );
			}
		}

		function setDestinationFilter( destination ) {
			state.destination = destination;

			if ( destinationSelect ) {
				destinationSelect.value = destination;
			}

			legendItems.forEach( function ( item ) {
				item.classList.toggle( 'is-active', item.dataset.destination === destination && destination !== '' );
			} );

			applyFilters();
		}

		if ( searchInput ) {
			searchInput.addEventListener( 'input', function () {
				state.search = searchInput.value.trim().toLowerCase();
				applyFilters();
			} );
		}

		if ( destinationSelect ) {
			destinationSelect.addEventListener( 'change', function () {
				setDestinationFilter( destinationSelect.value );
			} );
		}

		if ( activeOnlyCheckbox ) {
			activeOnlyCheckbox.addEventListener( 'change', function () {
				state.activeOnly = activeOnlyCheckbox.checked;
				applyFilters();
			} );
		}

		legendItems.forEach( function ( item ) {
			item.addEventListener( 'click', function () {
				var destination = item.dataset.destination;
				setDestinationFilter( state.destination === destination ? '' : destination );
			} );
		} );

		function zoom( delta ) {
			var current = parseFloat( getComputedStyle( wrapper ).getPropertyValue( '--set-year-width' ) ) || 40;
			var next = Math.min( MAX_YEAR_WIDTH, Math.max( MIN_YEAR_WIDTH, current + delta ) );
			wrapper.style.setProperty( '--set-year-width', next + 'px' );
		}

		if ( zoomInBtn ) {
			zoomInBtn.addEventListener( 'click', function () {
				zoom( ZOOM_STEP );
			} );
		}

		if ( zoomOutBtn ) {
			zoomOutBtn.addEventListener( 'click', function () {
				zoom( -ZOOM_STEP );
			} );
		}

		viewButtons.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				wrapper.dataset.view = btn.dataset.view;

				viewButtons.forEach( function ( other ) {
					var isActive = other === btn;
					other.classList.toggle( 'is-active', isActive );
					other.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
				} );
			} );
		} );
	}

	function initSortableTable( wrapper ) {
		var table = wrapper.querySelector( '.set-table' );

		if ( ! table ) {
			return;
		}

		var headers = table.querySelectorAll( 'th[data-key]' );
		var tbody = table.querySelector( 'tbody' );

		headers.forEach( function ( th ) {
			th.addEventListener( 'click', function () {
				var key = th.dataset.key;
				var type = th.dataset.type;
				var direction = th.getAttribute( 'aria-sort' ) === 'ascending' ? 'descending' : 'ascending';

				headers.forEach( function ( header ) {
					header.removeAttribute( 'aria-sort' );
				} );
				th.setAttribute( 'aria-sort', direction );

				var sortedRows = Array.prototype.slice.call( tbody.querySelectorAll( 'tr' ) );

				sortedRows.sort( function ( a, b ) {
					var valueA = a.dataset[ key ];
					var valueB = b.dataset[ key ];

					if ( 'number' === type ) {
						valueA = '' === valueA || undefined === valueA ? Infinity : parseFloat( valueA );
						valueB = '' === valueB || undefined === valueB ? Infinity : parseFloat( valueB );
					} else {
						valueA = valueA || '';
						valueB = valueB || '';
					}

					if ( valueA < valueB ) {
						return 'ascending' === direction ? -1 : 1;
					}

					if ( valueA > valueB ) {
						return 'ascending' === direction ? 1 : -1;
					}

					return 0;
				} );

				sortedRows.forEach( function ( row ) {
					tbody.appendChild( row );
				} );
			} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.set-wrapper' ).forEach( function ( wrapper ) {
			initTimeline( wrapper );
			initSortableTable( wrapper );
		} );
	} );
} )();
