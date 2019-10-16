jQuery(function($) {
    var selectors = {
        qrCodeWrapper: '[data-qrcode-wrapper]',
        paymentData: '[data-payment-data]',
        countDown: '[data-count-down]'
    };

    var EDD_EZPay_QRCode = function() {
        var check = this.check.bind(this);

        $(document).ready(function() {
            check();
        });
    };

    EDD_EZPay_QRCode.prototype.check = function() {
        var paymentData = JSON.parse($(selectors.paymentData).text());
        var countDown = $(selectors.countDown);

        this.checkPaymentStatus(paymentData.edd_payment_id);
        this.setTimeRemaining(countDown, paymentData.expiredTime);
    };

    EDD_EZPay_QRCode.prototype.checkPaymentStatus = function(id) {
        var data = {
            action: 'check_payment_status',
            paymentId: id
        };

        var checkPaymentLoop = setInterval(function() {
            $.post(ajax_object.ajax_url, data, function(response) {
                if(response == 'Complete') {
                    clearInterval(checkPaymentLoop);
                    alert('success');
                }
            });
        }, 3000);
    };

    EDD_EZPay_QRCode.prototype.setTimeRemaining = function(element, endTime) {
        var self = this;
        setInterval(function() {
            var t = self.getTimeRemaining(endTime);

            element.text(
                t.days + 'day' +
                t.hours + 'hours' +
                t.minutes + 'minuties' +
                t.seconds + 'seconds'
            );

            if(t < 0) {
                clearInterval();
                // clear form
            }
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

    new EDD_EZPay_QRCode();
});