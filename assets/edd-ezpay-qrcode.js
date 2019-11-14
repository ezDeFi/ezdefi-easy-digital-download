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
        var data = this.$container.find(selectors.paymentData).text();
        var paymentData = JSON.parse(data);
        var tabs = this.$container.find(selectors.tabs);
        // tabs.find(selectors.panel).each(function() {
        //     var panel = $(this);
        //     var method = $(this).attr('data-method');
        //     var paymentid = paymentData.paymentid[method];
        //     console.log(paymentid);
        //     self.getEzpayPayment.call(this, paymentid, panel);
        // });
        this.$container.find('.ezpay-payment-tabs').tabs();
    };

    EDD_EZPay_Checkout.prototype.renderOutput = function(data, panel) {
        var deeplink = 'ezpay://mobile?';
        var deeplink_params = {
            'to': data.to,
            'token': data.token.address,
            'value': data.value,
            'chain': data.chain,
            'gas': data.gas
        };
        deeplink = deeplink + $.param(deeplink_params);
        var $content = $(
            "<p class='exchange'>" +
            "<span>" + data.originCurrency + " " + data.originValue + "</span>" +
            "<img width='16' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAQAAAAAYLlVAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QAAKqNIzIAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAAHdElNRQfjChgQMyxZjA7+AAACP0lEQVRo3u2YvWsUQRTAf8nFQs5LCEY0aCGIB1ErRVMoFpYGTGNlo2AnBxHlrLQJKVSwiV//gqCV4gemEGJhiBYXRAtBDIhICiUGL8GP3Fjs7rs5vN0o5M1LsW+a2XkDv9/MvF12t4B2dDDODqbVOan46zgaVKzwN3A4O4VuarGAo8EZC4VeXnoKJruQK+QKa12hI2VyFyUFhY08Ymfcd1S49feU7VSZ5DPL4qrXGpxuhW/iJj8DgJutTrGJ38vHoPCobUnwg9QN8HeTItzGNP2yF7M85D11lTvhLAPSn2CYpah7R5zmOUmnChrgsrf6p6xPhvfRiAe/slsNnoqHcRketsDDbDw8ZYPvlsR5CzwMSGpICT+WhYdBSR4Ov3p9gbGV8Hr3PEAPx6XvPXZC7sBm3qSvPoRApJCB71KB+jHHERbab34YAZjLSuoW4T+EuYBNHJXC32W+A2taYAN9lgJFHjDZfGsNHUWe4XC8VVHwirD9hBLPZcpM+mN0NQTaHUGR+xySq3vpj1Gd8FfvuKjCyDiC5OyjdklpkSeE0N+aCLF6gNGY8IuCBb4zfklxzFjg4ZRQRi3wB/guB1AOjV9HhUXh3Ibo87zEYw7KpFqUWPUoUWaIrXL9gf18iRSeGPyamGdPYlI2wL/zflPQx4+g8CWu0tN6OiNBwL/5xAQjXhWQFCFc4IqMvOYY3xSKcIHlrPQ5z/UVvSr3wQqRK+QKuYIfVU9hSuGt+L924ZoFvqmgji+kZl6wSI2qtsAfm/EoPAbFFD0AAAAldEVYdGRhdGU6Y3JlYXRlADIwMTktMTAtMjRUMTY6NTE6NDQrMDA6MDBiAik3AAAAJXRFWHRkYXRlOm1vZGlmeQAyMDE5LTEwLTI0VDE2OjUxOjQ0KzAwOjAwE1+RiwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAAASUVORK5CYII=' />" +
            "<span>" + (data.value / Math.pow(10, data.decimal)) + " " + data.currency + "</span>" +
            "</p>" +
            "<p>You have <span class='count-down'></span> to scan this QR Code</p>" +
            "<p><a href='"+ deeplink + "'><img class='qrcode' src='" + data.qr + "' /></a></p>" +
            "<p><a href=''>Download ezPay for IOS</a><br />" +
            "<a href=''>Download ezPay for Android</a></p>"
        );
        panel.empty().append($content).show();
        this.setTimeRemaining.call(this, data.expiredTime);
        this.checkPaymentStatus.call(this, data.uoid);
    };

    EDD_EZPay_Checkout.prototype.getEzpayPayment = function(paymentid, panel) {
        var self = this;
        $.ajax({
            url: edd_ezpay_data.ajax_url,
            method: 'post',
            data: {
                action: 'edd_ezpay_get_payment',
                paymentid : paymentid
            },
            beforeSend: function() {
                if(self.checkPaymentLoop) {
                    clearInterval(self.checkPaymentLoop);
                }
                $.blockUI({message: null});
            },
            success:function(response) {
                var data = response.data;
                self.renderOutput(data, panel);
                $.unblockUI();
            },
            error: function(e) {
                console.log(e);
            }
        })
    };

    EDD_EZPay_Checkout.prototype.onChange = function(e) {
        e.preventDefault();
        this.$container.find(selectors.select).toggle();
        this.$container.find(selectors.submitBtn).prop('disabled', false).text('Confirm').show();
        this.$container.find(selectors.ezpayPayment).empty().hide();
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
        // this.$container.find(selectors.select).hide();
    };

    EDD_EZPay_Checkout.prototype.onSubmit = function(e) {
        var self = this;
        var symbol = this.$container.find(selectors.selected).find('.symbol').text();
        if(!symbol) {
            return false;
        }
        var paymentData = JSON.parse(this.$container.find(selectors.paymentData).text());
        this.$container.find(selectors.select).hide();
        self.$container.find(selectors.submitBtn).prop('disabled', true).text('Loading...');
        $.ajax({
            url: edd_ezpay_data.ajax_url,
            method: 'post',
            data: {
                action: 'edd_ezpay_create_payment',
                uoid: paymentData.uoid,
                symbol: symbol
            },
            beforeSend: function() {
                clearInterval(self.checkPaymentLoop);
                $.blockUI({message: null});
            },
            success:function(response) {
                var data = response.data;
                self.$container.find(selectors.ezpayPayment).show();
                self.$container.find(selectors.submitBtn).hide();
                self.renderOutput(data._doc, data._doc.token, data.qr);
                $.unblockUI();
            },
            error: function(e) {
                console.log(e);
            }
        });
    };

    EDD_EZPay_Checkout.prototype.checkPaymentStatus = function(uoid) {
        var self = this;
        self.checkPaymentLoop = setInterval(function() {
            $.ajax({
                url: edd_ezpay_data.ajax_url,
                method: 'post',
                data: {
                    action: 'edd_ezpay_check_payment_status',
                    paymentId: uoid
                },
                success: function( response ) {
                    if(response == 'Complete') {
                        clearInterval(self.checkPaymentLoop);
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
        location.reload(true)
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