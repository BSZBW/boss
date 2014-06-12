var BootstrapPrototype = {
	container: null,

	init: function() {
		this.initOffCanvas();
	},

	initOffCanvas: function() {
		if ($(".sidebar").length > 0) {
			this.container = $(".container");
			$("button#toggle-sidebar-offcanvas").click(this.toggleOffCanvas.bind(this));
		} else {
			$('button#toggle-sidebar-offcanvas').hide();
		}
	},

	toggleOffCanvas: function() {
		if ( this.container.hasClass("offcanvas-active") ) {
			this.container.removeClass("offcanvas-active");
		} else {
			this.container.addClass("offcanvas-active");
		}
	}
};

"use strict";

$(BootstrapPrototype.init.bind(BootstrapPrototype));