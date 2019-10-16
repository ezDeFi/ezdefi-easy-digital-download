jQuery(function($) {
    const selectors = {
        tokenTable: '[data-token-table]',
        searchInput: '[data-search-input]',
        searchBtn: '[data-search-btn]',
        currencySelect: '[data-currency-select]',
        currencyTable: '[data-currency-table]',
        addCurrencyBtn: '[data-add-currency-btn]',
        removeCurrencyBtn: '[data-remove-currency-btn]'
    };

    var EDD_EZPay_Admin = function() {
        let initCurrencySelect = this.initCurrencySelect.bind(this);

        $(document).ready(function() {
            $select = $(selectors.currencySelect)
            if($select) {
                initCurrencySelect($select);
            }
        });

        var addCurrencyRow = this.addCurrencyRow.bind(this);
        var removeCurrencyRow = this.removeCurrencyRow;
        $(document.body)
            .on('click', selectors.searchBtn, { eddEzPayAdmin: this }, this.searchToken)
            .on('click', selectors.addCurrencyBtn, addCurrencyRow)
            .on('click', selectors.removeCurrencyBtn, removeCurrencyRow);
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
    }

    EDD_EZPay_Admin.prototype.addCurrencyRow = function() {
        var self = this;
        var table = $(selectors.currencyTable);
        var row = table.find('tr:last');
        var clone = row.clone();
        var count = row.parent().find('tr').length;
        var $select = $("<select name='edd_settings[ezpay_currency]["+parseInt(count)+"][id]'></select>");
        clone.find('td input' ).not(':input[type=checkbox]').val( '' );
        clone.find('.ezpay_currency_select').html($select);
        this.initCurrencySelect($select);
        clone.find('input, select').each(function() {
            var name = $(this).attr('name');
            name = name.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');
            $(this).attr( 'name', name ).attr( 'id', name );
        });
        clone.insertAfter(row);
        return false;
    };

    EDD_EZPay_Admin.prototype.removeCurrencyRow = function() {
        if(confirm('Do you want to delete this row')) {
            var table = $(selectors.currencyTable);
            var rows = table.find('tr:visible');
            var count = rows.length;

            if( count === 2 ) {
                table.find('input, select').val('');
            } else {
                console.log($(this));
                $(this).closest('tr').remove();
            }

            /* re-index after deleting */
            table.find('tr').each( function(rowIndex) {
                $(this).children().find('input, select').each(function() {
                    var name = $( this ).attr( 'name' );
                    name = name.replace( /\[(\d+)\]/, '[' + ( rowIndex - 1 ) + ']');
                    $(this).attr('name', name).attr('id', name);
                });
            });
        }
        return false;
    };

    EDD_EZPay_Admin.prototype.searchToken = function(e) {
        let keyword = $(selectors.searchInput).val();
        e.preventDefault();
        e.data.eddEzPayAdmin.getToken(10, keyword);
    };

    EDD_EZPay_Admin.prototype.getToken = function(limit, keyword) {
        $(selectors.tokenTable).find('tbody').html('<tr><td class="edd_ezpay_loading_td" colspan="8"><span class="edd_ezpay_loading"></span></td></tr>');

        if(!limit) {
            limit = 10;
        }

        let data = {
            action: 'get_ezpay_token',
            limit: limit
        }

        if(keyword && keyword.length > 0) {
            data['keyword'] = keyword;
        }

        $.post(ajax_object.ajax_url, data, function(response) {
            $(selectors.tokenTable).find('tbody').html(response);
        });
    };

    new EDD_EZPay_Admin();
});