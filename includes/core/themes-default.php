<?php
/**
 * Default Theme Template
 *
 * @package USPIN_WHEEL
 * @author wowDevs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variables available from Spin_Wheel class:
 *
 * @var int $campaign_id
 * @var array $settings
 * @var array $coupons
 * @var array $custom_designs
 * @var array $form_settings    (from uspw_form_settings meta)
 * @var array $content_settings (from uspw_content_settings meta)
 */

// Helper for SVG copy icon
if ( ! function_exists( 'uspw_default_theme_svg_copy_icon' ) ) {
	function uspw_default_theme_svg_copy_icon() {
		?>
		<svg viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg">
			<path
				d="M48.186 92.137c0-8.392 6.49-14.89 16.264-14.89s29.827-.225 29.827-.225-.306-6.99-.306-15.88c0-8.888 7.954-14.96 17.49-14.96 9.538 0 56.786.401 61.422.401 4.636 0 8.397 1.719 13.594 5.67 5.196 3.953 13.052 10.56 16.942 14.962 3.89 4.402 5.532 6.972 5.532 10.604 0 3.633 0 76.856-.06 85.34-.059 8.485-7.877 14.757-17.134 14.881-9.257.124-29.135.124-29.135.124s.466 6.275.466 15.15-8.106 15.811-17.317 16.056c-9.21.245-71.944-.49-80.884-.245-8.94.245-16.975-6.794-16.975-15.422s.274-93.175.274-101.566zm16.734 3.946l-1.152 92.853a3.96 3.96 0 0 0 3.958 4.012l73.913.22a3.865 3.865 0 0 0 3.91-3.978l-.218-8.892a1.988 1.988 0 0 0-2.046-1.953s-21.866.64-31.767.293c-9.902-.348-16.672-6.807-16.675-15.516-.003-8.709.003-69.142.003-69.142a1.989 1.989 0 0 0-2.007-1.993l-23.871.082a4.077 4.077 0 0 0-4.048 4.014zm106.508-35.258c-1.666-1.45-3.016-.84-3.016 1.372v17.255c0 1.106.894 2.007 1.997 2.013l20.868.101c2.204.011 2.641-1.156.976-2.606l-20.825-18.135zm-57.606.847a2.002 2.002 0 0 0-2.02 1.988l-.626 96.291a2.968 2.968 0 0 0 2.978 2.997l75.2-.186a2.054 2.054 0 0 0 2.044-2.012l1.268-62.421a1.951 1.951 0 0 0-1.96-2.004s-26.172.042-30.783.042c-4.611 0-7.535-2.222-7.535-6.482S152.3 63.92 152.3 63.92a2.033 2.033 0 0 0-2.015-2.018l-36.464-.23z"
				stroke="currentColor" fill-rule="evenodd" />
		</svg>
		<?php
	}
}

// ─────────────────────────────────────────────────────────────
// FORM SETTINGS (from formSettings meta - matches FormControls.jsx)
// ─────────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────
// FORM SETTINGS (from formSettings meta - matches FormControls.jsx)
// ─────────────────────────────────────────────────────────────
$uspw_input_name  = $form_settings['inputName'] ?? [];
$uspw_input_email = $form_settings['inputEmail'] ?? [];
$uspw_input_phone = $form_settings['inputPhone'] ?? [];
$uspw_restrict    = $form_settings['restrictSpinPerUser'] ?? [];

// Field visibility & requirements
$uspw_show_name  = ( $uspw_input_name['enable'] ?? true ) !== false;
$uspw_show_email = ( $uspw_input_email['enable'] ?? true ) !== false;
$uspw_show_phone = ( $uspw_input_phone['enable'] ?? false ) === true;

$uspw_require_name  = $uspw_show_name && ( $uspw_input_name['required'] ?? true ) !== false;
$uspw_require_email = $uspw_show_email && ( $uspw_input_email['required'] ?? true ) !== false;
$uspw_require_phone = $uspw_show_phone && ( $uspw_input_phone['required'] ?? false ) === true;

// Field labels & placeholders
$uspw_name_label        = $uspw_input_name['label'] ?? 'Name';
$uspw_name_placeholder  = $uspw_input_name['placeholder'] ?? 'Enter your name';
$uspw_email_label       = $uspw_input_email['label'] ?? 'Email';
$uspw_email_placeholder = $uspw_input_email['placeholder'] ?? 'Enter your email';
$uspw_phone_label       = $uspw_input_phone['label'] ?? 'Phone';
$uspw_phone_placeholder = $uspw_input_phone['placeholder'] ?? 'Enter your phone number';

