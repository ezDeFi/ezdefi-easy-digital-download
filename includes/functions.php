<?php

function edd_ezpay_generate_payment_html( $payment ) {
	ob_start(); ?>
	<div id="<?php echo ( $payment['amountId'] ) ? 'amount_id' : 'ezpay_wallet' ;?>" class="ezpay-payment-panel">
		<div class="ezpay-payment">
			<?php if( ! $payment ) : ?>
				<span><?php echo __( 'Can not get payment', 'woocommerce-gateway-ezpay' ); ?></span>
			<?php else : ?>
				<p class="exchange">
					<span><?php echo $payment['originCurrency']; ?> <?php echo $payment['originValue']; ?></span>
					<img width="16" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAQAAAAAYLlVAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QAAKqNIzIAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAAHdElNRQfjChgQMyxZjA7+AAACP0lEQVRo3u2YvWsUQRTAf8nFQs5LCEY0aCGIB1ErRVMoFpYGTGNlo2AnBxHlrLQJKVSwiV//gqCV4gemEGJhiBYXRAtBDIhICiUGL8GP3Fjs7rs5vN0o5M1LsW+a2XkDv9/MvF12t4B2dDDODqbVOan46zgaVKzwN3A4O4VuarGAo8EZC4VeXnoKJruQK+QKa12hI2VyFyUFhY08Ymfcd1S49feU7VSZ5DPL4qrXGpxuhW/iJj8DgJutTrGJ38vHoPCobUnwg9QN8HeTItzGNP2yF7M85D11lTvhLAPSn2CYpah7R5zmOUmnChrgsrf6p6xPhvfRiAe/slsNnoqHcRketsDDbDw8ZYPvlsR5CzwMSGpICT+WhYdBSR4Ov3p9gbGV8Hr3PEAPx6XvPXZC7sBm3qSvPoRApJCB71KB+jHHERbab34YAZjLSuoW4T+EuYBNHJXC32W+A2taYAN9lgJFHjDZfGsNHUWe4XC8VVHwirD9hBLPZcpM+mN0NQTaHUGR+xySq3vpj1Gd8FfvuKjCyDiC5OyjdklpkSeE0N+aCLF6gNGY8IuCBb4zfklxzFjg4ZRQRi3wB/guB1AOjV9HhUXh3Ibo87zEYw7KpFqUWPUoUWaIrXL9gf18iRSeGPyamGdPYlI2wL/zflPQx4+g8CWu0tN6OiNBwL/5xAQjXhWQFCFc4IqMvOYY3xSKcIHlrPQ5z/UVvSr3wQqRK+QKuYIfVU9hSuGt+L924ZoFvqmgji+kZl6wSI2qtsAfm/EoPAbFFD0AAAAldEVYdGRhdGU6Y3JlYXRlADIwMTktMTAtMjRUMTY6NTE6NDQrMDA6MDBiAik3AAAAJXRFWHRkYXRlOm1vZGlmeQAyMDE5LTEwLTI0VDE2OjUxOjQ0KzAwOjAwE1+RiwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAAASUVORK5CYII=" />
					<span><?php echo ( $payment['value'] / pow( 10, $payment['decimal'] ) ); ?> <?php echo $payment['currency']; ?></span>
				</p>
				<p>You have <span class="count-down" data-endtime="<?php echo $payment['expiredTime']; ?>"></span> to scan this QR Code</p>
				<p>
					<?php
					if( $payment['amountId'] === true ) {
						$deeplink = $payment['deepLink'];
					} else {
						$deeplink = 'ezpay://mobile';
						$params   = array(
							'to'    => $payment['to'],
							'token' => $payment['token']['address'],
							'value' => $payment['value'],
							'chain' => $payment['chain']['name'],
							'gas'   => $payment['gas']
						);
						$deeplink = sprintf( "%s?%s", $deeplink, http_build_query( $params ) );
					}
					?>
					<a href="<?php echo $deeplink; ?>">
						<img class="qrcode" src="<?php echo $payment['qr']; ?>" />
					</a>
				</p>
				<?php if( $payment['amountId'] === true ) : ?>
					<p>
						<strong>Address:</strong> <?php echo $payment['to']; ?><br/>
						<strong>Amount:</strong> <?php echo $payment['originValue']; ?><br/>
					</p>
					<p>You have to pay an exact amount so that you payment can be recognized.</p>
				<?php else : ?>
					<p>
						<a href="">Download ezPay for IOS</a>
						<a href="">Download ezPay for Android</a>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
	<?php return ob_get_clean();
}