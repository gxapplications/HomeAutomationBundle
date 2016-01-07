(function ($) {
    /* Strict mode for this plugin */
    "use strict";

    $.fn.showTimer = function (params) {
    	var settings;
    	var timer = $(this);
    	
	    /* Specify default settings */
	    settings = {
	    	date: Date.now()+60000, // 1 minute by default
	    	refresh: 1000, // every seconds by default
			onEnd: function() { }
	    };
	
	    /* Override default settings with provided params, if any */
	    if (params !== undefined) {
	        $.extend(settings, params);
	    } else {
	        params = settings;
	    }
	    
	    // reset element
	    timer.empty();
		var div = document.createElement('div');
		timer.append(div);
		
		$(div).countdown({
			date: settings.date*1000,
			refresh: settings.refresh,
			render: function(data) {
				var h = data.hours + (data.days + (data.years*365))*24;
				$(div).text(h+":"+this.leadingZeros(data.min, 2)+":"+this.leadingZeros(data.sec, 2));
			},
			onEnd: function() {
				timer.empty();
				settings.onEnd();
			}
		});

    	return this;
    };
    
    $.fn.callbackGuiSuccess = function () {
    	var element = $(this);
    	
    	element.stop(false, true)
		.addClass('gx-green', 800, "swing").addClass('slow-transition')
		.delay(2000).removeClass('gx-green', 4000, "swing").removeClass('slow-transition');
    	
    	return this;
    };
    
    $.fn.callbackGuiError = function () {
    	var element = $(this);
    	
    	element.stop(false, true)
		.addClass('md-warn', 800, "swing").effect("shake").addClass('slow-transition')
		.delay(3000).removeClass('md-warn', 4000, "swing").removeClass('slow-transition');
    	
    	return this;
    };
    
}(jQuery));



function refreshHeatingDoubleKnob(component, onMinUpdate, onMaxUpdate, onCenterClick) {
	$('div#double-knob-'+component['id']).temperatureLoader({
		minValue: component['heating_dashboard']['minimal_temp'],
		maxValue: component['heating_dashboard']['maximal_temp'],
		title: component['title'],
		scaleOffset:14.0,
		scaleAmplitude: 18.0,
		precision: 0,
		onMinUpdate: onMinUpdate,
		onMaxUpdate: onMaxUpdate,
		onCenterClick: onCenterClick,
		componentData: component
	});
}

function refreshHeatingPlaner(component) {
	$('div#planer-'+component['id']).autoSizedPlaner({
		
	});
}