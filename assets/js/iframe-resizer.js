/**
 * Handles resizing iframe embed
 */
;(function($, window, document, undefined) {

    "use strict";

    var pluginName = "socialShopIframeResizer",
        defaults = {
        };

    /**
     * Initialise
     * @param {DomElement} element 
     * @param {Object} options 
     */
    function Plugin (element, options) {
        this.element = element;
        this.settings = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this.resizeTimer = null;
        this.$container = $(element);
        this.init();
    }

    $.extend(Plugin.prototype, {
        /**
         * Init the plugin
         */
        init: function() {
            this.removeUnwantedNags();
            $(window).on('resize', this.resizeEvent.bind(this));
            this.resize(null);
        },
        /**
         * Debounce resize event
         * @param {Event} e
         */
        resizeEvent: function(e) {
            if (this.resizeTimer) {
                clearTimeout(this.resizeTimer);
            }

            this.resizeTimer = setTimeout(this.resize.bind(this), 100);
        },
        /**
         * Resize the iframe
         * @param {Event} e
         */
        resize: function(e) {
            var offset = this.$container.offset();
            var windowHeight = $(window).height();
            var height =  windowHeight - offset.top;
            var $content = $('#wpbody-content');

            if ($content.length) {
                height -= parseInt($content.css('paddingBottom'));
            }

            this.$container.css('height', height + 'px');
        },
        /**
         * Remove unwanted nag messages other plugins put above our iframe
         */
        removeUnwantedNags: function () {
            this.$container.prevAll().remove();
        }
    });

    $.fn[ pluginName ] = function(options) {
        return this.each(function() {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" +
                    pluginName, new Plugin(this, options));
            }
        });
    };

})(jQuery, window, document);
