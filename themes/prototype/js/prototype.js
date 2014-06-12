"use strict";

var BootstrapPrototype = {
	container: null,

	init: function() {
		this.initOffCanvas();
	},

	initOffCanvas: function() {
		this.container = $(".container");
		$("button#toggle-sidebar-offcanvas").click(this.toggleOffCanvas.bind(this));
	},

	toggleOffCanvas: function() {
		if ( this.container.hasClass("offcanvas-active") ) {
			this.container.removeClass("offcanvas-active");
		} else {
			this.container.addClass("offcanvas-active");
		}
	}
};

$(BootstrapPrototype.init.bind(BootstrapPrototype));