<?php

defined( 'ABSPATH' ) or exit;

?>
<div class="currency-select">
    <?php foreach( $coins as $c ) : ?>
        <div class="currency-item__wrap">
            <div class="currency-item <?php echo ( ! empty( $selected_currency['_id'] ) && $c['_id'] === $selected_currency['_id'] ) ? 'selected' : ''; ?>" data-id="<?php echo $c['_id']; ?>" data-symbol="<?php echo $c['token']['symbol'] ;?>">
                <script type="application/json">
                            <?php
                    echo json_encode( array(
                        '_id' => $c['_id'],
                        'discount' => $c['discount'],
                        'wallet_address' => $c['walletAddress'],
                        'symbol' => $c['token']['symbol'],
                        'decimal' => $c['decimal']
                    ) )
                    ?>
                        </script>
                <div class="item__logo">
                    <img src="<?php echo $c['token']['logo']; ?>" alt="">
                    <?php if( ! empty( $c['token']['desc'] ) ) : ?>
                        <div class="item__desc">
                            <?php echo $c['token']['desc']; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="item__text">
                    <div class="item__price">
                        <?php
                        $discount = ( intval( $c['token']['discount']) > 0 ) ? $c['token']['discount'] : 0;
                        $index = array_search( $c['token']['symbol'], array_column( $exchanges, 'token' ) );
                        $amount = $exchanges[$index]['amount'];
                        $amount = $amount - ( $amount * ( $discount / 100 ) );
                        echo number_format( $amount, 8 );
                        ?>
                    </div>
                    <div class="item__info">
                        <div class="item__symbol">
                            <?php echo $c['token']['symbol']; ?>
                        </div>
                        <div class="item__discount">
                            - <?php echo $discount; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
