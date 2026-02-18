<?php

namespace USPIN_WHEEL\Includes\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Style_Generator
 *
 * Handles generation of dynamic CSS variables for themes.
 */
class Style_Generator {



	/**
	 * Get design settings with caching.
	 */
	public static function get_design_settings( $campaign_id ) {
		static $cache = [];
		if ( ! isset( $cache[ $campaign_id ] ) ) {
			$meta = get_post_meta( $campaign_id, 'uspw_design_settings', true );
			if ( is_string( $meta ) ) {
				$meta = json_decode( $meta, true );
			}
			$cache[ $campaign_id ] = is_array( $meta ) ? $meta : [];
		}
		return $cache[ $campaign_id ];
	}

	/**
	 * Generate CSS variables for the popup appearance.
	 */
	public static function generate_popup_variables( $campaign_id ) {
		$design_settings = self::get_design_settings( $campaign_id );
		$popup           = $design_settings['popup'] ?? [];

		$vars = [];

		// Layout & Size
		if ( ! empty( $popup['maxWidth'] ) ) {
			$vars[] = '--uspw-popup-max-width: ' . esc_attr( $popup['maxWidth'] ) . 'px;';
		}
		if ( ! empty( $popup['padding'] ) ) {
			$vars[] = '--uspw-popup-padding: ' . esc_attr( $popup['padding'] ) . 'px;';
		}
		if ( ! empty( $popup['borderRadius'] ) ) {
			$vars[] = '--uspw-popup-border-radius: ' . esc_attr( $popup['borderRadius'] ) . 'px;';
		}

		// Background (Priority: Image > Gradient > Color)
		if ( ! empty( $popup['backgroundImage'] ) ) {
			$vars[] = '--uspw-popup-bg: url(' . esc_url( $popup['backgroundImage'] ) . ') center/cover no-repeat;';
		} elseif ( ! empty( $popup['backgroundGradient'] ) && 'no' !== $popup['backgroundGradient'] ) {
			$vars[] = '--uspw-popup-bg: ' . $popup['backgroundGradient'] . ';'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( ! empty( $popup['backgroundColor'] ) ) {
			$vars[] = '--uspw-popup-bg: ' . esc_attr( $popup['backgroundColor'] ) . ';';
		} else {
			// Default fallback if nothing is set but we are generating variables
			$vars[] = '--uspw-popup-bg: #fff;';
		}

		// Close Button Styling
		if ( ! empty( $popup['closeButtonColor'] ) ) {
			$vars[] = '--uspw-close-color: ' . esc_attr( $popup['closeButtonColor'] ) . ';';
		}
		// Background (Priority: Gradient > Color)
		if ( ! empty( $popup['closeButtonBackgroundGradient'] ) && 'no' !== $popup['closeButtonBackgroundGradient'] ) {
			$vars[] = '--uspw-close-bg: ' . $popup['closeButtonBackgroundGradient'] . ';'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( ! empty( $popup['closeButtonBackgroundColor'] ) ) {
			$vars[] = '--uspw-close-bg: ' . esc_attr( $popup['closeButtonBackgroundColor'] ) . ';';
		}

		// Form Styling
		$form = $design_settings['form'] ?? [];

		// Form Title
		if ( ! empty( $form['title']['color'] ) ) {
			$vars[] = '--uspw-form-title-color: ' . esc_attr( $form['title']['color'] ) . ';';
		}
		if ( ! empty( $form['title']['fontSize'] ) ) {
			$vars[] = '--uspw-form-title-size: ' . esc_attr( $form['title']['fontSize'] ) . 'px;';
		}

		// Form Description
		if ( ! empty( $form['description']['color'] ) ) {
			$vars[] = '--uspw-form-desc-color: ' . esc_attr( $form['description']['color'] ) . ';';
		}
		if ( ! empty( $form['description']['fontSize'] ) ) {
			$vars[] = '--uspw-form-desc-size: ' . esc_attr( $form['description']['fontSize'] ) . 'px;';
		}

		// Form Inputs
		$input = $form['inputBox'] ?? [];
		if ( ! empty( $input['color'] ) ) {
			$vars[] = '--uspw-form-input-color: ' . esc_attr( $input['color'] ) . ';';
		}
		if ( ! empty( $input['placeholderColor'] ) ) {
			$vars[] = '--uspw-form-input-placeholder: ' . esc_attr( $input['placeholderColor'] ) . ';';
		}
		if ( ! empty( $input['backgroundColor'] ) ) {
			$vars[] = '--uspw-form-input-bg: ' . esc_attr( $input['backgroundColor'] ) . ';';
		}
		if ( ! empty( $input['borderColor'] ) ) {
			$vars[] = '--uspw-form-input-border: ' . esc_attr( $input['borderColor'] ) . ';';
		}
		if ( ! empty( $input['borderRadius'] ) ) {
			$vars[] = '--uspw-form-input-radius: ' . esc_attr( $input['borderRadius'] ) . 'px;';
		}
		if ( ! empty( $input['focusBorderColor'] ) ) {
			$vars[] = '--uspw-form-input-focus-border: ' . esc_attr( $input['focusBorderColor'] ) . ';';
		}
		if ( ! empty( $input['focusBackgroundColor'] ) ) {
			$vars[] = '--uspw-form-input-focus-bg: ' . esc_attr( $input['focusBackgroundColor'] ) . ';';
		}

		// Form Labels
		$label = $form['label'] ?? [];
		if ( ! empty( $label['color'] ) ) {
			$vars[] = '--uspw-form-label-color: ' . esc_attr( $label['color'] ) . ';';
		}
		if ( ! empty( $label['fontSize'] ) ) {
			$vars[] = '--uspw-form-label-size: ' . esc_attr( $label['fontSize'] ) . 'px;';
		}

		// Submit Button
		$btn = $form['submitButton'] ?? [];
		if ( ! empty( $btn['color'] ) ) {
			$vars[] = '--uspw-form-submit-color: ' . esc_attr( $btn['color'] ) . ';';
		}
		// Button Background (Priority: Gradient > Solid)
		if ( ! empty( $btn['backgroundGradient'] ) && 'no' !== $btn['backgroundGradient'] ) {
			$vars[] = '--uspw-form-submit-bg: ' . $btn['backgroundGradient'] . ';'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( ! empty( $btn['backgroundColor'] ) ) {
			$vars[] = '--uspw-form-submit-bg: ' . esc_attr( $btn['backgroundColor'] ) . ';';
		}

		// Button Hover Background
		if ( ! empty( $btn['hoverBackgroundGradient'] ) && $btn['hoverBackgroundGradient'] !== 'no' ) {
			$vars[] = '--uspw-form-submit-hover-bg: ' . esc_attr( $btn['hoverBackgroundGradient'] ) . ';';
		} elseif ( ! empty( $btn['hoverBackgroundColor'] ) ) {
			$vars[] = '--uspw-form-submit-hover-bg: ' . esc_attr( $btn['hoverBackgroundColor'] ) . ';';
		}

		if ( ! empty( $btn['padding'] ) ) {
			$vars[] = '--uspw-form-submit-padding: ' . esc_attr( $btn['padding'] ) . 'px;';
		}

		if ( isset( $btn['borderRadius'] ) ) {
			$vars[] = '--uspw-form-submit-radius: ' . esc_attr( $btn['borderRadius'] ) . 'px;';
		}

		if ( empty( $vars ) ) {
			return '';
		}

		return "<!-- Ultimate Spin Wheel Dynamic Styles -->\n        <style>\n            #uspw-modal-" . (int) $campaign_id . " {\n                " . implode( "\n                ", $vars ) . "\n            }\n        </style>";
	}
}