// ─────────────────────────────────────────────────────────────
// CONTENT SETTINGS (from contentSettings meta - matches ContentPanel.jsx)
// ─────────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────
// CONTENT SETTINGS (from contentSettings meta - matches ContentPanel.jsx)
// ─────────────────────────────────────────────────────────────
$uspw_form_content  = $content_settings['formContent'] ?? [];
$uspw_prize_content = $content_settings['prizeContent'] ?? [];

$uspw_form_title_text  = $uspw_form_content['title'] ?? "Let's try luck!";
$uspw_form_description = $uspw_form_content['description'] ?? 'Fill in your details below for a chance to win amazing prizes!';
$uspw_form_submit_text = $uspw_form_content['submitButton'] ?? 'Spin The Wheel';

$uspw_prize_won_title = $uspw_prize_content['winTitle'] ?? 'Congratulations!';
$uspw_prize_won_msg   = $uspw_prize_content['winMessage'] ?? 'You won a {{discount_label}} discount!';
$uspw_coupon_win_text = $uspw_prize_content['winButtonText'] ?? 'Start Shopping!';

$uspw_prize_lost_title = $uspw_prize_content['lostTitle'] ?? 'Better luck next time!';
$uspw_prize_lost_msg   = $uspw_prize_content['lostMessage'] ?? 'Oops! Looks like you didn\'t win this time.';
$uspw_coupon_lost_text = $uspw_prize_content['lostButtonText'] ?? 'Try Again';

// ─────────────────────────────────────────────────────────────
// BEHAVIOR SETTINGS (from behaviorSettings meta - matches SettingsPanel.jsx)
// ─────────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────
// BEHAVIOR SETTINGS (from behaviorSettings meta - matches SettingsPanel.jsx)
// ─────────────────────────────────────────────────────────────
$uspw_behavior_settings_raw = get_post_meta( $campaign_id, 'uspw_behavior_settings', true );
$uspw_behavior_settings     = is_string( $uspw_behavior_settings_raw ) ? json_decode( $uspw_behavior_settings_raw, true ) : $uspw_behavior_settings_raw;
$uspw_prize_delivery        = $uspw_behavior_settings['prizeDelivery'] ?? [];

$uspw_auto_apply   = $uspw_prize_delivery['autoApplyCoupon'] ?? false;
$uspw_redirect_url = $uspw_prize_delivery['redirectUrl'] ?? '';

// ─────────────────────────────────────────────────────────────
// DESIGN SETTINGS (Dynamic CSS Variables)
// ─────────────────────────────────────────────────────────────
echo \USPIN_WHEEL\Includes\Core\Style_Generator::generate_popup_variables( $campaign_id );

// Legacy design settings
$uspw_spin_button_text = $custom_designs['spinButton']['text'] ?? 'Go!';

// ─────────────────────────────────────────────────────────────
// ANTI-CHEAT / SPAM SETTINGS
// ─────────────────────────────────────────────────────────────
$uspw_already_played_message = $uspw_restrict['alreadyPlayedMessage'] ?? 'You have already played this campaign!';

// Pre-filled user data
$uspw_user_name  = $settings['user_name'] ?? '';
$uspw_user_email = $settings['email'] ?? '';

