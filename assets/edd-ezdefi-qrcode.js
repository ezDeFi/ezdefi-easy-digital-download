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
    qrcode: '.qrcode',
    changeQrcodeBtn: '.changeQrcodeBtn'
  };

  var EDD_EZDefi_Qrcode = function() {
    this.$container = $(selectors.container);
    this.$loader = this.$container.find(selectors.loader);
    this.$tabs = this.$container.find(selectors.tabs);
    this.$currencySelect = this.$container.find(selectors.select);
    this.paymentData = JSON.parse(this.$container.find(selectors.paymentData).text());

    var init = this.init.bind(this);
    var onSelectItem = this.onSelectItem.bind(this);
    var onClickEzdefiLink = this.onClickEzdefiLink.bind(this);
    var onUseAltQrcode = this.onUseAltQrcode.bind(this);
    var onClickQrcode = this.onClickQrcode.bind(this);

    init();

    $(document.body)
      .on('click', selectors.item, onSelectItem)
      .on('click', selectors.ezdefiEnableBtn, onClickEzdefiLink)
      .on('click', selectors.qrcode, onClickQrcode)
      .on('click', selectors.changeQrcodeBtn, onUseAltQrcode);
  };

  EDD_EZDefi_Qrcode.prototype.init = function() {
    var self = this;

    self.$tabs.tabs({
      activate: function(event, ui) {
        if (!ui.newPanel || ui.newPanel.is(':empty')) {
          self.createEzdefiPayment.call(self, ui.newPanel);
        }
        window.history.replaceState(null, null, ui.newPanel.selector);
      }
    });

    this.createEzdefiPayment.call(this);

    this.initClipboard.call(this);
  };

  EDD_EZDefi_Qrcode.prototype.initClipboard = function() {
    new ClipboardJS(selectors.copy).on('success', function(e) {
      var trigger = $(e.trigger)[0];
      trigger.classList.add('copied');
      setTimeout(function() {
        trigger.classList.remove('copied');
      }, 2000);
    });
  };

  EDD_EZDefi_Qrcode.prototype.onClickQrcode = function(e) {
    var self = this;
    var target = $(e.target);
    if (!target.hasClass('expired')) {
      return;
    } else {
      e.preventDefault();
      self.$currencySelect.find('.selected').click();
    }
  };

  EDD_EZDefi_Qrcode.prototype.createEzdefiPayment = function(panel = null) {
    var self = this;
    var active = panel ? panel : this.findActiveTab.call(this);
    var method = active.attr('id');
    var selectedCoin = this.$currencySelect.find('.selected');
    var coin_data = JSON.parse(selectedCoin.find('script[type="application/json"]').html());
    $.ajax({
      url: edd_ezdefi_data.ajax_url,
      method: 'post',
      data: {
        action: 'edd_ezdefi_create_payment',
        uoid: self.paymentData.uoid,
        coin_data: coin_data,
        method: method
      },
      beforeSend: function() {
        self.$tabs.hide();
        self.$currencySelect.hide();
        self.$loader.show();
      },
      success: function(response) {
        var html = response.success ? $(response.data) : response.data;
        active.html(html);
        self.setTimeRemaining.call(self, active);
        self.$loader.hide();
        self.$tabs.show();
        self.$currencySelect.show();
        self.checkPaymentStatus.call(self);
      }
    });
  };

  EDD_EZDefi_Qrcode.prototype.onSelectItem = function(e) {
    var selected = $(e.currentTarget);
    this.$currencySelect.find(selectors.item).removeClass('selected');
    selected.addClass('selected');
    this.$tabs.find(selectors.panel).empty();
    this.createEzdefiPayment.call(this);
  };

  EDD_EZDefi_Qrcode.prototype.onClickEzdefiLink = function(e) {
    e.preventDefault();
    self.$tabs.tabs('option', 'active', 1);
  };

  EDD_EZDefi_Qrcode.prototype.onUseAltQrcode = function(e) {
    var self = this;
    e.preventDefault();
    this.$tabs.find('#amount_id .qrcode img.main').toggle();
    this.$tabs.find('#amount_id .qrcode__info--main').toggle();
    this.$tabs.find('#amount_id .qrcode img.alt').toggle();
    this.$tabs.find('#amount_id .qrcode__info--alt').toggle();
  };

  EDD_EZDefi_Qrcode.prototype.checkPaymentStatus = function() {
    var self = this;
    $.ajax({
      url: edd_ezdefi_data.ajax_url,
      method: 'post',
      data: {
        action: 'edd_ezdefi_check_payment_status',
        paymentId: self.paymentData.uoid
      }
    }).done(function(response) {
      if (response == 'Complete') {
        self.success();
      } else {
        var checkPaymentStatus = self.checkPaymentStatus.bind(self);
        setTimeout(checkPaymentStatus, 600);
      }
    });
  };

  EDD_EZDefi_Qrcode.prototype.setTimeRemaining = function(panel) {
    var self = this;
    var timeLoop = setInterval(function() {
      var endTime = panel.find('.count-down').attr('data-endtime');
      var t = self.getTimeRemaining(endTime);
      var countDown = panel.find(selectors.ezdefiPayment).find('.count-down');

      if (t.total < 0) {
        clearInterval(timeLoop);
        countDown.text('0:0');
        self.timeout(panel);
      } else {
        countDown.text(t.text);
      }
    }, 1000);
  };

  EDD_EZDefi_Qrcode.prototype.getTimeRemaining = function(endTime) {
    var t = new Date(endTime).getTime() - new Date().getTime();
    var minutes = Math.floor(t / 60000);
    var seconds = ((t % 60000) / 1000).toFixed(0);
    return {
      total: t,
      text:
        seconds == 60 ? minutes + 1 + ':00' : minutes + ':' + (seconds < 10 ? '0' : '') + seconds
    };
  };

  EDD_EZDefi_Qrcode.prototype.success = function() {
    location.reload();
    return false;
  };

  EDD_EZDefi_Qrcode.prototype.timeout = function(panel) {
    panel.find('.qrcode').addClass('expired');
  };

  EDD_EZDefi_Qrcode.prototype.findActiveTab = function() {
    return this.$tabs.find('div.ui-tabs-panel[aria-hidden="false"]');
  };

  new EDD_EZDefi_Qrcode();
});
