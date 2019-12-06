jQuery(function($) {
    'use strict';

    var selectors = {
        table: '#edd-ezdefi-order-assign',
        select: '.order-select',
        amountIdInput: '#amount-id',
        currencyInput: '#currency',
        assignBtn: '.assignBtn',
        removeBtn: '.removeBtn'
    };

    var EDD_Ezdefi_Assign = function() {
        this.$table = $(selectors.table);
        this.$select = this.$table.find(selectors.select);
        this.$amountIdInput = this.$table.find(selectors.amountIdInput);
        this.$currencyInput = this.$table.find(selectors.currencyInput);

        var init = this.init.bind(this);
        var onAssign = this.onAssign.bind(this);
        var onRemove = this.onRemove.bind(this);

        init();

        $(this.$table)
            .on('click', selectors.assignBtn, onAssign)
            .on('click', selectors.removeBtn, onRemove)
    };

    EDD_Ezdefi_Assign.prototype.init = function() {
        var self = this;
        self.$table.find('tr').each(function() {
            var select = $(this).find(selectors.select);
            self.initOrderSelect.call(self, select);
        });
    };

    EDD_Ezdefi_Assign.prototype.initOrderSelect = function(element) {
        var self = this;
        element.select2({
            width: '100%',
            data: edd_ezdefi_data.orders,
            placeholder: 'Select Order',
            templateResult: self.formatOrderOption,
            templateSelection: self.formatOrderSelection,
            minimumResultsForSearch: -1
        });
    };

    EDD_Ezdefi_Assign.prototype.formatOrderOption = function(order) {
        var $container = $(
            "<div class='select2-order'>" +
            "<div class='select2-order__row'>" +
            "<div class='left'><strong>Order ID:</strong></div>" +
            "<div class='right'>" + order['id'] + "</div>" +
            "</div>" +
            "<div class='select2-order__row'>" +
            "<div class='left'><strong>Total:</strong></div>" +
            "<div class='right'>" + order['currency'] + " " + order['total'] + " ~ " + order['amount_id'] + " " + order['token'] + "</div>" +
            "</div>" +
            "<div class='select2-order__row'>" +
            "<div class='left'><strong>Billing Email:</strong></div>" +
            "<div class='right'>" + order['billing_email'] + "</div>" +
            "</div>" +
            "<div class='select2-order__row'>" +
            "<div class='left'><strong>Date created:</strong></div>" +
            "<div class='right'>" + order['date_created'] + "</div>" +
            "</div>" +
            "</div>"
        );
        return $container;
    };

    EDD_Ezdefi_Assign.prototype.formatOrderSelection = function(order) {
        return 'Order ID: ' + order['id'];
    };

    EDD_Ezdefi_Assign.prototype.onAssign = function(e) {
        e.preventDefault();
        var row = $(e.target).closest('tr');
        var order_id = this.$select.val();
        var amount_id = this.$amountIdInput.val();
        var currency = this.$currencyInput.val();
        var data = {
            action: 'edd_ezdefi_assign_amount_id',
            order_id: order_id,
            amount_id: amount_id,
            currency: currency
        };
        this.callAjax.call(this, data, row);
    };

    EDD_Ezdefi_Assign.prototype.onRemove = function(e) {
        e.preventDefault();
        if(!confirm('Do you want to delete this amount ID')) {
            return false;
        }
        var row = $(e.target).closest('tr');
        var amount_id = this.$amountIdInput.val();
        var currency = this.$currencyInput.val();
        var data = {
            action: 'edd_ezdefi_delete_amount_id',
            amount_id: amount_id,
            currency: currency
        };
        this.callAjax.call(this, data, row);
    };

    EDD_Ezdefi_Assign.prototype.callAjax = function(data, row) {
        var self = this;
        $.ajax({
            url: edd_ezdefi_data.ajax_url,
            method: 'post',
            data: data,
            beforeSend: function() {
                self.$table.block({message: 'Waiting...'});
            },
            success:function(response) {
                self.$table.unblock();
                row.remove();
            },
            error: function(e) {
                self.$table.block({message: 'Something wrong happend.'});
            }
        });
    };

    new EDD_Ezdefi_Assign();
});