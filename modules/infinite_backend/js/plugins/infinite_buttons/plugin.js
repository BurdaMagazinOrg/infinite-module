(function () {

    CKEDITOR.plugins.add('infinite_buttons', {
            lang: 'en',
            requires: 'widget,dialog',
            icons: 'infinite_buttons',
            init: function (editor) {
                var lang = editor.lang.infinite_buttons;

                CKEDITOR.dialog.add('infinite_buttons', this.path + 'dialogs/infinite_buttons.js');

                // Add widget
                editor.ui.addButton('infinite_buttons', {
                    label: lang.buttonTitle,
                    command: 'infinite_buttons',
                    icon: this.path + 'icons/infinite_buttons.png'
                });

                editor.widgets.add('infinite_buttons', {
                    dialog: 'infinite_buttons',

                    init: function () {

                    },

                    template: '<a class="button">' + '<span class="text"></span>' + '</a>',

                    data: function () {
                        var $el = jQuery(this.element.$);

                        if (this.data.btntype) {
                            $el.removeClass('btn-link btn-default btn-primary btn-info btn-success btn-warning btn-danger').addClass(this.data.btntype);
                        }

                        if (this.data.btnsize) {
                            $el.removeClass('btn-xs btn-sm btn-lg').addClass(this.data.btnsize);
                        }

                        if (this.data.href) {
                            $el.attr('href', this.data.href);
                        }

                        if (this.data.target && this.data.target != '') {
                            $el.attr('target', this.data.target);
                        }

                        if (this.data.text) {
                            jQuery('.text', $el).text(this.data.text);
                        }

                        if (this.data.hasOwnProperty('bsiconleft')) {
                            jQuery('.bs-icon-left', $el).remove();
                            if (this.data.bsiconleft) {
                                $el.prepend('<span class="bs-icon-left glyphicon ' + this.data.bsiconleft + '"></span>');
                            }
                        }

                        if (this.data.hasOwnProperty('bsiconright')) {
                            jQuery('.bs-icon-right', $el).remove();
                            if (this.data.bsiconright) {
                                $el.append('<span class="bs-icon-right glyphicon ' + this.data.bsiconright + '"></span>');
                            }
                        }

                        if (this.data.hasOwnProperty('faiconleft')) {
                            jQuery('.fa-icon-left', $el).remove();
                            if (this.data.faiconleft) {
                                $el.prepend('<i class="fa fa-icon-left ' + this.data.faiconleft + '"></i>');
                            }
                        }

                        if (this.data.hasOwnProperty('faiconright')) {
                            jQuery('.fa-icon-right', $el).remove();
                            if (this.data.faiconright) {
                                $el.append('<i class="fa fa-icon-right ' + this.data.faiconright + '"></i>');
                            }
                        }
                    },

                    requiredContent: 'a(btn)',

                    upcast: function (element) {
                        return element.name == 'a' && element.hasClass('btn');
                    }
                });
            }
        }
    );

})();






