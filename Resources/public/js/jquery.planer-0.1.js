/*global jQuery */

(function ($) {
    /* Strict mode for this plugin */
    "use strict";
    /*jslint browser: true */

   
    /** Planer
     * @param	params	Specify options in {}.
     */
    $.fn.planer = function (params) {
        var settings, canvas, ctx, lingrad, insetgrad, outerDiv, draw, setSize;
        var planer = $(this);

        /* Specify default settings */
        settings = {
            width: 256,
            height: 30,
        };

        /* Override default settings with provided params, if any */
        if (params !== undefined) {
            $.extend(settings, params);
        } else {
            params = settings;
        }

        outerDiv = document.createElement('div');
        outerDiv.style.width = settings.width + 'px';
        outerDiv.style.height = settings.height + 'px';
        outerDiv.style.position = 'relative';
        outerDiv.style.cursor = 'default';
        $(outerDiv).addClass('planerShadow');

        $(this).append(outerDiv);

        /* Create our canvas object */
        canvas = document.createElement('canvas');
        canvas.setAttribute('width', settings.width);
        canvas.setAttribute('height', settings.height);
        outerDiv.appendChild(canvas);

        /* Get a reference to the context of our canvas object */
        ctx = canvas.getContext("2d");

        /* Create our linear gradient for the outer recf */
        lingrad = ctx.createLinearGradient(0, 0, 0, canvas.height);
        lingrad.addColorStop(0, '#CFD8DC'); // 50
        lingrad.addColorStop(1, '#90A4AE'); // 300
        insetgrad = ctx.createLinearGradient(0, 0, 0, canvas.height);
        insetgrad.addColorStop(0, '#607D8B'); // 500
        insetgrad.addColorStop(1, '#78909C'); // 400

        /* Borders should be 1px */
        ctx.lineWidth = 1;

        /**
         * render the widget in its entirety.
         */
        draw = function () {
            /* Clear canvas entirely */
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            /*** IMAGERY ***/

            /* draw outer rect */
            ctx.fillStyle = lingrad;
            ctx.beginPath();
            ctx.strokeStyle = '#546E7A'; // 600
            ctx.rect(0, 0, canvas.width, canvas.height);
            ctx.fill();
            ctx.stroke();
            
            ctx.fillStyle = insetgrad;
            ctx.beginPath();
            ctx.strokeStyle = '#546E7A'; // 600
            ctx.rect(8, 8, canvas.width-16, canvas.height-16);
            ctx.fill();
            ctx.stroke();

        };

        /* Do an initial draw */
        draw();

    	return this;
    };
    
    
    
    
    
    /** AutoSizedPlaner
     * @param	params	Specify options in {}.
     */
    $.fn.autoSizedPlaner = function (params) {
        var settings, setSize, innerPlaner;
        var planer = $(this);

        /* Specify default settings */
        settings = {
        	width: 256,
        	height: 30
        };

        /* Override default settings with provided params, if any */
        if (params !== undefined) {
            $.extend(settings, params);
        } else {
            params = settings;
        }

        setSize = function(size) {
        	if (planer.children().length > 0) {
        		planer.html('');
        	}
        	settings.width = size;
        	settings.height = planer.height();
        	innerPlaner = planer.planer(settings);
        };
        planer.setSize = setSize;

        /* auto resize management */
        $( window ).resize(function(e) {
        	var size = Math.max(256, planer.width());
        	planer.setSize(size);
        });
        planer.resize(function(e) {
        	var size = Math.max(256, planer.width());
        	planer.setSize(size);
        });
        
	    /* init */
        var size = Math.max(256, planer.width());
    	planer.setSize(size); // also instanciate the component for the first time.
    	settings.height = planer.height();
	    
    	return this;
    };
    
    
}(jQuery));
