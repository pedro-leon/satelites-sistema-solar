( function () {
	'use strict';

	var MIN_YEAR_WIDTH = 18;
	var MAX_YEAR_WIDTH = 90;
	var ZOOM_STEP = 8;

	function initTimeline( wrapper ) {
		var searchInput = wrapper.querySelector( '.set-search-input' );
		var destinationSelect = wrapper.querySelector( '.set-destination-select' );
		var legendItems = wrapper.querySelectorAll( '.set-legend-item' );
		var zoomInBtn = wrapper.querySelector( '.set-zoom-in' );
		var zoomOutBtn = wrapper.querySelector( '.set-zoom-out' );
		var rows = wrapper.querySelectorAll( '.set-row' );
		var visibleCountEl = wrapper.querySelector( '.set-count-visible' );

		var state = {
			search: '',
			destination: '',
		};

		function applyFilters() {
			var visible = 0;

			rows.forEach( function ( row ) {
				var matchesSearch = ! state.search || row.dataset.search.indexOf( state.search ) !== -1;
				var matchesDestination = ! state.destination || row.dataset.destination === state.destination;
				var isVisible = matchesSearch && matchesDestination;

				row.classList.toggle( 'set-is-hidden', ! isVisible );

				if ( isVisible ) {
					visible++;
				}
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
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.set-wrapper' ).forEach( initTimeline );
	} );
} )();
