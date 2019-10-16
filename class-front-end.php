<?php

class EDD_EZPay_Front_End
{
    public function init()
    {
        $this->add_action();
    }

    public function add_action()
    {
        add_action( 'edd_ezpay_cc_form', array( $this, 'remove_credit_card_form' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function enqueue_scripts()
    {
        wp_enqueue_style( "edd_ezpay_frontend_css", EZPAY_PLUGIN_URL . 'assets/frontend.css' );
        wp_enqueue_script( "edd_ezpay_frontend_js", EZPAY_PLUGIN_URL . 'assets/frontend.js', array('jquery'), '', FALSE );
    }

    public function remove_credit_card_form()
    {
        $priority_coin = $this->get_priority_coin();
        $other_coin = $this->get_other_coin();
        ob_start(); ?>
        <input type="hidden" name="edd_ezpay_currency_id" data-ezpay-currency value="undefined_nusd">
        <input type="hidden" name="edd_ezpay_currency_symbol" data-ezpay-currency-symbol value="nusd">
        <div id="edd-ezpay-select">
            <div class="selected-coin-row" style="display: none" data-selected-coin-row>
                <div class="selected-coin" data-selected-coin>
                    <img src="" data-coin-icon alt="" class="coin-icon">
                    <span data-coin-name></span>
                </div>
                <div>
                    <a href="" data-change-coin>Change</a>
                </div>
            </div>
            <div class="choose-coin-row" data-choose-coin-row>
                <div class="priority-coin-list" data-priority-coin-list>
                    <?php foreach( $priority_coin as $c ) : ?>
                        <button class="coin-priority edd-ezpay-btn" data-coin-item>
                            <img class="coin-icon" data-coin-icon data-icon-url="<?php echo $c->icon; ?>" src="<?php echo $c->icon; ?>" alt="<?php echo $c->name; ?>" />
                            <span data-coin-name><?php echo $c->name; ?></span>
                        </button>
                    <?php endforeach; ?>
                    <button data-more-button class="change-coin-btn edd-ezpay-btn">
                        <img class="coin-icon" src="https://icon-library.net/images/and-more-icon/and-more-icon-0.jpg" alt="more" />
                    </button>
                </div>
                <div class="other-coin-list" style="display: none" data-other-coin-list>
                    <input data-search-coin type="text" class="coin-search" />
                    <?php foreach( $other_coin as $c ) : ?>
                        <button class="coin-other edd-ezpay-btn" data-coin-item data-other-coin data-coin-name="<?php echo $c->name; ?>">
                            <img class="coin-icon" data-coin-icon data-icon-url="<?php echo $c->icon; ?>" src="<?php echo $c->icon; ?>" alt="<?php echo $c->name; ?>" />
                            <span data-coin-name><?php echo $c->name; ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php echo ob_get_clean();

        return false;
    }

    private function get_list_coin()
    {
        $json = file_get_contents(EZPAY_PLUGIN_PATH . 'assets/list-coin.json');
        $list = json_decode($json);

        return $list;
    }

    private function get_priority_coin()
    {
        return array_slice($this->get_list_coin(), 0, 5);
    }

    private function get_other_coin()
    {
        return array_slice($this->get_list_coin(), 5, -1);
    }
}