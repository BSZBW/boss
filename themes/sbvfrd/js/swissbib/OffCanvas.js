/**
 *
 * @type {{sidebar: null, button: null, body: null, init: init, initOffCanvas: initOffCanvas, toggleOffCanvas: toggleOffCanvas, enableTransition: enableTransition}}
 */
var OffCanvas = {
  sidebar: null,
  button: null,
  body: null,

  /**
   * Initialize Off-Canvas handling
   */
  init: function () {
    this.initOffCanvas();
  },

  /**
   *
   */
  initOffCanvas: function () {
    this.sidebar = $(".sidebar");
    this.button = $("button#sidebar-offcanvas-trigger");

    if (this.sidebar.length > 0) {
      this.body = $("body");
      this.footer = $("footer");
      this.main = $(".main");
      this.header = $("header");

      this.button.click(this.toggleOffCanvas.bind(this));
    } else {
      this.button.hide();
    }
  },

  /**
   * Toggle Off Canvas
   */
  toggleOffCanvas: function () {
    this.enableTransition();

    if (this.body.hasClass("offcanvas-active")) {
      this.body.removeClass("offcanvas-active");
      this.button.removeClass("offcanvas-active");
      this.sidebar.removeClass("offcanvas-active");
      this.sidebar.css('height', '');
      this.sidebar.css('overflow-y', '');
      this.body.css('height', '');
      this.body.css('overflow-y', '');
    } else {
      this.body.addClass("offcanvas-active");
      this.button.addClass("offcanvas-active");
      this.sidebar.addClass("offcanvas-active");
      this.sidebar.css('height', window.innerHeight);
      this.sidebar.css('overflow-y', 'scroll');
      this.body.css('height', window.innerHeight);
      this.body.css('overflow-y', 'hidden');
    }
  },

  /**
   * Workaround to prevent transition on orientation change
   */
  enableTransition: function() {
    this.sidebar.addClass('transition');
    setTimeout(function() {
      $('.sidebar').removeClass('transition');
    }, 400);
  }
};

$(OffCanvas.init.bind(OffCanvas));