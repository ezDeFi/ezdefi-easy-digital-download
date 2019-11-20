jQuery(function($) {
    const selectors = {
        container: '#edd-ezpay-qrcode-section',
        changeBtn: '.changeBtn',
        select: '.currency-select',
        item: '.currency-item',
        selected: '.selected-currency',
        paymentData: '#payment-data',
        submitBtn: '.submitBtn',
        ezpayPayment: '.ezpay-payment',
        tabs: '.ezpay-payment-tabs',
        panel: '.ezpay-payment-panel'
    };

    var EDD_EZPay_Checkout = function() {
        this.$container = $(selectors.container);
        this.$tabs = this.$container.find(selectors.tabs);
        this.$currencySelect = this.$container.find(selectors.select);
        this.$submitBtn = this.$container.find(selectors.submitBtn);
        this.paymentData = JSON.parse(this.$container.find(selectors.paymentData).text());

        var init = this.init.bind(this);
        var onChange = this.onChange.bind(this);
        var onSelectItem = this.onSelectItem.bind(this);
        var onSubmit = this.onSubmit.bind(this);

        init();

        $(document.body)
            .on('click', selectors.changeBtn, onChange)
            .on('click', selectors.item, onSelectItem)
            .on('click', selectors.submitBtn, onSubmit);
    };

    EDD_EZPay_Checkout.prototype.init = function() {
        var self = this;

        self.$tabs.tabs({
            activate: function(event, ui) {
                if(!ui.newPanel || ui.newPanel.is(':empty')) {
                    var method = ui.newPanel.attr('id');
                    self.getEzpayPayment.call(self, method, ui.newPanel);
                }
            }
        });

        var index = self.$tabs.tabs('option', 'active');
        var active = self.$tabs.find(selectors.panel + ':eq('+index+')');
        var method = active.attr('id');

        self.getEzpayPayment.call(self, method, active);
    };

    EDD_EZPay_Checkout.prototype.getEzpayPayment = function(method, panel) {
        var self = this;
        var symbol = this.$container.find(selectors.selected).find('.symbol').text();
        if(!symbol) {
            return false;
        }
        $.ajax({
            url: edd_ezpay_data.ajax_url,
            method: 'post',
            data: {
                action: 'edd_ezpay_get_payment',
                uoid: self.paymentData.uoid,
                symbol: symbol,
                method: method
            },
            beforeSend: function() {
                clearInterval(self.checkOrderLoop);
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

    EDD_EZPay_Checkout.prototype.onChange = function(e) {
        e.preventDefault();
        this.$currencySelect.toggle();
        this.$submitBtn.prop('disabled', false).text('Confirm').show();
        this.$tabs.hide();
    };

    EDD_EZPay_Checkout.prototype.onSelectItem = function(e) {
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

    EDD_EZPay_Checkout.prototype.onSubmit = function(e) {
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
            url: edd_ezpay_data.ajax_url,
            method: 'post',
            data: {
                action: 'edd_ezpay_create_payment',
                uoid: self.paymentData.uoid,
                symbol: symbol,
                method: method
            },
            beforeSend: function() {
                window.clearInterval(window.checkPaymentLoop);
                if(self.checkPaymentRequest) {
                    self.checkPaymentRequest.abort();
                }
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

    EDD_EZPay_Checkout.prototype.checkPaymentStatus = function(uoid) {
        var self = this;
        window.checkPaymentLoop = setInterval(function() {
            self.checkPaymentRequest = $.ajax({
                url: edd_ezpay_data.ajax_url,
                method: 'post',
                data: {
                    action: 'edd_ezpay_check_payment_status',
                    paymentId: self.paymentData.uoid
                },
                success: function( response ) {
                    if(response == 'Complete') {
                        window.clearInterval(window.checkPaymentLoop);
                        if(self.checkPaymentRequest) {
                            self.checkPaymentRequest.abort();
                        }
                        self.success();
                    }
                }
            });
        }, 600);
    };

    EDD_EZPay_Checkout.prototype.setTimeRemaining = function(endTime) {
        var self = this;
        clearInterval(self.timeLoop);
        self.timeLoop = setInterval(function() {
            var t = self.getTimeRemaining(endTime);
            var countDown = self.$container.find(selectors.ezpayPayment).find('.count-down');

            if(t.total < 0) {
                clearInterval(self.timeLoop);
                self.timeout();
            }

            countDown.text(
                t.days + ' d ' +
                t.hours + ' h ' +
                t.minutes + ' m ' +
                t.seconds + ' s'
            );
        }, 1000);
    };

    EDD_EZPay_Checkout.prototype.getTimeRemaining = function(endTime) {
        var t = new Date(endTime).getTime() - new Date().getTime();
        var days = Math.floor(t / (1000 * 60 * 60 * 24));
        var hours = Math.floor((t % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((t % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((t % (1000 * 60)) / 1000);
        return {
            'total': t,
            'days': days,
            'hours': hours,
            'minutes': minutes,
            'seconds': seconds
        };
    };

    EDD_EZPay_Checkout.prototype.success = function() {
        location.reload(true);
    };

    EDD_EZPay_Checkout.prototype.timeout = function() {
        var self = this;

        var $content = $(
            "<p>Timeout. You will be redirect to checkout page in 3 seconds. If it does not, click " +
            "<a href='" + edd_ezpay_data.checkout_url + "'>here</a>" +
            "</p>"
        );

        self.$container.empty();
        self.$container.append($content);

        setTimeout(function(){ window.location = edd_ezpay_data.checkout_url; }, 3000);
    };

    new EDD_EZPay_Checkout();
});