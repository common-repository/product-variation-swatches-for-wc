/*global jQuery, Backbone, _ */
(function ($, Backbone, _) {
    'use strict';

    /**
     * EVINCE Backbone Modal plugin
     *
     * @param {object} options
     */

    $.fn.EVINCEBackboneModal = function (options) {
        return this.each(function () {
            new $.EVINCEBackboneModal($(this), options);
        });
    };

    /**
     * Initialize the Backbone Modal
     *
     * @param {object} element [description]
     * @param {object} options [description]
     */
    $.EVINCEBackboneModal = function (element, options) {
        // Set settings
        var settings = $.extend({}, $.EVINCEBackboneModal.defaultOptions, options);

        if (settings.template) {
            new $.EVINCEBackboneModal.View({
                target: settings.template,
                string: settings.data
            });
        }
    };

    /**
     * Set default options
     *
     * @type {object}
     */
    $.EVINCEBackboneModal.defaultOptions = {
        template: '',
        data: {}
    };

    /**
     * Create the Backbone Modal
     *
     * @return {null}
     */
    $.EVINCEBackboneModal.View = Backbone.View.extend({
        tagName: 'div',
        id: 'evince-backbone-modal-dialog',
        _target: undefined,
        _string: undefined,
        events: {
            'click .modal-close': 'closeButton',
            'click #btn-ok': 'addButton',
            'click #send-ajax': 'sendAjax',
            'touchstart #btn-ok': 'addButton',
            'keydown': 'keyboardActions',
            'change :radio': 'optionChange'
        },
        resizeContent: function resizeContent() {
            var $content = $('.evince-backbone-modal-content').find('article');
            var max_h = $(window).height() * 0.75;

            $content.css({
                'max-height': max_h + 'px'
            });
        },
        initialize: function initialize(data) {
            var view = this;
            this._target = data.target;
            this._string = data.string;
            _.bindAll(this, 'render');
            this.render();

            $(window).resize(function () {
                view.resizeContent();
            });
        },
        render: function render() {
            var template = wp.template(this._target);

            this.$el.append(template(this._string));

            $(document.body).css({
                'overflow': 'hidden'
            }).append(this.$el);

            this.resizeContent();
            this.$('.evince-backbone-modal-content').attr('tabindex', '0').focus();

            // $( document.body ).trigger( 'init_tooltips' );

            $(document.body).trigger('evince_backbone_modal_loaded', this._target);
        },
        closeButton: function closeButton(e) {
            e.preventDefault();
            $(document.body).trigger('evince_backbone_modal_before_remove', this._target);
            this.undelegateEvents();
            $(document).off('focusin');
            $(document.body).css({
                'overflow': 'auto'
            });
            this.remove();
            $(document.body).trigger('evince_backbone_modal_removed', this._target);
        },

        addButton: function addButton(e) {
            $(document.body).trigger('evince_backbone_modal_response', [this._target, this.getFormData()]);
            this.closeButton(e);
        },

        sendAjax: function sendAjax(event) {
            var _this = this;

            event.preventDefault();

            var form = this.getFormData();
            var data = this._string;

            if (typeof form['reason_type'] === 'undefined') {
                this.closeButton(event);
                return;
            }

            $(event.target).prop('disabled', true).text($(event.target).data('deactivating')).next().addClass('visible');

            wp.ajax.send(form.action, {
                data: form,
                success: function success(response) {
                    window.location.replace(data.deactivate_link);
                    _this.closeButton(event);
                },
                error: function error() {
                    console.error('Deactivation Not logged.');
                    window.location.replace(data.deactivate_link);
                    _this.closeButton(event);
                }
            });
        },

        optionChange: function optionChange(event) {

            $(event.target).closest('.feedback-dialog-form-body').find('.feedback-text').prop('disabled', true).hide();

            $(event.target).nextAll('.feedback-text').prop('disabled', false).show().focus();
        },

        getFormData: function getFormData() {
            var data = {};

            $(document.body).trigger('evince_backbone_modal_before_update', this._target);

            $.each($('form', this.$el).serializeArray(), function (index, item) {
                if (item.name.indexOf('[]') !== -1) {
                    item.name = item.name.replace('[]', '');
                    data[item.name] = $.makeArray(data[item.name]);
                    data[item.name].push(item.value);
                } else {
                    data[item.name] = item.value;
                }
            });

            return data;
        },
        keyboardActions: function keyboardActions(e) {
            var button = e.keyCode || e.which;

            // Enter key
            if (13 === button && !(e.target.tagName && (e.target.tagName.toLowerCase() === 'input' || e.target.tagName.toLowerCase() === 'textarea'))) {
                this.addButton(e);
            }

            // ESC key
            if (27 === button) {
                this.closeButton(e);
            }
        }
    });
})(jQuery, Backbone, _);