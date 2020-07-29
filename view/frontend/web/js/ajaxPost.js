define([
    'jquery',
    'mage/template',
    'Magento_Ui/js/modal/confirm',
    'mage/url',
    'mage/cookies',
    'Magento_Ui/js/model/messageList',
    'Magento_Customer/js/customer-data',
    'jquery/jquery.cookie',
    'jquery/ui',
    'mage/translate'
], function ($, mageTemplate, uiConfirm, urlBuilder, cookies, globalMessageList, customerData) {
    'use strict';

    $.widget('codesicle.ajaxPost', {
        options: {
            formTemplate: '<form action="<%- data.action %>" method="post">' +
                '<% _.each(data.data, function(value, index) { %>' +
                '<input name="<%- index %>" value="<%- value %>">' +
                '<% }) %></form>',
            trigger: ['a[data-ajax-post]', 'button[data-ajax-post]', 'span[data-ajax-post]', 'a[data-ajax-remove-sidebar]'],
            formKeyInputSelector: 'input[name="form_key"]',
            classCookie: parseInt($.cookie('wishlist_class'))
        },

        /** @inheritdoc */
        _create: function () {
            this._bind();
            if ($.mage.cookies.get('wishlist-login') === '1') {
                var formKey = $.cookie("form-key"),
                    ajaxCookie = $.parseJSON($.cookie('wishlist-ajax')),
                    action = ajaxCookie.action,
                    ajaxData = ajaxCookie.params,
                    params = ajaxCookie.formParams,
                    url = 'no-redirect',
                    form = $(mageTemplate(this.options.formTemplate, {
                        data: params
                    }));

                this.wishlistRequest(action, ajaxData, url, this, form, null, null);
            }
        },

        /** @inheritdoc */
        _bind: function () {
            var events = {};

            $.each(this.options.trigger, function (index, value) {
                events['click ' + value] = '_postDataAction';
            });

            this._on(events);
        },

        /**
         * Handler for click.
         *
         * @param {Object} e
         */
        _postDataAction: function (e) {
            var target = $(e.currentTarget),
                params = target.data('ajax-post'),
                remove = target.data('ajax-remove');

            e.preventDefault();
            if (target.hasClass('ajax-remove')) {
                this.ajaxRemove(target, remove);
            } else {
                if ($(e.currentTarget).hasClass('ajax-action')) {
                    this.postAjax(params, target);
                } else {
                    return false;
                }
            }
        },

        /**
         *
         * Create the ajax data to be sent in the Add to wishlist function
         *
         * @param params
         * @param target
         */
        postAjax: function (params, target) {
            var $parent = this;
            var formKey = $.cookie("form-key"),
                $form,
                superAttr,
                referrer = window.btoa(window.location.href),
                url = urlBuilder.build("customer/account/login/" + "referer/" + referrer);
            if (formKey) {
                params.data['form_key'] = formKey;
            }

            $form = $(mageTemplate(this.options.formTemplate, {
                data: params
            }));

            if ($(target).closest('.product-item-info').length) {
                superAttr = $(target).closest('.product-item-info').find('.swatch-attribute').toArray();
            } else if ($(target).closest('.product-info-main').length) {
                superAttr = $(target).closest('.product-info-main').find('.swatch-attribute').toArray();
            }
            params.data.super_attribute = {};
            $(superAttr).each(function (index, listItem) {
                params.data.super_attribute[$(listItem).attr('attribute-id')] = $(listItem).attr('option-selected');
            });
            $form.appendTo('body').hide();
            $('body').trigger('processStart');
            if (params.data.confirmation) {
                uiConfirm({
                    content: params.data.confirmationMessage,
                    actions: {
                        /** @inheritdoc */
                        confirm: function () {
                            this.wishlistRequest($form.attr('action'), params.data, url, $parent, $form, params, target);
                        }
                    }
                });
            } else {
                this.wishlistRequest($form.attr('action'), params.data, url, $parent, $form, params, target);
            }
        },

        /**
         * Function to add class on clicked element and remove the created form when ajax is successfull
         *
         * @param $form
         * @param target
         * @param type
         */
        afterAjaxComplete: function ($form, target, type) {
            $form.remove();
            if (type === 'add') {
                customerData.reload('messages');
                if (target != null) {
                    target.addClass('ajax-remove');
                    if (this.options.classCookie === 1) {
                        target.addClass('in-wishlist');
                    }
                }
            } else {
                target.removeClass('in-wishlist ajax-remove');
                target.data('ajax-remove', '');
                target.attr('title', $.mage.__('Add to Wish List'));
                target.children('span').text($.mage.__('Add to Wish List'));
            }
        },

        /**
         * Wishlist to ajax request function
         *
         * @param action Ajax call "url"
         * @param ajaxData Ajax call "data"
         * @param url Url param used for page redirect if user is not logged-in
         * @param parent set "this" as parent variable to be able to do things outside ajax call
         * @param form this is the form created for Add to wishlist
         * @param params variable used to create needed cookies
         * @param target the "add to wishlist" clickable element
         */
        wishlistRequest: function (action, ajaxData, url, parent, form, params, target) {

            var messageContainer = messageContainer || globalMessageList;

            $.ajax({
                url: action,
                type: "POST",
                data: ajaxData,
                success: function () {
                }
            }).done(function (response) {

                if (response.hasOwnProperty('errors') && response.errors === false) {
                    parent.afterAjaxComplete(form, target, 'add');
                    parent.clearWishlistCookies();
                    $('body').trigger('processStop');
                    messageContainer.addSuccessMessage({message: response.message});
                    if (target) {
                        target.attr('title', $.mage.__('ほしいものリストから削除されます'));
                        target.children('span').text($.mage.__('ほしいものリストから削除されます'));
                        target.addClass('ajax-remove');
                    }
                    if (parent.options.classCookie === 1) {
                        if (target) {
                            target.addClass('in-wishlist');
                        }
                    }
                } else {
                    $('body').trigger('processStop');
                    messageContainer.addErrorMessage({message: response.message});
                }

                if (response.hasOwnProperty('redirect') && response.redirect === true) {
                    if (url != 'no-redirect') {
                        messageContainer.addErrorMessage({message: response.message});
                        parent.setWishlistCookies(action, ajaxData, form, params);
                        window.location.href = url;
                    }
                }
            })
        },

        /**
         *  Function to clear Codesicle Wishlist related cookies
         */
        clearWishlistCookies: function () {
            if (typeof $.cookie('wishlist-ajax') !== 'undefined') {
                $.cookie('wishlist-ajax', null);
            }
            if (typeof $.cookie('wishlist-login') !== 'undefined') {
                $.cookie('wishlist-login', null);
            }
        },

        /**
         *  Function to set Codesicle Wishlist related cookies
         */
        setWishlistCookies: function (action, params, form, formParams) {
            var wishlistConfig = {action: action, params: params, form: form, formParams: formParams};
            $.cookie('wishlist-ajax', JSON.stringify(wishlistConfig));
            $.cookie('wishlist-login', '1');
        },

        /**
         * Set custom message after action
         *
         * @param type
         * @param messageText
         */
        setCustomMessages: function (type, messageText) {
            var messages = customerData.get('messages');
            messages.subscribe(function (message) {
                if (message.messages.length === 0) {
                    customerData.set('messages', {
                        messages: [{
                            type: type,
                            text: messageText
                        }]
                    });
                }
            });
        },

        /**
         * Remove from wishlist with ajax
         *
         * @param params Params that are taken from the data-ajax-remove attr that will only be filled
         * after the product is in wishlist from customer-data wishlist
         * @param target Element that needs to be removed from wishlist
         */
        ajaxRemove: function (target, params) {
            var formKey = $.cookie("form-key"),
                url = params.action,
                $form,
                parent = this;

            $form = $(mageTemplate(this.options.formTemplate, {
                data: params
            }));

            if (formKey) {
                params.data['form_key'] = formKey;
            }

            var wishlist = customerData.get('wishlist')();
            if (wishlist.items) {
                var wishlistItems = wishlist.items;
                for (var i = 0; i < wishlistItems.length; i++) {
                    var product = wishlistItems[i];
                    if (parseInt(product.product) === parseInt(target.data('product-id'))) {
                        params = $.parseJSON(product.delete_item_params);
                        url = params.action;
                    }
                }
            }

            $form.appendTo('body').hide();
            $('body').trigger('processStart');
            $.ajax({
                url: url,
                type: "POST",
                data: params.data,
                cache: false,
                success: function () {
                }
            }).done(function (response) {
                if (response.hasOwnProperty('errors') && response.errors === false) {
                    parent.afterAjaxComplete($form, target, 'remove');
                    target.data('ajax-remove', '');
                    $('body').trigger('processStop');
                    parent.setCustomMessages('success', response.message);
                } else {
                    $('body').trigger('processStop');
                    parent.setCustomMessages('error', response.message);
                }
            });
            return false;
        }
    });


    $(document).ajaxPost();

    return $.codesicle.ajaxPost;
});
