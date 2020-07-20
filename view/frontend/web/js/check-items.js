define(
    [
        'ko',
        'jquery',
        'uiComponent',
        'Magento_Customer/js/customer-data',
        'jquery/jquery.cookie',
        'mage/translate'
    ],
    function (ko, $, Component, customerData) {
        'use strict';

        return Component.extend({
            wishlist: customerData.get('wishlist')(),
            elmClass: 'in-wishlist',
            checkedWishlist: ko.observable(false),
            classCookie: parseInt($.cookie('wishlist_class')),


            initialize: function (config, node) {
                this._super();
                var nodeId = node.getAttribute('data-product-id'),
                    checkInt = null,
                    self = this;
                this.checker(this.wishlist, node);

                customerData.get('wishlist').subscribe(
                    function (items) {
                        setTimeout(
                            function () {
                                self.checker(customerData.get('wishlist')());
                            },
                            300
                        );
                    }
                );

                if (this.checkedWishlist() !== true) {
                    checkInt = setInterval(this.checker(customerData.get('wishlist')()), 500);
                }
                this.checkedWishlist.subscribe(
                    function () {
                        if (self.checkedWishlist() === true) {
                            clearInterval(checkInt);
                        }
                    }
                )

            },

            checker: function (wishlist) {
                if (typeof (wishlist.items) !== "undefined") {
                    if (wishlist.items.length === 0) {
                        this.checkedWishlist(false);
                    } else {
                        for (var i = 0; i < wishlist.items.length; i++) {
                            var product = wishlist.items[i];

                            var inWishlist = $('.to-wishlist[data-product-id="' + product.product + '"]'),
                                removeUrl = product.delete_item_params;

                            if (inWishlist.length) {
                                if (!inWishlist.hasClass(this.elmClass)) {
                                    if (this.classCookie === 1) {
                                        inWishlist.addClass(this.elmClass);
                                    }
                                    inWishlist.addClass('ajax-remove');
                                    inWishlist.attr('data-ajax-remove', removeUrl);
                                    inWishlist.attr('title', $.mage.__('Remove from wishlist'));
                                    inWishlist.children('span').text($.mage.__('Remove from wishlist'));
                                }
                            }

                            if (this.nodeId === product.product && this.node.classList.contains(this.elmClass) !== false) {
                                this.node.className += " " + elmClass;
                            }
                        }
                        this.checkedWishlist(true);
                    }
                }
            }
        });
    }
);
