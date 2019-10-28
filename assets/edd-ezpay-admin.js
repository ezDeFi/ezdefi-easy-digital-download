jQuery(function($) {
    const selectors = {
        idInput: '.currency-id',
        symbolInput: '.currency-symbol',
        nameInput: '.currency-name',
        logoInput: '.currency-logo',
        descInput: '.currency-desc',
        walletInput: '.currency-wallet',
        currencyTable: '#ezpay-currency-table',
        currencySelect: '.currency-select',
        addBtn: '.addBtn',
        deleteBtn: '.deleteBtn',
        editBtn: '.editBtn',
        cancelBtn: '.cancelBtn',
        view: '.view',
        edit: '.edit',
        tip: '.help-tip'
    };

    var EDD_EZPay_Admin = function() {
        this.$form = $('form');
        this.$currencyTable = $(selectors.currencyTable);

        var addCurrency = this.addCurrency.bind(this);
        var removeCurrency = this.removeCurrency.bind(this);
        var toggleEdit = this.toggleEdit.bind(this);
        var checkWalletAddress = this.checkWalletAddress.bind(this);
        var initValidation = this.initValidation.bind(this);
        var initSort = this.initSort.bind(this);
        var initTiptip = this.initTiptip.bind(this);
        var initCurrencySelect = this.initCurrencySelect.bind(this);

        initValidation();
        initSort();
        initTiptip();

        this.$currencyTable.find('select').each(function() {
            initCurrencySelect($(this));
        });

        $(document.body)
            .on('click', selectors.editBtn, toggleEdit)
            .on('click', selectors.cancelBtn, toggleEdit)
            .on('click', selectors.addBtn, addCurrency)
            .on('click', selectors.deleteBtn, removeCurrency)
            .on('keyup', selectors.walletInput, checkWalletAddress);
    };

    EDD_EZPay_Admin.prototype.initValidation = function() {
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
                'edd_settings[ezpay_api_url]': {
                    required: true,
                    url: true
                },
                'edd_settings[ezpay_api_key]': {
                    required: true
                }
            },
            onkeyup: function(element) {
                setTimeout(function() {
                    $(element).valid();
                }, 300);
            }
        });

        this.$currencyTable.find('tbody tr').each(function() {
            var $row = $(this);
            self.addValidationRule($row);
        });
    };

    EDD_EZPay_Admin.prototype.initSort = function() {
        var self = this;
        this.$currencyTable.find('tbody').sortable({
            handle: '.sortable-handle span',
            stop: function() {
                self.updateNameAttr($(this));
            }
        }).disableSelection();
    };

    EDD_EZPay_Admin.prototype.initTiptip = function() {
        this.$currencyTable.find(selectors.tip).tipTip();
    };

    EDD_EZPay_Admin.prototype.addValidationRule = function($row) {
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
                            return self.$form.find('.ezpay_api_url input').val() !== '';
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
        });
    };

    EDD_EZPay_Admin.prototype.checkWalletAddress = function(e) {
        var self = this;
        var $input = $(e.target);
        $input.rules('add', {
            remote: {
                depends: function(element) {
                    return self.$form.find('.ezpay_api_url input').val() !== '' &&
                        self.$form.find('.ezpay_api_key input').val() !== '';
                },
                param: {
                    url: edd_ezpay_data.ajax_url,
                    type: 'POST',
                    beforeSend: function () {
                        $checking = $(
                            "<div class='checking'><span class='text'>Checking wallet address</span>" +
                            "<div class='dots'>" +
                            "<div class='dot'></div>" +
                            "<div class='dot'></div>" +
                            "<div class='dot'></div>" +
                            "</div>" +
                            "</div>"
                        );
                        $input.closest('td').find('.error').remove();
                        $input.closest('.edit').append($checking);
                    },
                    data: {
                        action: 'edd_ezpay_check_wallet',
                        address: function () {
                            return $input.val();
                        },
                        apiUrl: function() {
                            return self.$form.find('.ezpay_api_url input').val();
                        },
                        apiKey: function() {
                            return self.$form.find('.ezpay_api_key input').val();
                        },
                    },
                    complete: function (data) {
                        var response = data.responseText;
                        var $inputWrapper = $input.closest('.edit');
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

    EDD_EZPay_Admin.prototype.toggleEdit = function(e) {
        var self = this;
        e.preventDefault();
        var $row = $(e.target).closest('tr');
        if($row.find(selectors.symbolInput).val() === '') {
            self.removeCurrency(e);
        }
        $row.toggleClass('editing');
    };

    EDD_EZPay_Admin.prototype.initCurrencySelect = function(element) {
        var self = this;
        element.select2({
            width: '100%',
            ajax: {
                url: edd_ezpay_data.ajax_url,
                type: 'POST',
                data: function(params) {
                    var query = {
                        action: 'edd_ezpay_get_currency',
                        apiUrl: self.$form.find('.ezpay_api_url input').val(),
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
        return currency.name || currency.text ;
    };

    EDD_EZPay_Admin.prototype.addCurrency = function() {
        var $row = this.$currencyTable.find('tr:last');
        var $clone = $row.clone();
        var count = this.$currencyTable.find('tbody tr').length;
        var selectName = $clone.find('select').attr('name');
        $clone.find('select, .select2-container').remove();
        $select = $('<select name="'+selectName+'" class="select-select2"></select>');
        $clone.find('.logo img').attr('src', '');
        $clone.find('.name .view span').remove();
        $clone.find('.name .edit').prepend($select);
        $clone.find('input').val('');
        $clone.find('input, select').each(function() {
            var name = $(this).attr('name');
            name = name.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');
            $(this).attr( 'name', name ).attr( 'id', name );
        });
        $clone.insertAfter($row);
        this.initCurrencySelect($select);
        this.addValidationRule($clone);
        $clone.addClass('editing');
        return false;
    };

    EDD_EZPay_Admin.prototype.removeCurrency = function(e) {
        var self = this;
        e.preventDefault();
        if(confirm('Do you want to delete this row')) {
            $(e.target).closest('tr').remove();
            self.$currencyTable.find('tr').each(function (rowIndex) {
                $(this).find('.select2-container').remove();
                $select = $(this).find('.select-select2');
                self.initCurrencySelect($select);
                $(this).find('input, select').each(function () {
                    var name = $(this).attr('name');
                    name = name.replace(/\[(\d+)\]/, '[' + (rowIndex - 1) + ']');
                    $(this).attr('name', name).attr('id', name);
                });
            });
        }
        return false;
    };

    EDD_EZPay_Admin.prototype.onSelect2Select = function(e) {
        $(e.target).closest('td').find(selectors.idInput).val(e.params.data._id);
        $(e.target).closest('td').find(selectors.symbolInput).val(e.params.data.symbol);
        $(e.target).closest('td').find(selectors.nameInput).val(e.params.data.name);
        $(e.target).closest('td').find(selectors.logoInput).val(e.params.data.logo);
        if(e.params.data.description) {
            $(e.target).closest('td').find(selectors.descInput).val(e.params.data.description);
        } else {
            $(e.target).closest('td').find(selectors.descInput).val('');
        }
        $(e.target).closest('tr').find('.logo img').attr('src', e.params.data.logo);
        var $nameView = $(e.target).closest('td').find('.view');
        $nameView.find('span').text(e.params.data.name)
    };

    EDD_EZPay_Admin.prototype.updateNameAttr = function($tbody) {
        $tbody.find('tr').each(function (rowIndex) {
            $(this).find('input, select').each(function () {
                var name = $(this).attr('name');
                name = name.replace(/\[(\d+)\]/, '[' + (rowIndex) + ']');
                $(this).attr('name', name).attr('id', name);
            });
        });
    };

    new EDD_EZPay_Admin();
});