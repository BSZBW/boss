"use strict";

var BootstrapPrototype = {
	container:	null,
	sidebar:	null,
	body:		null,

	init: function() {
		this.initOffCanvas();
	},

	initOffCanvas: function() {
		if ($(".sidebar").length > 0) {

			this.container	= $(".container");
			this.sidebar 	= $(".sidebar");
			this.body 		= $("body");

			$("button#toggle-sidebar-offcanvas").click(this.toggleOffCanvas.bind(this));
		} else {
			$('button#toggle-sidebar-offcanvas').hide();
		}
	},

	toggleOffCanvas: function() {
		if ( this.container.hasClass("offcanvas-active") ) {
			this.container.removeClass("offcanvas-active");
            this.sidebar.css('height', '');
            this.sidebar.css('overflow-y', '');
            this.body.css('height', '');
            this.body.css('overflow-y', '');
		} else {
			this.container.addClass("offcanvas-active");
			this.sidebar.css('height', window.innerHeight);
			this.sidebar.css('overflow-y', 'scroll');
			this.body.css('height', window.innerHeight);
			this.body.css('overflow-y', 'hidden');
		}
	}
};

$(BootstrapPrototype.init.bind(BootstrapPrototype));