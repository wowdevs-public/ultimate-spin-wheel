<?php
/**
 * Disposable Email Domains List
 *
 * A comprehensive list of known disposable/temporary email providers.
 * This file is auto-loaded by the is_disposable_email() method.
 *
 * @package UltimateSpinWheel
 * @since 1.0.28
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the list of disposable email domains
 *
 * @return array List of domain names (lowercase, without @ symbol)
 */
function uspw_get_disposable_email_domains() {
	return [
		// Popular disposable email services
		'mailinator.com',
		'mailinator.net',
		'mailinator.org',
		'guerrillamail.com',
		'guerrillamail.org',
		'guerrillamail.net',
		'guerrillamail.biz',
		'guerrillamail.de',
		'tempmail.com',
		'temp-mail.org',
		'temp-mail.io',
		'temp-mail.net',
		'10minutemail.com',
		'10minutemail.net',
		'10minutemail.org',
		'10minmail.com',
		'throwaway.email',
		'throwawaymail.com',

		// Yopmail network
		'yopmail.com',
		'yopmail.fr',
		'yopmail.net',
		'yopmail.org',

		// Maildrop & similar
		'maildrop.cc',
		'mailnesia.com',
		'fakeinbox.com',
		'fakemailbox.com',

		// Trash mail services
		'trashmail.com',
		'trashmail.org',
		'trashmail.net',
		'trashmail.ws',
		'mytrashmail.com',
		'trash-mail.com',
		'trash-mail.de',

		// Getnada & similar
		'getnada.com',
		'getnada.cc',
		'nada.email',

		// Dispostable & similar
		'dispostable.com',
		'disposableemailaddresses.com',
		'disposable.email',

		// Sharklasers network
		'sharklasers.com',
		'grr.la',
		'guerrillamailblock.com',
		'spam4.me',

		// Burner & temporary services
		'burnermail.io',
		'burnermailprovider.com',
		'tempr.email',
		'tempail.com',
		'tempmailaddress.com',
		'tempmails.net',
		'tmpmail.net',
		'tmpmail.org',
		'mail-temp.com',

		// Email on deck
		'emailondeck.com',
		'anonymousemail.me',

		// Minute inboxes
		'mintemail.com',
		'mytemp.email',
		'mohmal.com',
		'tempinbox.com',

		// Airmail & getair
		'getairmail.com',
		'inboxbear.com',

		// Discard & spam services
		'discard.email',
		'spamgourmet.com',
		'mailexpire.com',

		// Fake mail generators
		'fakemailgenerator.com',
		'emailfake.com',
		'fakemail.net',

		// Mail catch services
		'mailcatch.com',
		'mailsac.com',

		// Crazy mailing & similar
		'crazymailing.com',
		'anonbox.net',

		// International disposable services
		'correotemporal.org',
		'tempemailco.com',
		'jetable.org',

		// Bob mail & similar
		'bobmail.info',
		'harakirimail.com',

		// Anti-spam services
		'antespam.com',
		'spambox.info',
		'mailnull.com',

		// Incognito & anonymous
		'incognitomail.org',
		'nodezine.com',
		'receiveee.com',

		// Additional popular services
		'throwam.com',
		'spamherelots.com',
		'spamobox.com',
		'tempsky.com',
		'mailforspam.com',
		'spamfree24.org',
		'spamfree24.com',
		'spamfree24.de',
		'wegwerfmail.de',
		'wegwerfmail.net',
		'wegwerfmail.org',
		'20minutemail.com',
		'20minutemail.it',
		'mail22.space',
		'emailtemporanea.com',
		'emailtemporanea.net',
		'tempemail.net',
		'tempemail.com',
		'tempemail.biz',
		'fakeinbox.org',
		'fakeinbox.net',
		'fakeinbox.info',
		'mailna.co',
		'mailna.me',
		'mailna.biz',
		'dropmail.me',
		'emkei.cz',
		'ema-sofia.eu',
		'emailsensei.com',
		'emailwarden.com',
		'emailx.at.hm',
		'emz.net',
		'enterto.com',
		'ephemail.net',
		'ero-tube.org',
		'etranquil.com',
		'etranquil.net',
		'evopo.com',
		'explodemail.com',
		'express.net.ua',
		'eyepaste.com',
		'facebook-email.cf',
		'facebook-email.ga',
		'fakedemail.com',
		'fakeinformation.com',
		'fakemail.fr',
		'fastacura.com',
		'fastchevy.com',
		'fastchrysler.com',
		'filzmail.com',
		'fixmail.tk',
		'fizmail.com',
		'flyspam.com',
		'frapmail.com',
		'friendlymail.co.uk',
		'front14.org',
		'garliclife.com',
		'gehensiull.com',
		'ghosttexter.de',
		'gishpuppy.com',
		'goemailgo.com',
		'gorillaswithdirtyarmpits.com',
		'gotmail.com',
		'gotmail.net',
		'haltospam.com',
		'hatespam.org',
		'hidemail.de',
		'hidzz.com',
		'hochsitze.com',
		'hopemail.biz',
		'hotpop.com',
		'hulapla.de',
		'ieatspam.eu',
		'ieatspam.info',
		'ieh-mail.de',
		'ignoremail.com',
		'ihateyoualot.info',
		'imails.info',
		'imgof.com',
	];
}
