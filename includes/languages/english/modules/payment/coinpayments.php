<?php
/**
 * sagepay form
 *
 * @package paymentMethod
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: zcwilt  Wed Jan 6 18:17:56 2016 +0000 New in v1.5.5 $
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

define('MODULE_PAYMENT_COINPAYMENTS_ADMIN_TEXT_TITLE', 'Coinpayments');
define('MODULE_PAYMENT_COINPAYMENTS_ADMIN_TEXT_DESCRIPTION', '<fieldset style="background: #eee; margin-bottom: 1.5em"><legend style="font-size: 1.2em; font-weight: bold">Description:</legend><br /> Pay with Bitcoin, Litecoin, or other altcoins via <a href="https://alpha.coinpayments.net/" target="_blank" style="text-decoration: underline; font-weight: bold;" title="CoinPayments.net">CoinPayments.net</a>');
define('MODULE_PAYMENT_COINPAYMENTS_CATALOG_TEXT_TITLE', 'Bitcoin/Litecoin/Other Payments');


define('MODULE_PAYMENT_COINPAYMENTS_TEXT_ERROR', 'Debit/Credit Card Error!');
define('MODULE_PAYMENT_COINPAYMENTS_TEXT_NOTAUTHED_MESSAGE', 'Your card could not be authorised! Please try again, try another card or <a href="index.php?main_page=contact_us">contact the administrator</a> for further assistance.<p class="ExtraErrorInfo">(%s)</p>');
define('MODULE_PAYMENT_COINPAYMENTS_TEXT_MALFORMED_MESSAGE', 'Your card could not be recognised! Please try again, try another card or <a href="index.php?main_page=contact_us">contact the administrator</a> for further assistance.<p class="ExtraErrorInfo">(%s)</p>');
define('MODULE_PAYMENT_COINPAYMENTS_TEXT_INVALID_MESSAGE', 'Your card details could not be recognised! Please try again, try another card or <a href="index.php?main_page=contact_us">contact the administrator</a> for further assistance.<p class="ExtraErrorInfo">(%s)</p>');
define('MODULE_PAYMENT_COINPAYMENTS_TEXT_ABORT_MESSAGE', 'The Transaction could not be completed because the user clicked the CANCEL button on the payment pages, went inactive for 15 minutes or longer or there was a problem with your internet connection to our servers. Please try again or <a href="index.php?main_page=contact_us">contact the administrator</a> for further assistance.<p class="ExtraErrorInfo">(%s)</p>');
define('MODULE_PAYMENT_COINPAYMENTS_TEXT_REJECTED_MESSAGE', 'Unable to continue! A problem has occurred with our systems. Please <strong><a href="index.php?main_page=contact_us">contact the administrator</a> for further assistance.<p class="ExtraErrorInfo">(%s)</p>');
define('MODULE_PAYMENT_COINPAYMENTS_TEXT_ERROR_MESSAGE', 'Unable to continue! A problem has occurred with our systems. Please <strong><a href="index.php?main_page=contact_us">contact the administrator</a></strong> for assistance.<p class="ExtraErrorInfo">(%s)</p>');
define('MODULE_PAYMENT_COINPAYMENTS_TEXT_DECLINED_MESSAGE', 'Your card could not be authorised! Please try again or <a href="index.php?main_page=contact_us">contact the administrator</a> for further assistance.<p class="ExtraErrorInfo">(%s)</p>');

// Admin text definitions
define('MODULE_PAYMENT_COINPAYMENTS_TEXT_ADMIN_TITLE', 'Coinpayments v%s');