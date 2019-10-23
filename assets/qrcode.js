jQuery(function($) {
    var selectors = {
        wrapper: '[data-content-wrapper]',
        paymentData: '[data-payment-data]',
        redirectUrl: '[data-redirect-url]',
        countDown: '[data-count-down]'
    };

    var EDD_EZPay_QRCode = function() {
        this.wrapper = $(selectors.wrapper);
        this.paymentData = JSON.parse($(selectors.paymentData).text());
        this.redirectUrl = JSON.parse($(selectors.redirectUrl).text());
        this.success = this.success.bind(this);
        this.timeout = this.timeout.bind(this);

        var render = this.render.bind(this);
        var setTimeRemaining = this.setTimeRemaining.bind(this);
        var checkPaymentStatus = this.checkPaymentStatus.bind(this);

        render();
        setTimeRemaining();
        checkPaymentStatus();
    };

    EDD_EZPay_QRCode.prototype.render = function() {
        var self = this;
        var paymentDoc = self.paymentData._doc;
        var $content = $(
            "<p>You have left <span data-count-down></span> to scan this QR Code</p>" +
            "<p>" + paymentDoc.originCurrency + paymentDoc.originValue + " - " + (paymentDoc.value / Math.pow(10, paymentDoc.token.decimal)) + paymentDoc.currency + "</p>" +
            "<img src='" + self.paymentData.qr + "' />"
        );
        self.wrapper.empty();
        self.wrapper.append($content);
    };

    EDD_EZPay_QRCode.prototype.checkPaymentStatus = function() {
        var self = this;

        var data = {
            action: 'check_payment_status',
            paymentId: self.paymentData._doc.uoid
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

    EDD_EZPay_QRCode.prototype.setTimeRemaining = function() {
        var self = this;
        var timeLoop = setInterval(function() {
            var endTime = self.paymentData._doc.expiredTime;
            var t = self.getTimeRemaining(endTime);
            var countDown = self.wrapper.find(selectors.countDown);

            if(t.total < 0) {
                clearInterval(timeLoop);
                self.timeout();
            }

            countDown.text(
                t.days + ' day ' +
                t.hours + ' hours ' +
                t.minutes + ' minuties ' +
                t.seconds + ' seconds'
            );
        }, 1000);
    };

    EDD_EZPay_QRCode.prototype.getTimeRemaining = function(endTime) {
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

    EDD_EZPay_QRCode.prototype.success = function() {
        var self = this;

        var $content = $(
            "<p>Success. You will be redirect to success page in 3 seconds. If it does not, click " +
            "<a href='" + self.redirectUrl.success + "'>here</a>" +
            "</p>"
        );

        self.wrapper.empty();
        self.wrapper.append($content);

        setTimeout(function(){ window.location = self.redirectUrl.success; }, 3000);
    };

    EDD_EZPay_QRCode.prototype.timeout = function() {
        var self = this;

        var $content = $(
            "<p>Timeout. You will be redirect to checkout page in 3 seconds. If it does not, click " +
            "<a href='" + self.redirectUrl.timeout + "'>here</a>" +
            "</p>"
        );

        self.wrapper.empty();
        self.wrapper.append($content);

        setTimeout(function(){ window.location = self.redirectUrl.timeout; }, 3000);
    };

    new EDD_EZPay_QRCode();
});