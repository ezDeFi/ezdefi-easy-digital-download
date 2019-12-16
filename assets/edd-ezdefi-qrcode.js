jQuery(function($) {
    const selectors = {
        container: '#edd-ezdefi-qrcode-section',
        changeBtn: '.changeBtn',
        select: '.currency-select',
        itemWrap: '.currency-item__wrap',
        item: '.currency-item',
        selected: '.selected-currency',
        paymentData: '#payment-data',
        submitBtn: '.submitBtn',
        ezdefiPayment: '.ezdefi-payment',
        tabs: '.ezdefi-payment-tabs',
        panel: '.ezdefi-payment-panel',
        ezdefiEnableBtn: '.ezdefiEnableBtn',
        loader: '.edd-ezdefi-loader',
        copy: '.copy-to-clipboard',
        copyContent: '.copy-content',
        qrcode: '.qrcode'
    };

    var EDD_EZDefi_Qrcode = function() {
        this.$container = $(selectors.container);
        this.$loader = this.$container.find(selectors.loader);
        this.$tabs = this.$container.find(selectors.tabs);
        this.$currencySelect = this.$container.find(selectors.select);
        this.paymentData = JSON.parse(this.$container.find(selectors.paymentData).text());
        this.xhrPool = [];
        this.checkPaymentLoop;

        var init = this.init.bind(this);
        var onSelectItem = this.onSelectItem.bind(this);
        var onClickEzdefiLink = this.onClickEzdefiLink.bind(this);
        var onCopy = this.onCopy.bind(this);
        var onClickQrcode = this.onClickQrcode.bind(this);

        init();

        $(document.body)
            .on('click', selectors.item, onSelectItem)
            .on('click', selectors.ezdefiEnableBtn, onClickEzdefiLink)
            .on('click', selectors.ezdefiEnableBtn, onClickEzdefiLink)
            .on('click', selectors.qrcode, onClickQrcode)
            .on('click', selectors.copy, onCopy);
    };

    EDD_EZDefi_Qrcode.prototype.init = function() {
        var self = this;

        self.$tabs.tabs({
            activate: function(event, ui) {
                if(!ui.newPanel || ui.newPanel.is(':empty')) {
                    var method = ui.newPanel.attr('id');
                    self.getEzdefiPayment.call(self, method, ui.newPanel);
                }
                var url = ui.newTab.find('a').prop('href');
                if(url) {
                    location.href = url;
                }
            }
        });

        var active = self.$tabs.find('div.ui-tabs-panel[aria-hidden="false"]');
        var method = active.attr('id');

        self.getEzdefiPayment.call(self, method, active);
    };

    EDD_EZDefi_Qrcode.prototype.onCopy = function(e) {
        var target = $(e.target);
        var element;
        if(target.hasClass(selectors.copy)) {
            element = target;
        } else {
            element = target.closest(selectors.copy);
        }
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(element.find(selectors.copyContent).text()).select();
        document.execCommand("copy");
        $temp.remove();
        element.addClass('copied');
        setTimeout(function () {
            element.removeClass('copied');
        }, 2000);
    };

    EDD_EZDefi_Qrcode.prototype.onClickQrcode = function(e) {
        var self = this;
        var target = $(e.target);
        if(!target.hasClass('expired')) {
            return false;
        } else {
            e.preventDefault();
            self.$currencySelect.find('.selected').click();
        }
    };

    EDD_EZDefi_Qrcode.prototype.getEzdefiPayment = function(method, panel) {
        var self = this;
        var symbol = this.$currencySelect.find(selectors.item + '.selected').attr('data-symbol');
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
                $.each(self.xhrPool, function(index, jqXHR) {
                    jqXHR.abort();
                });
                self.$loader.show();
                self.$tabs.hide();
            },
            success:function(response) {
                if(response.success) {
                    panel.html($(response.data));
                } else {
                    panel.html(response.data);
                }
                self.setTimeRemaining.call(self, panel);
                self.$loader.hide();
                self.$tabs.show();
                self.checkPaymentStatus.call(self);
            },
            error: function(e) {
                console.log(e);
            }
        });
    };

    EDD_EZDefi_Qrcode.prototype.onSelectItem = function(e) {
        var self = this;
        this.$currencySelect.find(selectors.item).removeClass('selected');
        var target = $(e.target);
        var selected;
        if(target.is(selectors.itemWrap)) {
            selected = target.find(selectors.item).addClass('selected');
        } else {
            selected = target.closest(selectors.itemWrap).find(selectors.item).addClass('selected');
        }
        var symbol = selected.attr('data-symbol');
        if(!symbol) {
            return false;
        }
        var index = self.$tabs.tabs( "option", "active" );
        var active = self.$tabs.find(selectors.panel + ':eq('+index+')');
        var method = active.attr('id');
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
                self.$tabs.hide();
                self.$loader.show();
                clearInterval(self.checkOrderLoop);
                $.each(self.xhrPool, function(index, jqXHR) {
                    jqXHR.abort();
                });
            },
            success:function(response) {
                self.$tabs.find(selectors.panel).empty();
                if(response.success) {
                    active.html($(response.data));
                } else {
                    active.html(response.data);
                }
                self.setTimeRemaining.call(self, active);
                self.$loader.hide();
                self.$tabs.show();
                self.checkPaymentStatus.call(self);
            },
            error: function(e) {
                console.log(e);
            }
        });
    };

    EDD_EZDefi_Qrcode.prototype.onClickEzdefiLink = function(e) {
        var self = this;
        e.preventDefault();
        self.$tabs.tabs('option', 'active', 1);
    };

    EDD_EZDefi_Qrcode.prototype.checkPaymentStatus = function() {
        var self = this;
        // self.checkPaymentLoop = setInterval(function() {
        //     $.ajax({
        //         url: edd_ezdefi_data.ajax_url,
        //         method: 'post',
        //         data: {
        //             action: 'edd_ezdefi_check_payment_status',
        //             paymentId: self.paymentData.uoid
        //         },
        //         beforeSend: function(jqXHR) {
        //             self.xhrPool.push(jqXHR);
        //         },
        //         success: function( response ) {
        //             if(response == 'Complete') {
        //                 $.each(self.xhrPool, function(index, jqXHR) {
        //                     jqXHR.abort();
        //                 });
        //                 self.success();
        //             }
        //         }
        //     });
        // }, 600);
    };

    EDD_EZDefi_Qrcode.prototype.setTimeRemaining = function(panel) {
        var self = this;
        var timeLoop = setInterval(function() {
            var endTime = panel.find('.count-down').attr('data-endtime');
            var t = self.getTimeRemaining(endTime);
            var countDown = panel.find(selectors.ezdefiPayment).find('.count-down');

            if(t.total < 0) {
                clearInterval(timeLoop);
                countDown.text('0:0');
                self.timeout(panel);
            } else {
                countDown.text(t.minutes + ':' + t.seconds);
            }
        }, 1000);
    };

    EDD_EZDefi_Qrcode.prototype.getTimeRemaining = function(endTime) {
        var t = new Date(endTime).getTime() - new Date().getTime();
        var minutes = Math.floor((t / 60000));
        var seconds = (t % 60000 / 1000).toFixed(0);
        return {
            'total': t,
            'minutes': minutes,
            'seconds': seconds
        };
    };

    EDD_EZDefi_Qrcode.prototype.success = function() {
        location.reload();
        return false;
    };

    EDD_EZDefi_Qrcode.prototype.timeout = function(panel) {
        panel.find('.qrcode').addClass('expired');
    };

    new EDD_EZDefi_Qrcode();
});