?>
<div class="modal micromodal-slide ultimate-spin-wheel" id="uspw-modal-<?php echo esc_attr( $campaign_id ); ?>"
	aria-hidden="true">
	<div class="modal__overlay" tabindex="-1">
		<div class="modal__container" role="dialog" aria-modal="true"
			aria-labelledby="modal-title-<?php echo esc_attr( $campaign_id ); ?>">
			<header class="modal__header">
				<a href="#" class="modal__close" aria-label="Close modal" data-micromodal-close></a>
			</header>
			<main class="modal__content" id="modal-content-<?php echo esc_attr( $campaign_id ); ?>">
				<div class="uspw-spin-wrap">
					<div id="uspw-wheel-<?php echo esc_attr( $campaign_id ); ?>"></div>
				</div>
				<div class="uspw-form-wrap">
					<div class="uspw-title"><?php echo wp_kses_post( $uspw_form_title_text ); ?></div>
					<div class="uspw-description"><?php echo wp_kses_post( $uspw_form_description ); ?></div>
					<form class="uspw-spin-form" method="post">
						<?php if ( $uspw_show_name ) : ?>
							<div class="uspw-form-group">
								<label for="uspw-name-<?php echo esc_attr( $campaign_id ); ?>" class="uspw-label"><?php echo wp_kses_post( $uspw_name_label ); ?></label>
								<input type="text" id="uspw-name-<?php echo esc_attr( $campaign_id ); ?>" name="name"
									class="uspw-input" placeholder="<?php echo esc_attr( $uspw_name_placeholder ); ?>"
									autocomplete="on" <?php echo $uspw_require_name ? 'required' : ''; ?>>
							</div>
						<?php endif; ?>
						<?php if ( $uspw_show_email ) : ?>
							<div class="uspw-form-group">
								<label for="uspw-email-<?php echo esc_attr( $campaign_id ); ?>" class="uspw-label"><?php echo wp_kses_post( $uspw_email_label ); ?></label>
								<input type="email" id="uspw-email-<?php echo esc_attr( $campaign_id ); ?>" name="email"
									class="uspw-input" placeholder="<?php echo esc_attr( $uspw_email_placeholder ); ?>"
									autocomplete="on" <?php echo $uspw_require_email ? 'required' : ''; ?>>
							</div>
						<?php endif; ?>
						<?php if ( $uspw_show_phone ) : ?>
							<div class="uspw-form-group">
								<label for="uspw-phone-<?php echo esc_attr( $campaign_id ); ?>" class="uspw-label"><?php echo wp_kses_post( $uspw_phone_label ); ?></label>
								<input type="tel" id="uspw-phone-<?php echo esc_attr( $campaign_id ); ?>" name="phone"
									class="uspw-input" placeholder="<?php echo esc_attr( $uspw_phone_placeholder ); ?>"
									<?php echo $uspw_require_phone ? 'required' : ''; ?>>
							</div>
						<?php endif; ?>

						<?php
						// GDPR Consent Checkbox (from global settings - passed from render_spin_wheel)
						$uspw_general_settings = $global_settings['general'] ?? [];
						$uspw_gdpr_enabled     = $uspw_general_settings['gdpr_enabled'] ?? false;
						$uspw_gdpr_label       = $uspw_general_settings['gdpr_label'] ?? 'I agree to the privacy policy and terms.';
						$uspw_gdpr_required    = $uspw_general_settings['gdpr_required'] ?? false;
						?>
						<?php if ( $uspw_gdpr_enabled ) : ?>
							<div class="uspw-form-group uspw-gdpr-group">
								<label class="uspw-gdpr-label">
									<input type="checkbox" id="uspw-gdpr-<?php echo esc_attr( $campaign_id ); ?>" name="gdpr_optin" value="1" class="uspw-gdpr-checkbox" <?php echo $uspw_gdpr_required ? 'required' : ''; ?>>
									<span class="uspw-gdpr-text"><?php echo wp_kses_post( $uspw_gdpr_label ); ?></span>
								</label>
							</div>
						<?php endif; ?>

						<button type="submit" class="uspw-spin"><?php echo wp_kses_post( $uspw_form_submit_text ); ?></button>
					</form>

					<div class="uspw-already-played-container" style="display:none;">
						<div class="uspw-already-played-icon">
							<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-500"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
						</div>
						<div class="uspw-already-played-title" id="uspw-already-played-title-<?php echo esc_attr( $campaign_id ); ?>"><?php echo esc_html__( 'Oops!', 'ultimate-spin-wheel' ); ?></div>
						<div class="uspw-already-played-msg" id="uspw-already-played-msg-<?php echo esc_attr( $campaign_id ); ?>"><?php echo wp_kses_post( $uspw_already_played_message ); ?></div>
					
						<!-- Countdown Timer -->
						<div class="uspw-countdown" id="uspw-countdown-<?php echo esc_attr( $campaign_id ); ?>" style="display:none;">
							<div class="uspw-countdown-title"><?php echo esc_html__( '⏱ Try again in', 'ultimate-spin-wheel' ); ?></div>
							<div class="uspw-countdown-boxes">
								<div class="uspw-countdown-box uspw-countdown-hours" style="display:none;">
									<div class="uspw-countdown-value" data-unit="hours">00</div>
									<div class="uspw-countdown-label"><?php echo esc_html__( 'Hours', 'ultimate-spin-wheel' ); ?></div>
								</div>
								<span class="uspw-countdown-separator uspw-countdown-hours-sep" style="display:none;">:</span>
								<div class="uspw-countdown-box">
									<div class="uspw-countdown-value" data-unit="minutes">00</div>
									<div class="uspw-countdown-label"><?php echo esc_html__( 'Mins', 'ultimate-spin-wheel' ); ?></div>
								</div>
								<span class="uspw-countdown-separator">:</span>
								<div class="uspw-countdown-box">
									<div class="uspw-countdown-value" data-unit="seconds">00</div>
									<div class="uspw-countdown-label"><?php echo esc_html__( 'Secs', 'ultimate-spin-wheel' ); ?></div>
								</div>
							</div>
						</div>
					
						<!-- Countdown Complete Message -->
						<div class="uspw-countdown-complete" id="uspw-countdown-complete-<?php echo esc_attr( $campaign_id ); ?>" style="display:none;">
							<span>✓ <?php echo esc_html__( 'You can spin again!', 'ultimate-spin-wheel' ); ?></span>
						</div>
					
						<!-- <button class="uspw-close-inline-btn" data-micromodal-close><?php //echo esc_html__( 'Close', 'ultimate-spin-wheel' ); ?></button> -->
					</div>
				</div>
			</main>
		</div>
	</div>
