jQuery(function($) {
    'use strict';

    var selectors = {
        amountIdCheckbox: 'input[name="edd_settings[ezdefi_method][amount_id]"]',
        symbolInput: '.currency-symbol',
        nameInput: '.currency-name',
        logoInput: '.currency-logo',
        descInput: '.currency-desc',
        walletInput: '.currency-wallet',
        decimalInput: '.currency-decimal',
        currencyTable: '#ezdefi-currency-table',
        currencySelect: '.select-select2',
        addBtn: '.addBtn',
        deleteBtn: '.deleteBtn',
        editBtn: '.editBtn',
        cancelBtn: '.cancelBtn',
        view: '.view',
        edit: '.edit',
        tip: '.help-tip'
    };

    var EDD_EZDefi_Admin = function() {
        // this.$form = $('form');
        this.$table = $(selectors.currencyTable);
        this.$form = this.$table.closest('form');

        var addCurrency = this.addCurrency.bind(this);
        var removeCurrency = this.removeCurrency.bind(this);
        var toggleEdit = this.toggleEdit.bind(this);
        var checkWalletAddress = this.checkWalletAddress.bind(this);
        var toggleAmountSetting = this.toggleAmountSetting.bind(this);
        var onChangeDecimal = this.onChangeDecimal.bind(this);
        var onBlurDecimal = this.onBlurDecimal.bind(this);

        this.init.call(this);

        $(document.body)
            .on('click', selectors.editBtn, toggleEdit)
            .on('click', selectors.cancelBtn, toggleEdit)
            .on('click', selectors.addBtn, addCurrency)
            .on('click', selectors.deleteBtn, removeCurrency)
            .on('keyup', selectors.walletInput, checkWalletAddress)
            .on('change', selectors.paymentMethod, toggleAmountSetting)
            .on('focus', selectors.decimalInput, onChangeDecimal)
            .on('blur', selectors.decimalInput, onBlurDecimal);;
    };

    EDD_EZDefi_Admin.prototype.init = function() {
        var self = this;

        self.initValidation.call(this);
        self.initSort.call(this);
        self.initTiptip.call(this);
        self.toggleAmountSetting(this);

        this.$table.find('select').each(function() {
            self.initCurrencySelect($(this));
        });
    };

    EDD_EZDefi_Admin.prototype.initValidation = function() {
        var self = this;

        this.$form.validate({
            ignore: [],
            errorElement: 'span',
            errorClass: 'error',
            errorPlacement: function(error, element) {
                if(element.hasClass('select-select2')) {
                    error.insertAfter(element.closest('.edit').find('.select2-container'));
                } else {
                    error.appendTo(element.closest('td'));
                }
            },
            highlight: function(element) {
                $(element).closest('td').addClass('form-invalid');
            },
            unhighlight: function(element) {
                $(element).closest('td').removeClass('form-invalid');
            },
            rules: {
                'edd_settings[ezdefi_api_url]': {
                    required: true,
                    url: true
                },
                'edd_settings[ezdefi_api_key]': {
                    required: true
                },
                'edd_settings[ezdefi_acceptable_variation]': {
                    required: {
                        depends: function(element) {
                            return self.$form.find(selectors.paymentMethod + ':checked').val() != 'ezdefi_wallet';
                        }
                    }
                },
                'edd_settings[ezdefi_amount_decimals]': {
                    required: {
                        depends: function(element) {
                            return self.$form.find(selectors.paymentMethod + ':checked').val() != 'ezdefi_wallet';
                        }
                    }
                }
            }
        });

        this.$table.find('tbody tr').each(function() {
            var row = $(this);
            self.addValidationRule(row);
        });
    };

    EDD_EZDefi_Admin.prototype.initSort = function() {
        var self = this;
        this.$table.find('tbody').sortable({
            handle: '.sortable-handle span',
            stop: function() {
                $(this).find('tr').each(function (rowIndex) {
                    var row = $(this);
                    self.updateAttr(row, rowIndex)
                });
            }
        }).disableSelection();
    };

    EDD_EZDefi_Admin.prototype.initTiptip = function() {
        this.$table.find(selectors.tip).tipTip();
    };

    EDD_EZDefi_Admin.prototype.addValidationRule = function($row) {
        var self = this;
        $row.find('input, select').each(function() {
            var name = $(this).attr('name');

            if(name.indexOf('discount') > 0) {
                $('input[name="'+name+'"]').rules('add', {
                    max: 100
                });
            }

            if(name.indexOf('select') > 0) {
                var $select = $('select[name="'+name+'"]');
                $select.rules('add', {
                    required: {
                        depends: function(element) {
                            return self.$form.find('.ezdefi_api_url input').val() !== '';
                        },
                    },
                    messages: {
                        required: 'Please select currency'
                    }
                });
                $select.on('select2:close', function () {
                    $(this).valid();
                });
            }

            if(name.indexOf('wallet') > 0) {
                var $input = $('input[name="'+name+'"]');
                $input.rules('add', {
                    required: true,
                    messages: {
                        required: 'Please enter wallet address'
                    }
                });
            }

            if(name.indexOf('decimal') > 0) {
                var $input = $('input[name="'+name+'"]');
                $input.rules('add', {
                    required: true,
                    min: 2,
                    messages: {
                        required: 'Please enter number of decimal',
                        min: 'Please enter number greater than 0'
                    }
                });
            }
        });
    };

    EDD_EZDefi_Admin.prototype.toggleAmountSetting = function() {
        var checked = this.$form.find(selectors.amountIdCheckbox).is(':checked');
        var amount_settings = this.$form.find('.acceptable_variation').closest('tr');
        if(checked) {
            amount_settings.each(function() {
                $(this).show();
            });
        } else {
            amount_settings.each(function() {
                $(this).hide();
            });
        }
    };

    EDD_EZDefi_Admin.prototype.checkWalletAddress = function(e) {
        var self = this;
        var api_url = self.$form.find('.ezdefi_api_url input').val();
        var api_key = self.$form.find('.ezdefi_api_key input').val();
        var $input = $(e.target);
        var $row = $(e.target).closest('tr');
        var currency_chain = $row.find('.currency-chain').val();
        var $checking = $(
            "<div class='checking'><span class='text'>Checking wallet address</span>" +
            "<div class='dots'>" +
            "<div class='dot'></div>" +
            "<div class='dot'></div>" +
            "<div class='dot'></div>" +
            "</div>" +
            "</div>"
        );
        $input.rules('add', {
            remote: {
                depends: function(element) {
                    return api_url !== '' && api_key !== '' && currency_chain !== '';
                },
                param: {
                    url: edd_ezdefi_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'edd_ezdefi_check_wallet',
                        address: function () {
                            return $input.val();
                        },
                        api_url: function() {
                            return api_url;
                        },
                        api_key: function() {
                            return api_key;
                        },
                        currency_chain: function() {
                            return currency_chain;
                        }
                    },
                    beforeSend: function() {
                        $input.closest('td').find('.error').hide();
                        $input.closest('.edit').append($checking);
                    },
                    complete: function (data) {
                        var response = data.responseText;
                        var $inputWrapper = $input.closest('td');
                        if (response === 'true') {
                            $inputWrapper.find('.checking').empty().append('<span class="correct">Correct</span>');
                            window.setTimeout(function () {
                                $inputWrapper.find('.checking').remove();
                            }, 1000);
                        } else {
                            $inputWrapper.find('.checking').remove();
                        }
                    }
                }
            },
            messages: {
                remote: "This address is not active. Please check again in <a href='http://163.172.170.35/profile/info'>your profile</a>."
            }
        });
    };

    EDD_EZDefi_Admin.prototype.initCurrencySelect = function(element) {
        var self = this;
        element.select2({
            width: '100%',
            ajax: {
                url: edd_ezdefi_data.ajax_url,
                type: 'POST',
                data: function(params) {
                    var query = {
                        action: 'edd_ezdefi_get_currency',
                        api_url: self.$form.find('.ezdefi_api_url input').val(),
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

    EDD_EZDefi_Admin.prototype.formatCurrencyOption = function(currency) {
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

    EDD_EZDefi_Admin.prototype.formatCurrencySelection = function(currency) {
        return currency.name || currency.text ;
    };

    EDD_EZDefi_Admin.prototype.toggleEdit = function(e) {
        e.preventDefault();

        var self = this;
        var $row = $(e.target).closest('tr');

        if($row.find(selectors.symbolInput).val() === '') {
            self.removeCurrency(e);
        } else {
            $row.toggleClass('editing');
        }
    };

    EDD_EZDefi_Admin.prototype.addCurrency = function(e) {
        e.preventDefault();

        var $row = this.$table.find('tbody tr:last');
        var $clone = $row.clone();
        var count = this.$table.find('tbody tr').length;
        var selectName = $clone.find('select').attr('name')
        var $select = $('<select name="'+selectName+'" class="select-select2"></select>');

        $clone.find('select, .select2-container').remove();
        $clone.find('.logo img').attr('src', '');
        $clone.find('.name .view span').remove();
        $clone.find('.name .edit').prepend($select);
        $clone.find('input').val('');
        $clone.find('td').each(function() {
            $(this).removeClass('form-invalid');
            $(this).find('.error').remove();
        });
        this.updateAttr($clone, count);
        this.removeAttr($clone);
        $clone.insertAfter($row);
        this.initCurrencySelect($select);
        this.addValidationRule($clone);
        $clone.addClass('editing');
        return false;
    };

    EDD_EZDefi_Admin.prototype.removeCurrency = function(e) {
        e.preventDefault();

        var self = this;

        if(confirm('Do you want to delete this row')) {
            $(e.target).closest('tr').remove();
            self.$table.find('tr').each(function (rowIndex) {
                $(this).find('.select2-container').remove();
                var $select = $(this).find('.select-select2');
                self.initCurrencySelect($select);

                if($(this).hasClass('editing')) {
                    var name = $(this).find('.currency-name').val();
                    $(this).find('.select2-selection__rendered').attr('title', name);
                    $(this).find('.select2-selection__rendered').text(name);
                }

                var row = $(this);
                var number = rowIndex - 1;
                self.updateAttr(row, number);
            });
        }
        return false;
    };

    EDD_EZDefi_Admin.prototype.onSelect2Select = function(e) {
        var td = $(e.target).closest('td');
        var tr = $(e.target).closest('tr');
        var data = e.params.data;
        td.find('.currency-symbol').val(data.symbol);
        td.find('.currency-name').val(data.name);
        td.find('.currency-logo').val(data.logo);
        td.find('.currency-chain').val(data.chain.network_type);
        if(data.description) {
            td.find('.currency-desc').val(data.description);
        } else {
            td.find('.currency-desc').val('');
        }
        tr.find('.logo img').attr('src', data.logo);
        td.find('.view span').text(data.name);
    };

    EDD_EZDefi_Admin.prototype.updateAttr = function(row, number) {
        row.find('input, select').each(function () {
            var name = $(this).attr('name');
            name = name.replace(/\[(\d+)\]/, '[' + parseInt(number) + ']');
            $(this).attr('name', name).attr('id', name);
        });
    };

    EDD_EZDefi_Admin.prototype.removeAttr = function(row) {
        row.find('input, select').each(function () {
            $(this).removeAttr('aria-describedby').removeAttr('aria-invalid');
        });
    };

    EDD_EZDefi_Admin.prototype.onChangeDecimal = function(e) {
        var input = $(e.target);
        if(input.val().length > 0) {
            var td = $(e.target).closest('td');
            td.find('.edit').append('<span class="error">Changing decimal can cause to payment interruption</span>');
        }
    };

    EDD_EZDefi_Admin.prototype.onBlurDecimal = function(e) {
        var td = $(e.target).closest('td');
        td.find('.edit').find('.error').remove();
    };

    new EDD_EZDefi_Admin();
});