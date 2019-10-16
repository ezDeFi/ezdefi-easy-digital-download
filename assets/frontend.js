jQuery(function($) {
    const selectors = {
        coinIcon: '[data-coin-icon]',
        coinItem: '[data-coin-item]',
        coinName: '[data-coin-name]',
        chooseCoinRow: '[data-choose-coin-row]',
        moreBtn: '[data-more-button]',
        otherCoinList: '[data-other-coin-list]',
        otherCoin: '[data-other-coin]',
        searchCoin: '[data-search-coin]',
        selectedCoinRow: '[data-selected-coin-row]',
        selectedCoin: '[data-selected-coin]',
        changeBtn: '[data-change-coin]',
        currencyInput: '[data-ezpay-currency]'
    };

    var EDD_EZPay_Frontend = function() {
        $(document.body)
            .on('click', selectors.moreBtn, this.showMoreCoin)
            .on('keyup', selectors.searchCoin, this.searchCoin)
            .on('click', selectors.coinItem, { eddEzpayFrontend: this }, this.selectCoin)
            .on('click', selectors.changeBtn, { eddEzpayFrontend: this }, this.changeCoin);
    };

    EDD_EZPay_Frontend.prototype.showMoreCoin = function(e) {
        e.preventDefault();
        $(selectors.otherCoinList).toggle();
    };

    EDD_EZPay_Frontend.prototype.searchCoin = function() {
        let term = $(this).val();

        $(selectors.otherCoin).each(function () {
            let coin_name = $(this).attr('data-coin-name').toLowerCase();
            if(coin_name.indexOf(term) == -1) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    };

    EDD_EZPay_Frontend.prototype.selectCoin = function(e) {
        e.preventDefault();
        e.data.eddEzpayFrontend.toggleCoinRow();

        const icon_url = $(this).find(selectors.coinIcon).attr('data-icon-url');
        const coin_name = $(this).find(selectors.coinName).text();

        $(selectors.selectedCoin).find(selectors.coinIcon).attr('src', icon_url);
        $(selectors.selectedCoin).find(selectors.coinName).text(coin_name);

        $(selectors.currencyInput).val(coin_name);
    };

    EDD_EZPay_Frontend.prototype.changeCoin = function(e) {
        e.preventDefault();
        e.data.eddEzpayFrontend.toggleCoinRow();
    };

    EDD_EZPay_Frontend.prototype.toggleCoinRow = function() {
        $(selectors.selectedCoinRow).toggle();
        $(selectors.chooseCoinRow).toggle();
    };

    new EDD_EZPay_Frontend();
})