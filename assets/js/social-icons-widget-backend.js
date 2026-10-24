// Custom Event Polyfill
(function () {

    if (typeof window.CustomEvent === "function") return false;

    function CustomEvent(event, params) {
        params = params || {bubbles: false, cancelable: false, detail: undefined};
        var evt = document.createEvent('CustomEvent');
        evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
        return evt;
    }

    CustomEvent.prototype = window.Event.prototype;

    window.CustomEvent = CustomEvent;
})();

(function ($, _, widgetData) {

    var VueInstance = function (selector, data) {
        data.instance['icons'] = widgetData;
        var defaultField = data['default_field'];

        new Vue({
            el: selector,
            data: data.instance,
            created: function () {
                defaultField.color_picker = this.global_color_picker;
                defaultField.color_picker_hover = this.global_color_picker_hover;
            },
            mounted: function () {
                this.$nextTick(function () {

                    //remove unused div, it is for ajax to work properly in customizer.
                    $(this.$el).find('.must-remove').remove();
                    //cancel enter default behavior to submit form
                    $(this.$el).on('keypress', function (e) {
                        if (e.keyCode == 13) {
                            e.preventDefault();
                        }
                    });

                    var $colorPickers = $(this.$el).find('.zoom-social-icons__field-color-picker');

                    $colorPickers.wpColorPicker({
                        palettes: true,
                        change: function () {
                            var that = this;
                            _.defer(function () {
                                var event = new CustomEvent('input', {bubbles: true, cancelable: true});
                                that.dispatchEvent(event);
                            }, 100);
                        }
                    });
                })
            },
            watch: {
                global_color_picker: function (newVal) {
                    _.each(this.fields, function (el) {
                        el.color_picker = newVal;
                    });

                    defaultField.color_picker = newVal;

                    this.$nextTick(function () {
                        $(this.$el).find('input:first').trigger('change');
                    });
                },
                global_color_picker_hover: function (newVal) {
                    _.each(this.fields, function (el) {
                        el.color_picker_hover = newVal;
                    });

                    defaultField.color_picker_hover = newVal;

                    this.$nextTick(function () {
                        $(this.$el).find('input:first').trigger('change');
                    });
                }
            },
            filters: {
                filterUrlScheme: function (uri) {

                    var schemas = {
                        'mailto': 'mail',
                        'viber': 'viber',
                        'skype': 'skype',
                        'tg': 'tg',
                        'tel': 'mobile',
                        'sms': 'comments',
                        'fax': 'fax',
                        'news': 'newspaper-o',
                        'feed': 'rss'
                    };

                    var domains = {
                        'feedburner.google.com': 'rss',
                        'ok.ru':'odnoklassniki',
                        't.me':'telegram',
                        'm.me':'messenger',
                        'mail.ru':'mailru',
                        'wa.me':'whatsapp',
                        'bsky.app': 'bluesky',
                        'news.google.com':'google-news',
                        'itunes.apple.com/us/podcast':'apple-podcasts',
                        'podcasts.apple.com':'apple-podcasts',
                        'podcasts.google.com':'google-podcasts',
                        'zen.yandex.com':'zen-yandex',
                        'zen.yandex.ru':'zen-yandex'
                    };

                    var domain = uri.domain() !== undefined ? uri.domain().split('.').shift() : uri.scheme();

                    var schemaHasIcon = _.findKey(schemas, function (val, key) {
                        return key === uri.scheme();
                    });

                    domain = schemaHasIcon !== undefined ? schemas[schemaHasIcon] : domain;

                    var domainHasIcon = _.findKey(domains, function (val, key) {
                        return key === uri.hostname();
                    });

                    return (domainHasIcon !== undefined ) ? domains[domainHasIcon] : domain;
                },
            },
            methods: {
                toggleExtraOptionsClass: function (key, $event) {
                    var showExtraOptions = this.fields[key].show_extra_options;
                    var classObject = {
                        'toggle-extra-options': true,
                        'dashicons': true,
                        'dashicons-arrow-down': !showExtraOptions,
                        'dashicons-arrow-up': showExtraOptions
                    };
                    return classObject;
                },

                onInputModal: function (color_picker, icon_kit, icon, color_picker_hover, key) {

                    this.fields[key].color_picker = color_picker;
                    this.fields[key].icon_kit = icon_kit;
                    this.fields[key].icon = icon;
                    this.fields[key].color_picker_hover = color_picker_hover;

                    this.$nextTick(function () {
                        $(this.$el).find('input:first').trigger('change');
                    });
                },
                toggleExtraOptions: function(key, $event){
                    this.fields[key].show_extra_options = ! this.fields[key].show_extra_options;
                },
                onUpdate: function () {
                    $(this.$el).find('input:first').trigger('change');

                },
                urlFieldNameHandler: function (fieldKey) {

                    var newValue = this.fields[fieldKey].url;

                    if (newValue.length == 0) {
                        return;
                    }

                    var uri = new URI(newValue);
                    uri = uri.is('absolute') ? uri : new URI({'hostname': newValue, 'protocol': 'http'});
                    var icon = this.$options.filters.filterUrlScheme(uri);
                    var that = this;
                    var filtered = {};

                    _.each(this.icons.icons, function (icons, key) {
                        filtered[key] = icons.filter(function (item) {
                            if (_.isObject(item)) {
                                return item.icon.indexOf(icon) > -1;
                            }
                        });
                    });

                    filtered = _.pick(filtered, function (value) {
                        return value.length;
                    });

                    if (!_.isEmpty(filtered)) {
                        var firstKey = _.has(filtered, 'socicon') ? 'socicon' : _.first(_.keys(filtered));
                        var first = filtered[firstKey].shift();
                        that.fields[fieldKey].icon = first.icon;
                        that.fields[fieldKey].icon_kit = firstKey;

                        if (first.color != undefined) {
                            that.fields[fieldKey].color_picker = first.color;
                            that.fields[fieldKey].color_picker_hover = first.color;
                        } else {
                            that.fields[fieldKey].color_picker = that.global_color_picker;
                            that.fields[fieldKey].color_picker_hover = that.global_color_picker_hover;
                        }
                    }
                },
                mouseoverIcon: function (key, $event) {
                    var $rule = this.icon_style == 'without-canvas' ? 'color' : 'backgroundColor';
                    $($event.target).css($rule, this.fields[key].color_picker_hover);
                },
                mouseleaveIcon: function (key, $event) {
                    var $rule = this.icon_style == 'without-canvas' ? 'color' : 'backgroundColor';
                    $($event.target).css($rule, this.fields[key].color_picker);
                },
                clickonIconHandler: function (key) {
                    this.fields[key].show_modal = true;
                    $('body').addClass('modal-open');

                    var $widgetkWrapper = $(this.$el).closest('.block-editor-block-list__block');

                    if ($widgetkWrapper.length) {
                        $widgetkWrapper.attr('data-is-social-icons-widget', true);
                    }
                },
                clickOnDeleteIconHandler: function (key) {

                    this.fields.splice(key, 1);

                    //trigger change event after delete.
                    this.$nextTick(function () {
                        $(this.$el).find('input:first').trigger('change');
                    });
                },
                openModal: function (key) {
                    this.fields[key].show_modal = true;
                    $('body').addClass('modal-open');
                },
                closeModal: function (key) {
                    this.fields[key].show_modal = false;
                    $('body').removeClass('modal-open');

                    var $widgetkWrapper = $(this.$el).closest('.block-editor-block-list__block');

                    if ($widgetkWrapper.length) {
                        $widgetkWrapper.removeAttr('data-is-social-icons-widget');

                    }
                },
                insertField: function (e) {
                    this.fields.push(_.clone(defaultField));

                    this.$nextTick(function () {
                        $(this.$el).find('.zoom-social-icons__field').eq(this.fields.length - 1).find('.zoom-social-icons__field-url').focus();
                    });
                },
                iconCanvasStyleLabel: function () {
                    var result = {};

                    if (this.icon_style == 'without-canvas') {
                        result['opacity'] = 0.6;
                    }

                    return result;
                },
                normalizeStyle: function (key) {
                    var returnObj = {};
                    var $rule = this.icon_style == 'without-canvas' ? 'color' : 'backgroundColor';

                    returnObj[$rule] = this.fields[key].color_picker;

                    if (this.icon_style === 'without-canvas') {
                        returnObj['backgroundColor'] = 'transparent';
                    }

                    if (this.icon_style === 'with-canvas') {
                        returnObj['color'] = '#fff';
                    }

                    return returnObj;
                }
            }
        });
    };


    $(document).ready(function () {

        Vue.component('modal', {
            template: '#tmpl-zoom-social-modal',
            props: ['color_picker', 'color_picker_hover', 'icon', 'icon_kit', 'icons', 'icon_canvas_style', 'icon_style', 'icon_categories', 'field_key'],
            data: function () {
                return {
                    modal_color_picker: this.color_picker,
                    modal_icon: this.icon,
                    modal_icon_kit: this.icon_kit,
                    searchIcons: '',
                    modal_icon_kit_category: 'all',
                    modal_color_picker_hover: this.color_picker_hover,
                    modal_field_key : this.field_key
                };
            },
            watch: {
                modal_icon_kit: function (newValue) {
                    this.modal_icon_kit_category = 'all';
                },
                searchIcons: function (newValue) {
                    if (newValue.length) {
                        this.modal_icon_kit_category = 'all';
                    }
                },
            },
            computed: {
                getIconCategories: function () {
                    return this.icon_categories[this.modal_icon_kit];
                },
                searchIconsLength: function () {
                    return this.searchIcons.length > 0;
                },
                filterBySocicons: function () {
                    var collector = {};
                    var that = this;

                    _.each(this.icons, function (icons, key) {
                        collector[key] = icons.filter(function (item) {
                            if (_.isObject(item)) {

                                var category = (that.modal_icon_kit_category === 'all' ? true : item.category.indexOf(that.modal_icon_kit_category) > -1);
                                return item.icon.indexOf(that.searchIcons) > -1 && category;
                            }

                            return item.indexOf(that.searchIcons) > -1;
                        });
                    });
                    return collector;
                }
            },
            methods: {
                clickOnIcon: function ($event) {
                    this.searchIcons = '';
                    this.modal_icon_kit = $event.target.dataset.kit;
                    this.modal_icon = $event.target.dataset.icon;
                    var $rule = this.icon_style == 'without-canvas' ? 'color' : 'backgroundColor';
                    $(this.$el).find('.zoom-social-icons__single-element').css($rule, this.modal_color_picker);
                },
                overOnIcon: function ($event) {
                    var $rule = this.icon_style == 'without-canvas' ? 'color' : 'backgroundColor';

                    $($event.target).css($rule, this.modal_color_picker_hover);
                },
                leaveOnIcon: function ($event) {
                    var $rule = this.icon_style == 'without-canvas' ? 'color' : 'backgroundColor';

                    $($event.target).css($rule, this.modal_color_picker);
                },
                saveModal: function () {
                    this.$emit('input', this.modal_color_picker, this.modal_icon_kit, this.modal_icon, this.modal_color_picker_hover, this.modal_field_key);
                    this.$emit('close');
                    $(this.$el).find('input:first').trigger('change');
                },
                normalizeStyle: function (icon, icon_kit) {
                    var returnObj = {};
                    var $rule = this.icon_style == 'without-canvas' ? 'color' : 'backgroundColor';

                    returnObj[$rule] = this.modal_color_picker;

                    if (this.icon_style === 'without-canvas') {
                        returnObj['backgroundColor'] = 'transparent';
                    }
                    if (this.icon_style === 'with-canvas') {
                        returnObj['color'] = '#fff';
                    }

                    return returnObj;
                }

            },
            created: function () {
                var that = this;
                window.addEventListener('keyup', function (e) {
                    that.$emit('keyup', e);
                });
            },
            mounted: function () {
                this.$nextTick(function () {
                    var $colorPickers = $(this.$el).find('.zoom-social-icons__field-color-picker');
                    var $scrollableDiv = $(this.$el).find('.modal-icons-wrapper');
                    $scrollableDiv.scrollTo('.zoom-social-icons__single-element.selected');

                    $colorPickers.wpColorPicker({
                        palettes: true,
                        change: function () {
                            var that = this;
                            _.defer(function () {
                                var event = new CustomEvent('input', {bubbles: false, cancelable: true});
                                that.dispatchEvent(event);
                            }, 100);
                        }
                    });
                })
            },
            updated: function () {
                this.$nextTick(function () {
                    var $scrollableDiv = $(this.$el).find('.modal-icons-wrapper');
                    $scrollableDiv.scrollTo('.zoom-social-icons__single-element.selected');
                });
            },
            filters: {
                capitalize: function (value) {
                    return value.charAt(0).toUpperCase() + value.slice(1);
                },
                spacify: function (value) {
                    return value.replace('-', ' ');
                },
                humanizeIconType: function (value) {
                    var $collector = {
                        'fa': 'Font Awesome',
                        'genericon': 'Genericons',
                        'academicons': 'Academicons',
                        'dashicons': 'Dashicons',
                        'socicon': 'WPZOOM Icons',
                        'fab' : 'Font Awesome Brands',
                        'far' : 'Font Awesome Regular',
                        'fas' : 'Font Awesome Solid',

                    };

                    return $collector[value];
                }
            }
        });


        $('.form-instance').each(function (index, instance) {
            var $instance = $(instance);
            var selector = ($instance.closest('.widget').attr('id') == undefined) ? $instance.attr('id') : $instance.closest('.widget').attr('id');
            var instanceData = $instance.data('instance');
            var validSelector = (typeof selector === 'string' || selector instanceof String) && selector.indexOf('__i__') === -1;

            if (validSelector && $instance.attr('data-instance') && !$instance.closest('.widget').hasClass('ui-draggable')) {
                VueInstance('#' + selector, instanceData);
            }
        });

        // hook for init js in Beaver builder.
        $('body').on('fl-builder.preview-init', function (e, previewObj) {

            var data = $(previewObj.elements.settings).find('.form-instance').data('instance');

            if (data) {
                $(previewObj.elements.settings).find('.form-instance').each(function (index, el) {
                    var selector = 'wpz-form-class-' + index;
                    $(this).addClass(selector);
                    VueInstance('.' + selector, data);
                });
            }
        });

        $(document).on('widget-added', function (e, instance) {
            e.preventDefault();

            var data = $(instance).find('.form-instance').data('instance');

            if (data) {
                var selector = '#' + data.id;
                VueInstance(selector, data);
            }
        });

        $(document).on('widget-updated', function (e, instance) {
            e.preventDefault();

            var data = $(instance).find('.form-instance').data('instance');

            if (data) {
                var selector = '#' + data.id;

                VueInstance(selector, data);
            }
        });

        $(document).on('panelsopen', function (e) {
            var dialog = $(e.target);

            if (!dialog.has('.zoom-social-icons__list')) return;

            var data = $(dialog).find('.form-instance').data('instance');

            if (data) {

                $('.form-instance').each(function (index, el) {
                    var selector = 'wpz-form-class-' + index;
                    $(this).addClass(selector);
                    VueInstance('.' + selector, data);
                    dialog.addClass('widget-content');
                });

            }
        });
    });

    $(window).on('elementor:init', function () {

        elementor.hooks.addAction('panel/widgets/wp-widget-zoom-social-icons-widget/controls/wp_widget/loaded', function (panel) {
            var $formInstance = $(panel.$el).find('.form-instance');
            var data = $formInstance.data('instance');

            if (data) {
                $formInstance.each(function (index, el) {
                    var selector = 'wpz-form-class-' + index;
                    $(this).addClass(selector);
                    VueInstance('.' + selector, data);
                });
            }
        });
    });
})(jQuery, _, zoom_social_widget_data);
