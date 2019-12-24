<?php

defined( 'ABSPATH' ) or exit;

?>
<div class="currency-select">
    <?php foreach( $currency as $c ) : ?>
	<div class="currency-item__wrap">
		<div class="currency-item <?php echo ( ! empty( $selected_currency['symbol'] ) && $c['symbol'] === $selected_currency['symbol'] ) ? 'selected' : ''; ?>" data-symbol="<?php echo $c['symbol']; ?>" >
			<div class="item__logo">
				<img src="<?php echo $c['logo']; ?>" alt="">
				<?php if( ! empty( $c['desc'] ) ) : ?>
                    <div class="item__desc">
						<?php echo $c['desc']; ?>
                    </div>
				<?php endif; ?>
			</div>
			<div class="item__text">
				<div class="item__price">
					<?php
                    $discount = ( intval($c['discount']) > 0) ? $c['discount'] : 0;
					$index = array_search( $c['symbol'], array_column( $exchanges, 'token' ) );
					$amount = $exchanges[$index]['amount'];
					$amount = $amount - ( $amount * ( $discount / 100 ) );
					echo number_format( $amount, 8 );
					?>
				</div>
				<div class="item__info">
					<div class="item__symbol">
						<?php echo $c['symbol']; ?>
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
