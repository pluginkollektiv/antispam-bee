( function() {
	var labels = [];
	var data = [];
	var dataTable = jQuery( '#ab_chart_data' );
	var maxValue;
	var chartWidth;
	var fullWidth = true;
	var pointRadius = 4;
	var chart;

	// Abort if no data is present.
	if ( ! dataTable.length ) {
		return;
	}

	// Grab the data
	jQuery( 'tfoot th', dataTable ).each( function() {
		labels.push( jQuery( this ).text() );
	} );
	jQuery( 'tbody td', dataTable ).each( function() {
		data.push( jQuery( this ).text() );
	} );

	// Determine maximum value for scaling.
	maxValue = Math.max.apply( Math, data );

	// Adjust display according if there are too many values to display readable.
	chartWidth = jQuery( '#statify_chart' ).width();
	if ( chartWidth < data.length * 4 ) {
		// Make chart scrollable, if 2px points are overlapping.
		fullWidth = false;
		pointRadius = 3;
	} else if ( chartWidth < data.length * 8 ) {
		// Shrink datapoints if 4px is overlapping, but 2 is not.
		pointRadius = 2;
	}

	// Draw chart.
	chart = new Chartist.LineChart( '#ab_chart', {
		labels: labels,
		series: [
			data,
		],
	}, {
		low: 0,
		showArea: true,
		fullWidth: fullWidth,
		width: ( fullWidth ? undefined : 5 * data.length ),
		axisX: {
			showGrid: false,
			showLabel: false,
			offset: 0,
		},
		axisY: {
			showGrid: true,
			showLabel: true,
			type: Chartist.FixedScaleAxis,
			low: 0,
			high: maxValue + 1,
			ticks: [
				0,
				Math.round( maxValue * 1 / 4 ),
				Math.round( maxValue * 2 / 4 ),
				Math.round( maxValue * 3 / 4 ),
				maxValue,
			],
			offset: 30,
		},
		plugins: [
			Chartist.plugins.tooltip( {
				appendToBody: true,
				class: 'ab-chartist-tooltip',
			} ),
		],
	} );

	// Replace default points with hollow circles, add "× Spam" to value and append date (label) as meta data.
	chart.on( 'draw', function( d ) {
		var circle;
		if ( 'point' === d.type ) {
			circle = new Chartist.Svg( 'circle', {
				cx: [ d.x ],
				cy: [ d.y ],
				r: [ pointRadius ],
				'ct:value': d.value.y + '× Spam',
				'ct:meta': labels[d.index],
			}, 'ct-point' );
			d.element.replace( circle );
		}
	} );
}() );
