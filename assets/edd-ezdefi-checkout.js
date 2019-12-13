jQuery(function($) {
    'use strict';

    var selectors = {
        container: '#ezdefi-currency-select',
        input: '#edd_ezdefi_currency_symbol',
        item: '.currency-item',
        itemWrap: '.currency-item__wrap',
    };

    var EDD_Ezdefi_Checkout = function() {
        this.$container = $(selectors.container);

        var onSelectItem = this.onSelectItem.bind(this);

        $(document.body).on('click', selectors.itemWrap, onSelectItem);
    };

    EDD_Ezdefi_Checkout.prototype.onSelectItem = function(e) {
        $(selectors.item).removeClass('selected');

        var target = $(e.target);
        var selected;

        if(target.is(selectors.itemWrap)) {
            selected = target.find(selectors.item);
        } else {
            selected = target.closest(selectors.itemWrap).find(selectors.item);
        }

        selected.addClass('selected');

        var symbol = selected.attr('data-symbol');

        console.log(symbol);

        if(!symbol) {
            return false;
        }

        $(selectors.input).val(symbol);
    };

    new EDD_Ezdefi_Checkout();
});