</div>

	<!-- Win Modal -->
	<div class="micromodal-slide uspw-result-modal uspw-win-modal" id="uspw-win-modal-<?php echo esc_attr( $campaign_id ); ?>"
		aria-hidden="true" 
		data-win-title="<?php echo esc_attr( $uspw_prize_won_title ); ?>"
		data-win-message="<?php echo esc_attr( $uspw_prize_won_msg ); ?>" 
		data-win-btn="<?php echo esc_attr( $uspw_coupon_win_text ); ?>"
		data-auto-apply="<?php echo esc_attr( $uspw_auto_apply ? 'true' : 'false' ); ?>"
		data-redirect-url="<?php echo esc_url( $uspw_redirect_url ); ?>">
			<div class="uspw-result-overlay" tabindex="-1" data-micromodal-close></div>
		<div class="uspw-result-container" role="dialog" aria-modal="true"
			aria-labelledby="uspw-win-heading-<?php echo esc_attr( $campaign_id ); ?>">
			<a href="javascript:void(0)" class="uspw-close-icon" aria-label="Close modal" data-micromodal-close></a>

			<div class="uspw-result-header" id="uspw-win-heading-<?php echo esc_attr( $campaign_id ); ?>">
				<?php echo wp_kses_post( $uspw_prize_won_title ); ?>
			</div>
			<div class="uspw-result-subheader" id="uspw-win-message-<?php echo esc_attr( $campaign_id ); ?>">
				<?php echo wp_kses_post( $uspw_prize_won_msg ); ?>
			</div>

			<div class="uspw-win-content" id="uspw-result-win-content-<?php echo esc_attr( $campaign_id ); ?>" style="display:none;">
				<div class="uspw-prize-highlight" id="uspw-result-label-<?php echo esc_attr( $campaign_id ); ?>"></div>
				<div class="uspw-coupon-box" id="uspw-coupon-box-<?php echo esc_attr( $campaign_id ); ?>">
					<div class="uspw-coupon-inner">
						<div class="uspw-coupon-code" id="uspw-result-code-<?php echo esc_attr( $campaign_id ); ?>"></div>
						<div class="uspw-copy-indicator">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
						</div>
					</div>
					<span class="uspw-coupon-label"><?php echo esc_html__( 'Click to Copy', 'ultimate-spin-wheel' ); ?></span>
				</div>
			</div>

			<button class="uspw-result-btn" id="uspw-win-btn-<?php echo esc_attr( $campaign_id ); ?>"
				><?php echo wp_kses_post( $uspw_coupon_win_text ); ?></button>

			<canvas id="uspw-confetti-canvas-<?php echo esc_attr( $campaign_id ); ?>" class="uspw-confetti-canvas"></canvas>
		</div>
	</div>
