jQuery(function($) {
    const $currencyTable = $('[data-currency-table]');

    const selectors = {
        currencyIdInput: '[data-id-input]',
        currencySymbolInput: '[data-symbol-input]',
        currencyNameInput: '[data-name-input]',
        currencyLogoInput: '[data-logo-input]',
        currencyTable: '[data-currency-table]',
        currencySelect: '[data-currency-select]',
        currencyWalletInput: '[data-currency-wallet]',
        templateRow: '[data-table-row-template]',
        templateBlankRow: '[data-blank-row-template]',
        blankRow: '[data-blank-row]',
        addBtn: '[data-add-btn]',
        deleteBtn: '[data-delete-btn]',
        editBtn: '[data-edit-btn]',
        cancelBtn: '[data-cancel-btn]',
        view: '[data-currency-view]',
        edit: '[data-currency-edit]'
    };

    var EDD_EZPay_Admin = function() {
        this.$currencyTable = $(selectors.currencyTable);

        var addCurrency = this.addCurrency.bind(this);
        var removeCurrency = this.removeCurrency.bind(this);
        var toggleEdit = this.toggleEdit.bind(this);
        var checkWallet = this.checkWallet.bind(this);

        $(document.body)
            .on('click', selectors.editBtn, toggleEdit)
            .on('click', selectors.cancelBtn, toggleEdit)
            .on('keyup', selectors.currencyWalletInput, checkWallet)
            .on('click', selectors.addBtn, addCurrency)
            .on('click', selectors.deleteBtn, removeCurrency);
    };

    EDD_EZPay_Admin.prototype.toggleEdit = function(e) {
        var self = this;

        e.preventDefault();

        var $row = $(e.target).closest('tr');
        if(typeof $row.data('editing-row') !== 'undefined') {
            self.removeCurrency(e);
        }

        var $view = $row.find(selectors.view);
        var $edit = $row.find(selectors.edit);
        var $select = $row.find(selectors.currencySelect);

        $view.toggle();
        $edit.toggle();

        if($edit.is(':visible')) {
            // self.initCurrencySelect.call(self, $select);
            // self.setCurrencySelectValue.call(self, $select);
        } else {
            $select.select2('destroy');
        }
    };

    EDD_EZPay_Admin.prototype.checkWallet = function(e) {
        setTimeout(function() {
            $.ajax({
                url: edd_ezpay_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'edd_ezpay_check_wallet',
                    address: e.target.value
                }
            }).done(function(data) {
                var status = data.toLowerCase();
                var inputWrapper = $(e.target).closest('div');
                inputWrapper.find('span').remove();

                if(status !== 'active') {
                    inputWrapper.append('<span class="error">This address is not active. Please check again</span>');
                }
            });
        }, 500);
    };

    EDD_EZPay_Admin.prototype.initCurrencySelect = function(element) {
        let self = this;
        element.select2({
            ajax: {
                url: 'http://163.172.170.35:3000/api/token/list',
                data: function(params) {
                    var query = {
                        keyword: params.term
                    };

                    return query;
                },
                processResults: function(data) {
                    return {
                        results: data.data
                    }
                },
                cache: true,
                dataType: 'json',
                delay: 250
            },
            placeholder: 'Select currency',
            minimumInputLength: 1,
            templateResult: self.formatCurrencyOption,
            templateSelection: self.formatCurrencySelection
        });
        element.on('select2:select', self.onSelect2Select);
    };

    EDD_EZPay_Admin.prototype.formatCurrencyOption = function(currency) {
        if(currency.loading) {
            return currency.text;
        }

        var $container = $(
            "<div class='select2-currency'>" +
            "<div class='select2-currency__icon'><img src='" + currency.logo + "' /></div>" +
            "<div class='select2-currency__name'>" + currency.name + "</div>" +
            "</div>"
        );

        return $container;
    };

    EDD_EZPay_Admin.prototype.formatCurrencySelection = function(currency) {
        return currency.name;
    };

    EDD_EZPay_Admin.prototype.addCurrency = function() {
        if($currencyTable.find(selectors.blankRow).length > 0) {
            $currencyTable.find('tbody').empty();
        }
        var html = $(selectors.templateRow).html()
        var $row = $(html);
        var count = $currencyTable.find('tbody tr').length;
        $select = $row.find(selectors.currencySelect);
        this.initCurrencySelect.call(this, $select);
        $row.find('input').each(function() {
            var name = $(this).attr('name');
            name = name.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');
            $(this).attr( 'name', name ).attr( 'id', name );
        });
        $row.find(selectors.view).hide();
        $row.find(selectors.edit).show();
        $currencyTable.find('tbody').append($row);
        return false;
    };

    EDD_EZPay_Admin.prototype.removeCurrency = function(e) {
        e.preventDefault();
        if(confirm('Do you want to delete this row')) {
            $(e.target).closest('tr').remove();

            if($currencyTable.find('tbody tr').length === 0) {
                var html = $(selectors.templateBlankRow).html()
                $currencyTable.find('tbody').append(html);
            } else {
                $currencyTable.find('tr').each(function (rowIndex) {
                    $(this).find('input').each(function () {
                        var name = $(this).attr('name');
                        name = name.replace(/\[(\d+)\]/, '[' + (rowIndex - 1) + ']');
                        $(this).attr('name', name).attr('id', name);
                    });
                });
            }
        }
        return false;
    };

    EDD_EZPay_Admin.prototype.onSelect2Select = function(e) {
        $(e.target).closest('td').find(selectors.currencyIdInput).val(e.params.data._id);
        $(e.target).closest('td').find(selectors.currencySymbolInput).val(e.params.data.symbol);
        $(e.target).closest('td').find(selectors.currencyNameInput).val(e.params.data.name);
        $(e.target).closest('td').find(selectors.currencyLogoInput).val(e.params.data.logo);
    };

    new EDD_EZPay_Admin();
});