jQuery(function($) {
    const selectors = {
        container: '#edd-ezpay-qrcode-section',
        changeBtn: '.changeBtn',
        select: '.currency-select',
        item: '.currency-item',
        selected: '.selected-currency',
        paymentData: '#payment-data',
        ezpayData: '#ezpay-payment',
        redirectUrl: '#redirect-url',
        submitBtn: '.submitBtn',
        ezpayPayment: '.ezpay-payment'
    };

    var EDD_EZPay_Checkout = function() {
        this.$container = $(selectors.container);
        this.redirectUrl = JSON.parse(this.$container.find(selectors.redirectUrl).text());

        var onChange = this.onChange.bind(this);
        var onSelectItem = this.onSelectItem.bind(this);
        var onSubmit = this.onSubmit.bind(this);

        var ezpayData = this.$container.find(selectors.ezpayData);

        if(ezpayData) {
            var data = JSON.parse(ezpayData.text());
            this.renderOutput(data);
        }

        $(document.body)
            .on('click', selectors.changeBtn, onChange)
            .on('click', selectors.item, onSelectItem)
            .on('click', selectors.submitBtn, onSubmit);
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
        // this.$container.find(selectors.select).hide();
    };

    EDD_EZPay_Checkout.prototype.onSubmit = function(e) {
        var self = this;
        var symbol = this.$container.find(selectors.selected).find('.symbol').text();
        if(!symbol) {
            return false;
        }
        var paymentData = JSON.parse(this.$container.find(selectors.paymentData).text());
        var data = {
            action: 'create_ezpay_payment',
            uoid: paymentData.uoid,
            symbol: symbol
        };
        this.$container.find(selectors.select).hide();
        self.$container.find(selectors.submitBtn).prop('disabled', true).text('Loading...');
        $.post(edd_ezpay_data.ajax_url, data, function(response) {
            self.$container.find(selectors.ezpayPayment).show();
            self.$container.find(selectors.submitBtn).hide();
            self.renderOutput.call(self, response.data.data);
        });
    };

    EDD_EZPay_Checkout.prototype.renderOutput = function(data) {
        var paymentData = JSON.parse(this.$container.find(selectors.paymentData).text());
        var paymentDoc = data._doc;
        var $content = $(
            "<p class='exchange'>" +
            "<span>" + paymentData.currency + " " + paymentData.subtotal + "</span>" +
            "<img width='16' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAQAAAAAYLlVAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QAAKqNIzIAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAAHdElNRQfjChgQMyxZjA7+AAACP0lEQVRo3u2YvWsUQRTAf8nFQs5LCEY0aCGIB1ErRVMoFpYGTGNlo2AnBxHlrLQJKVSwiV//gqCV4gemEGJhiBYXRAtBDIhICiUGL8GP3Fjs7rs5vN0o5M1LsW+a2XkDv9/MvF12t4B2dDDODqbVOan46zgaVKzwN3A4O4VuarGAo8EZC4VeXnoKJruQK+QKa12hI2VyFyUFhY08Ymfcd1S49feU7VSZ5DPL4qrXGpxuhW/iJj8DgJutTrGJ38vHoPCobUnwg9QN8HeTItzGNP2yF7M85D11lTvhLAPSn2CYpah7R5zmOUmnChrgsrf6p6xPhvfRiAe/slsNnoqHcRketsDDbDw8ZYPvlsR5CzwMSGpICT+WhYdBSR4Ov3p9gbGV8Hr3PEAPx6XvPXZC7sBm3qSvPoRApJCB71KB+jHHERbab34YAZjLSuoW4T+EuYBNHJXC32W+A2taYAN9lgJFHjDZfGsNHUWe4XC8VVHwirD9hBLPZcpM+mN0NQTaHUGR+xySq3vpj1Gd8FfvuKjCyDiC5OyjdklpkSeE0N+aCLF6gNGY8IuCBb4zfklxzFjg4ZRQRi3wB/guB1AOjV9HhUXh3Ibo87zEYw7KpFqUWPUoUWaIrXL9gf18iRSeGPyamGdPYlI2wL/zflPQx4+g8CWu0tN6OiNBwL/5xAQjXhWQFCFc4IqMvOYY3xSKcIHlrPQ5z/UVvSr3wQqRK+QKuYIfVU9hSuGt+L924ZoFvqmgji+kZl6wSI2qtsAfm/EoPAbFFD0AAAAldEVYdGRhdGU6Y3JlYXRlADIwMTktMTAtMjRUMTY6NTE6NDQrMDA6MDBiAik3AAAAJXRFWHRkYXRlOm1vZGlmeQAyMDE5LTEwLTI0VDE2OjUxOjQ0KzAwOjAwE1+RiwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAAASUVORK5CYII=' />" +
            "<span>" + (paymentDoc.value / Math.pow(10, paymentDoc.token.decimal)) + " " + paymentDoc.currency + "</span>" +
            "</p>" +
            "<p>You have <span class='count-down'></span> to scan this QR Code</p>" +
            "<img class='qrcode' src='" + data.qr + "' />" +
            "<a href=''>Download ezPay for IOS</a>" +
            "<a href=''>Download ezPay for Android</a>"
        );
        this.$container.find(selectors.ezpayPayment).empty().append($content);
        this.setTimeRemaining.call(this, paymentDoc.expiredTime);
        this.checkPaymentStatus.call(this, paymentDoc.uoid)
    };

    EDD_EZPay_Checkout.prototype.checkPaymentStatus = function(uoid) {
        var self = this;
        var data = {
            action: 'check_payment_status',
            paymentId: uoid
        };
        var checkPaymentLoop = setInterval(function() {
            $.post(edd_ezpay_data.ajax_url, data, function(response) {
                if(response == 'Complete') {
                    clearInterval(checkPaymentLoop);
                    self.success();
                }
            });
        }, 600);
    };

    EDD_EZPay_Checkout.prototype.setTimeRemaining = function(endTime) {
        var self = this;
        var timeLoop = setInterval(function() {
            var t = self.getTimeRemaining(endTime);
            var countDown = self.$container.find(selectors.ezpayPayment).find('.count-down');

            if(t.total < 0) {
                clearInterval(timeLoop);
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
            "<a href='" + self.redirectUrl.timeout + "'>here</a>" +
            "</p>"
        );

        self.$container.empty();
        self.$container.append($content);

        setTimeout(function(){ window.location = self.redirectUrl.timeout; }, 3000);
    };


    new EDD_EZPay_Checkout();
});