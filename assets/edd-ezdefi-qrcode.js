jQuery(function($) {
    const selectors = {
        container: '#edd-ezdefi-qrcode-section',
        changeBtn: '.changeBtn',
        select: '.currency-select',
        item: '.currency-item',
        selected: '.selected-currency',
        paymentData: '#payment-data',
        submitBtn: '.submitBtn',
        ezdefiPayment: '.ezdefi-payment',
        tabs: '.ezdefi-payment-tabs',
        panel: '.ezdefi-payment-panel',
        ezdefiEnableBtn: '.ezdefiEnableBtn',
    };

    var EDD_EZDefi_Checkout = function() {
        this.$container = $(selectors.container);
        this.$tabs = this.$container.find(selectors.tabs);
        this.$currencySelect = this.$container.find(selectors.select);
        this.$submitBtn = this.$container.find(selectors.submitBtn);
        this.paymentData = JSON.parse(this.$container.find(selectors.paymentData).text());
        this.xhrPool = [];
        this.checkPaymentLoop;

        var init = this.init.bind(this);
        var onChange = this.onChange.bind(this);
        var onSelectItem = this.onSelectItem.bind(this);
        var onSubmit = this.onSubmit.bind(this);
        var onClickEzdefiLink = this.onClickEzdefiLink.bind(this);

        init();

        $(document.body)
            .on('click', selectors.changeBtn, onChange)
            .on('click', selectors.item, onSelectItem)
            .on('click', selectors.submitBtn, onSubmit)
            .on('click', selectors.ezdefiEnableBtn, onClickEzdefiLink);
    };

    EDD_EZDefi_Checkout.prototype.init = function() {
        var self = this;

        self.$tabs.tabs({
            activate: function(event, ui) {
                if(!ui.newPanel || ui.newPanel.is(':empty')) {
                    var method = ui.newPanel.attr('id');
                    self.getEzdefiPayment.call(self, method, ui.newPanel);
                }
            }
        });

        var index = self.$tabs.tabs('option', 'active');
        var active = self.$tabs.find(selectors.panel + ':eq('+index+')');
        var method = active.attr('id');

        self.getEzdefiPayment.call(self, method, active);
    };

    EDD_EZDefi_Checkout.prototype.getEzdefiPayment = function(method, panel) {
        var self = this;
        var symbol = this.$container.find(selectors.selected).find('.symbol').text();
        if(!symbol) {
            return false;
        }
        $.ajax({
            url: edd_ezdefi_data.ajax_url,
            method: 'post',
            data: {
                action: 'edd_ezdefi_get_payment',
                uoid: self.paymentData.uoid,
                symbol: symbol,
                method: method
            },
            beforeSend: function() {
                clearInterval(self.checkPaymentLoop);
                $.blockUI({message: null});
            },
            success:function(response) {
                if(response.success) {
                    panel.html($(response.data));
                } else {
                    panel.html(response.data);
                }
                var endTime = panel.find('.count-down').attr('data-endtime');
                self.setTimeRemaining.call(self, endTime);
                $.unblockUI();
                self.checkPaymentStatus.call(self);
            },
            error: function(e) {
                console.log(e);
            }
        });
    };

    EDD_EZDefi_Checkout.prototype.onChange = function(e) {
        e.preventDefault();
        this.$currencySelect.toggle();
        this.$submitBtn.prop('disabled', false).text('Confirm').show();
        this.$tabs.hide();
    };

    EDD_EZDefi_Checkout.prototype.onSelectItem = function(e) {
        var $item = $(e.target).closest(selectors.item);
        var $selected = this.$container.find(selectors.selected);

        $selected.find('.logo').attr('src', $item.find('.logo').attr('src'));
        $selected.find('.symbol').text($item.find('.symbol').text());
        $selected.find('.name').text($item.find('.name').text());

        var desc = $item.find('.desc');

        if(desc) {
            $selected.find('.desc').text($item.find('.desc').text());
        }
    };

    EDD_EZDefi_Checkout.prototype.onSubmit = function(e) {
        var self = this;
        var symbol = this.$container.find(selectors.selected).find('.symbol').text();
        if(!symbol) {
            return false;
        }
        var index = self.$tabs.tabs( "option", "active" );
        var active = self.$tabs.find(selectors.panel + ':eq('+index+')');
        var method = active.attr('id');
        self.$currencySelect.hide();
        self.$tabs.hide();
        self.$submitBtn.prop('disabled', true).text('Loading...');
        self.$tabs.find(selectors.panel).empty();
        $.ajax({
            url: edd_ezdefi_data.ajax_url,
            method: 'post',
            data: {
                action: 'edd_ezdefi_create_payment',
                uoid: self.paymentData.uoid,
                symbol: symbol,
                method: method
            },
            beforeSend: function() {
                clearInterval(self.checkPaymentLoop);
                $.each(self.xhrPool, function(index, jqXHR) {
                    jqXHR.abort();
                });
                $.blockUI({message: null});
            },
            success:function(response) {
                if(response.success) {
                    active.html($(response.data));
                } else {
                    active.html(response.data);
                }
                var endTime = active.find('.count-down').attr('data-endtime');
                self.setTimeRemaining.call(self, endTime);
                $.unblockUI();
                self.$tabs.show();
                self.$submitBtn.prop('disabled', false).text('Confirm').hide();
                self.checkPaymentStatus.call(self);
            },
            error: function(e) {
                console.log(e);
            }
        });
    };

    EDD_EZDefi_Checkout.prototype.onClickEzdefiLink = function(e) {
        var self = this;
        e.preventDefault();
        self.$tabs.tabs('option', 'active', 1);
    };

    EDD_EZDefi_Checkout.prototype.checkPaymentStatus = function() {
        var self = this;
        self.checkPaymentLoop = setInterval(function() {
            $.ajax({
                url: edd_ezdefi_data.ajax_url,
                method: 'post',
                data: {
                    action: 'edd_ezdefi_check_payment_status',
                    paymentId: self.paymentData.uoid
                },
                beforeSend: function(jqXHR) {
                    self.xhrPool.push(jqXHR);
                },
                success: function( response ) {
                    if(response == 'Complete') {
                        $.each(self.xhrPool, function(index, jqXHR) {
                            jqXHR.abort();
                        });
                        self.success();
                    }
                }
            });
        }, 600);
    };

    EDD_EZDefi_Checkout.prototype.setTimeRemaining = function(endTime) {
        var self = this;
        clearInterval(self.timeLoop);
        self.timeLoop = setInterval(function() {
            var t = self.getTimeRemaining(endTime);
            var countDown = self.$container.find(selectors.ezdefiPayment).find('.count-down');

            if(t.total < 0) {
                clearInterval(self.timeLoop);
                self.timeout();
            }

            countDown.text(t.minutes + ':' + t.seconds);
        }, 1000);
    };

    EDD_EZDefi_Checkout.prototype.getTimeRemaining = function(endTime) {
        var t = new Date(endTime).getTime() - new Date().getTime();
        var minutes = Math.floor((t / 60000));
        var seconds = (t % 60000 / 1000).toFixed(0);
        return {
            'total': t,
            'minutes': minutes,
            'seconds': seconds
        };
    };

    EDD_EZDefi_Checkout.prototype.success = function() {
        location.reload();
        return false;
    };

    EDD_EZDefi_Checkout.prototype.timeout = function() {
        var self = this;

        var $content = $(
            "<p>Timeout. You will be redirect to checkout page in 3 seconds. If it does not, click " +
            "<a href='" + edd_ezdefi_data.checkout_url + "'>here</a>" +
            "</p>"
        );

        self.$container.empty();
        self.$container.append($content);

        setTimeout(function(){ window.location = edd_ezdefi_data.checkout_url; }, 3000);
    };

    new EDD_EZDefi_Checkout();
});