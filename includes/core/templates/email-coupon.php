<?php
/**
 * Email Coupon Template
 *
 * @package USPIN_WHEEL
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variables available:
 *
 * @var string $site_name
 * @var string $site_url
 * @var string $coupon_title
 * @var string $coupon_code
 */
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $site_name ); ?></title>
	<style>
		body {
			font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
			line-height: 1.6;
			color: #1f2937;
			margin: 0;
			padding: 0;
			background-color: #f3f4f6;
		}

		.wrapper {
			width: 100%;
			table-layout: fixed;
			background-color: #f3f4f6;
			padding-bottom: 40px;
		}

		.container {
			max-width: 600px;
			margin: 0 auto;
			background-color: #ffffff;
			border-radius: 16px;
			overflow: hidden;
			margin-top: 40px;
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
		}

		.header {
			background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
			padding: 40px 20px;
			text-align: center;
			color: #ffffff;
		}

		.header h1 {
			margin: 0;
			font-size: 24px;
			font-weight: 800;
			letter-spacing: -0.025em;
		}

		.content {
			padding: 40px 30px;
			text-align: center;
		}

		.badge {
			display: inline-block;
			padding: 4px 12px;
			background-color: #ecfdf5;
			color: #059669;
			border-radius: 9999px;
			font-size: 12px;
			font-weight: 700;
			text-transform: uppercase;
			margin-bottom: 16px;
		}

		.congrats {
			color: #111827;
			font-size: 32px;
			font-weight: 800;
			margin-bottom: 8px;
			line-height: 1.2;
		}

		.sub-text {
			color: #4b5563;
			font-size: 16px;
			margin-bottom: 32px;
		}

		/* Creative Coupon Box */
		.coupon-card {
			background-color: #fafafa;
			border: 2px solid #e5e7eb;
			border-radius: 12px;
			padding: 32px;
			margin: 32px 0;
			position: relative;
			background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
			background-size: 10px 10px;
		}

		.coupon-card::before,
		.coupon-card::after {
			content: '';
			position: absolute;
			top: 50%;
			width: 20px;
			height: 20px;
			background-color: #ffffff;
			border-radius: 50%;
			margin-top: -10px;
			border: 2px solid #e5e7eb;
		}

		.coupon-card::before {
			left: -12px;
			border-left-color: transparent;
		}

		.coupon-card::after {
			right: -12px;
			border-right-color: transparent;
		}

		.prize-label {
			font-size: 14px;
			color: #6b7280;
			margin-bottom: 4px;
			font-weight: 600;
		}

		.coupon-code {
			font-size: 42px;
			font-weight: 900;
			color: #4f46e5;
			letter-spacing: 4px;
			margin: 12px 0;
			font-family: 'Courier New', Courier, monospace;
		}

		.btn {
			display: inline-block;
			padding: 16px 32px;
			background-color: #4f46e5;
			color: #ffffff !important;
			text-decoration: none !important;
			border-radius: 12px;
			font-weight: 700;
			font-size: 16px;
			transition: all 0.2s;
			box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
		}

		.footer {
			text-align: center;
			color: #6b7280;
			font-size: 14px;
			padding: 32px 20px;
			background-color: #f9fafb;
			border-top: 1px solid #f3f4f6;
		}

		.footer p {
			margin: 4px 0;
		}

		.footer a {
			color: #4f46e5;
			text-decoration: none;
			font-weight: 600;
		}
	</style>
</head>

<body>
	<div class="wrapper">
		<div class="container">
			<div class="header">
				<h1><?php echo esc_html( $site_name ); ?></h1>
			</div>

			<div class="content">
				<div class="badge"><?php esc_html_e( 'Lucky Win', 'ultimate-spin-wheel' ); ?></div>
				<div class="congrats"><?php esc_html_e( 'You Won!', 'ultimate-spin-wheel' ); ?></div>
				<p class="sub-text">
					<?php
					/* translators: %s: Prize/coupon title */
					printf( esc_html__( 'Congratulations! You spinned the wheel and unlocked a special %s reward.', 'ultimate-spin-wheel' ), '<strong>' . esc_html( $coupon_title ) . '</strong>' );
					?>
				</p>

				<div class="coupon-card">
					<div class="prize-label"><?php esc_html_e( 'YOUR EXCLUSIVE CODE', 'ultimate-spin-wheel' ); ?></div>
					<div class="coupon-code"><?php echo esc_html( $coupon_code ); ?></div>
					<div style="font-size: 12px; color: #9ca3af; margin-top: 8px;">
						<?php esc_html_e( 'Apply this code at checkout to claim your discount.', 'ultimate-spin-wheel' ); ?>
					</div>
				</div>

				<div style="margin-top: 40px;">
					<a href="<?php echo esc_url( $site_url ); ?>"
						class="btn"><?php esc_html_e( 'REDEEM YOUR PRIZE', 'ultimate-spin-wheel' ); ?></a>
				</div>
			</div>

			<div class="footer">
				<?php /* translators: %s: Site name with link */ ?>
				<p><?php printf( esc_html__( 'Visit us at %s', 'ultimate-spin-wheel' ), '<a href="' . esc_url( $site_url ) . '">' . esc_html( $site_name ) . '</a>' ); ?>
				</p>
				<p style="margin-top: 16px; font-size: 12px; opacity: 0.7;">
					<?php
					/* translators: 1: Current year, 2: Site name */
					printf( esc_html__( '© %1$s %2$s. All rights reserved.', 'ultimate-spin-wheel' ), esc_html( gmdate( 'Y' ) ), esc_html( $site_name ) );
					?>
				</p>
			</div>
		</div>
	</div>
</body>

</html>