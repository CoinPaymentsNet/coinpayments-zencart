<?php
require_once(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/coinpayments/AbstractCoinpaymentsAPI.php');

class coinpayments extends AbstractCoinpaymentsAPI
{
    /**
     * $code determines the internal 'code' name used to designate "this" payment module
     *
     * @var string
     */
    var $code = 'coinpayments';
    /**
     * @var mixed|null
     */
    public $version = '1.00';
    /**
     * @var string
     */
    public $title;
    /**
     * @var mixed|null
     */
    public $description;
    /**
     * @var bool
     */
    public $enabled;
    /**
     * @var int|mixed|null
     */
    public $order_status = 0;
    /**
     *
     */
    var $api;
    public function __construct()
    {
        $this->code = 'coinpayments';
        $this->form_action_url = sprintf('%s/%s/', AbstractCoinpaymentsAPI::CHECKOUT_URL, AbstractCoinpaymentsAPI::API_CHECKOUT_ACTION);
        global $order;
        $this->title = $this->getModuleDefineValue('_CATALOG_TEXT_TITLE');
        $coinpayments_link = sprintf(
            '<a href="%s" target="_blank" style="text-decoration: underline; font-weight: bold;" title="CoinPayments.net">CoinPayments.net</a>',
            'https://alpha.coinpayments.net/'
        );
        $coin_description = 'Pay with Bitcoin, Litecoin, or other altcoins via ';
        $this->description = sprintf('%s<br/>%s', $coin_description, $coinpayments_link);


        if ((defined('IS_ADMIN_FLAG') && IS_ADMIN_FLAG === true) || (!isset($_GET['main_page']) || $_GET['main_page'] == ''))
        {
            $this->title = sprintf($this->getModuleDefineValue('_ADMIN_TEXT_TITLE'), $this->version);
            $this->description = $this->getModuleDefineValue('_ADMIN_TEXT_DESCRIPTION');

            if ($this->getModuleDefineValue('_STATUS')) {
                $new_version_details = plugin_version_check_for_updates(2049, $this->version);
                if ($new_version_details !== FALSE) {
                    $this->title .= '<span class="alert">' . ' - NOTE: A NEW VERSION OF THIS PLUGIN IS AVAILABLE. <a href="' . $new_version_details['link'] . '" target="_blank">[Details]</a>' . '</span>';
                }
            }
        }
        $this->enabled = (($this->getModuleDefineValue('_STATUS') == 'True') ? true : false);
        $this->sort_order = $this->getModuleDefineValue('_SORT_ORDER');

        if (defined('MODULE_PAYMENT_COINPAYMENTS_SORT_ORDER') && (int)MODULE_PAYMENT_COINPAYMENTS_ORDER_STATUS_ID_PREPARING > 0) {
            $this->order_status = MODULE_PAYMENT_COINPAYMENTS_ORDER_STATUS_ID_PREPARING;
        }
        if ((int)$this->getModuleDefineValue('_ORDER_STATUS_ID') > 0) {
            $this->order_status = $this->getModuleDefineValue('_ORDER_STATUS_ID');
        }
        if (is_object($order)) {
            $this->update_status();
        }



        $this->api = new AbstractCoinpaymentsAPI();

        $this->form_action_url = sprintf('%s/%s/', AbstractCoinpaymentsAPI::CHECKOUT_URL, AbstractCoinpaymentsAPI::API_CHECKOUT_ACTION);

        try {
            if (
                $this->enabled &&
                defined('MODULE_PAYMENT_COINPAYMENTS_CLIENT_ID') &&
                defined('MODULE_PAYMENT_COINPAYMENTS_WEBHOOKS') &&
                defined('MODULE_PAYMENT_COINPAYMENTS_CLIENT_SECRET') &&
                !empty(MODULE_PAYMENT_COINPAYMENTS_CLIENT_ID) &&
                MODULE_PAYMENT_COINPAYMENTS_WEBHOOKS == 'Enabled' &&
                !empty(MODULE_PAYMENT_COINPAYMENTS_CLIENT_SECRET)
            ) {

                $webhooks_list = $this->api->getWebhooksList(MODULE_PAYMENT_COINPAYMENTS_CLIENT_ID, MODULE_PAYMENT_COINPAYMENTS_CLIENT_SECRET);
                if (!empty($webhooks_list)) {
                    $webhooks_urls_list = array();
                    if (!empty($webhooks_list['items'])) {
                        $webhooks_urls_list = array_map(function ($webHook) {
                            return $webHook['notificationsUrl'];
                        }, $webhooks_list['items']);
                    }

                    if (!in_array($this->api->getNotificationUrl(MODULE_PAYMENT_COINPAYMENTS_CLIENT_ID, AbstractCoinpaymentsAPI::CANCELLED_EVENT), $webhooks_urls_list) || !in_array($this->api->getNotificationUrl(MODULE_PAYMENT_COINPAYMENTS_CLIENT_ID,AbstractCoinpaymentsAPI::PAID_EVENT), $webhooks_urls_list)) {
                        $this->api->createWebHook(MODULE_PAYMENT_COINPAYMENTS_CLIENT_ID, MODULE_PAYMENT_COINPAYMENTS_CLIENT_SECRET, AbstractCoinpaymentsAPI::CANCELLED_EVENT);
                        $this->api->createWebHook(MODULE_PAYMENT_COINPAYMENTS_CLIENT_ID, MODULE_PAYMENT_COINPAYMENTS_CLIENT_SECRET, AbstractCoinpaymentsAPI::PAID_EVENT);
                    }
                }
            }
        } catch (Exception $e) {
        }
    }

    /**
     *
     */
    public function update_status()
    {
        global $order, $db;
        if ($this->enabled == false || (int)$this->getModuleDefineValue('_ZONE') == 0) {
            return;
        }
        $check_flag = false;
        $sql = "SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '" . (int)$this->getModuleDefineValue('_ZONE') . "' AND zone_country_id = '" . (int)$order->billing['country']['id'] . "' ORDER BY zone_id";
        $checks = $db->Execute($sql);
        foreach ($checks as $check) {
            if ($check['zone_id'] < 1) {
                $check_flag = true;
                break;
            } elseif ($check['zone_id'] == $order->billing['zone_id']) {
                $check_flag = true;
                break;
            }
        }
        if ($check_flag == false) {
            $this->enabled = false;
        }
    }

    /**
     * @return bool
     */
    public function javascript_validation()
    {
        return false;
    }

    /**
     * @return array
     */
    public function selection()
    {
        return array(
            'id' => $this->code,
            'module' => $this->title
        );
    }

    /**
     * @return bool
     */
    public function pre_confirmation_check()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function confirmation()
    {
        global $cart_CoinPayments_Standard_ID, $customer_id, $languages_id, $order, $order_total_modules, $currency, $db;
        if (isset($_SESSION['cart']->cartID)) {
            $insert_order = false;
            if (isset($_SESSION['cart_CoinPayments_Standard_ID'])) {
                $insert_order=true;
            }
            else{
                $insert_order=true;
            }
            if ($insert_order == true){
                $order_totals = array();
                if (is_array($order_total_modules->modules)) {
                    reset($order_total_modules->modules);
                    while (list(, $value) = each($order_total_modules->modules)) {
                        $class = substr($value, 0, strrpos($value, '.'));
                        if ($GLOBALS[$class]->enabled) {
                            for ($i = 0, $n = sizeof($GLOBALS[$class]->output); $i < $n; $i++) {
                                if (zen_not_null($GLOBALS[$class]->output[$i]['title']) && zen_not_null($GLOBALS[$class]->output[$i]['text'])) {
                                    $order_totals[] = array('code' => $GLOBALS[$class]->code,
                                        'title' => $GLOBALS[$class]->output[$i]['title'],
                                        'text' => $GLOBALS[$class]->output[$i]['text'],
                                        'value' => $GLOBALS[$class]->output[$i]['value'],
                                        'sort_order' => $GLOBALS[$class]->sort_order);
                                }
                            }
                        }
                    }
                }

                $sql_data_array = array('customers_id' => (int)$customer_id,
                    'customers_name' => $db->prepare_input($order->customer['firstname'] . ' ' . $order->customer['lastname']),
                    'customers_company' => $db->prepare_input($order->customer['company']),
                    'customers_street_address' => $db->prepare_input($order->customer['street_address']),
                    'customers_suburb' => $db->prepare_input($order->customer['suburb']),
                    'customers_city' => $db->prepare_input($order->customer['city']),
                    'customers_postcode' => $db->prepare_input($order->customer['postcode']),
                    'customers_state' => $db->prepare_input($order->customer['state']),
                    'customers_country' => $db->prepare_input($order->customer['country']['title']),
                    'customers_telephone' => $db->prepare_input($order->customer['telephone']),
                    'customers_email_address' => $db->prepare_input($order->customer['email_address']),
                    'customers_address_format_id' => $db->prepare_input($order->customer['format_id']),
                    'delivery_name' => $db->prepare_input($order->delivery['firstname'] . ' ' . $order->delivery['lastname']),
                    'delivery_company' => $db->prepare_input($order->delivery['company']),
                    'delivery_street_address' => $db->prepare_input($order->delivery['street_address']),
                    'delivery_suburb' => $db->prepare_input($order->delivery['suburb']),
                    'delivery_city' => $db->prepare_input($order->delivery['city']),
                    'delivery_postcode' => $db->prepare_input($order->delivery['postcode']),
                    'delivery_state' => $db->prepare_input($order->delivery['state']),
                    'delivery_country' => $db->prepare_input($order->delivery['country']['title']),
                    'delivery_address_format_id' => $db->prepare_input($order->delivery['format_id']),
                    'billing_name' => $db->prepare_input($order->billing['firstname'] . ' ' . $order->billing['lastname']),
                    'billing_company' => $db->prepare_input($order->billing['company']),
                    'billing_street_address' => $db->prepare_input($order->billing['street_address']),
                    'billing_suburb' => $db->prepare_input($order->billing['suburb']),
                    'billing_city' => $db->prepare_input($order->billing['city']),
                    'billing_postcode' => $db->prepare_input($order->billing['postcode']),
                    'billing_state' => $db->prepare_input($order->billing['state']),
                    'billing_country' => $db->prepare_input($order->billing['country']['title']),
                    'billing_address_format_id' => $db->prepare_input($order->billing['format_id']),
                    'payment_method' => $db->prepare_input($order->info['payment_method']),
                    'payment_module_code' => $db->prepare_input($order->info['payment_module_code']),
                    'shipping_method' => $db->prepare_input($order->info['shipping_method']),
                    'shipping_module_code' => $db->prepare_input($order->info['shipping_module_code']),
                    'cc_type' => $db->prepare_input($order->info['cc_type']),
                    'cc_owner' => $db->prepare_input($order->info['cc_owner']),
                    'cc_number' => $db->prepare_input($order->info['cc_number']),
                    'cc_expires' => $db->prepare_input($order->info['cc_expires']),
                    'date_purchased' => 'now()',
                    'orders_status' => $db->prepare_input($order->info['order_status']),
                    'order_total' => $db->prepare_input($order->info['total']),
                    'order_tax' => $db->prepare_input($order->info['tax']),
                    'currency' => $db->prepare_input($order->info['currency']),
                    'currency_value' => $db->prepare_input($order->info['currency_value']),
                    'ip_address' => $db->prepare_input($_SESSION['customers_ip_address'] . ' - ' . $_SERVER['REMOTE_ADDR']),
                    'language_code' => $db->prepare_input($_SESSION['languages_code']),
                    'order_weight' => $db->prepare_input($_SESSION['cart']->weight));

                zen_db_perform(TABLE_ORDERS, $sql_data_array);
                $insert_id = zen_db_insert_id();

                $order_total_modules = new order_total;
                $order_totals = $order_total_modules->process();

                for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++) {
                    $sql_data_array = array('orders_id' => $insert_id,
                        'title' => $order_totals[$i]['title'],
                        'text' => $order_totals[$i]['text'],
                        'value' => $order_totals[$i]['value'],
                        'class' => $order_totals[$i]['code'],
                        'sort_order' => $order_totals[$i]['sort_order']);
                    zen_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
                }

                for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {
                    $sql_data_array = array(
                        'orders_id' => $insert_id,
                        'products_id' => zen_get_prid($order->products[$i]['id']),
                        'products_model' => $order->products[$i]['model'],
                        'products_name' => $order->products[$i]['name'],
                        'products_price' => $order->products[$i]['price'],
                        'final_price' => $order->products[$i]['final_price'],
                        'onetime_charges' => $order->products[$i]['onetime_charges'],
                        'products_tax' => $order->products[$i]['tax'],
                        'products_quantity' => $order->products[$i]['qty'],
                        'products_priced_by_attribute' => $order->products[$i]['products_priced_by_attribute'],
                        'product_is_free' => $order->products[$i]['product_is_free'],
                        'products_discount_type' => $order->products[$i]['products_discount_type'],
                        'products_discount_type_from' => $order->products[$i]['products_discount_type_from'],
                        'products_prid' => $order->products[$i]['id'],
                        'products_weight' => (float)$order->products[$i]['weight'],
                        'products_virtual' => (int)$order->products[$i]['products_virtual'],
                        'product_is_always_free_shipping' => (int)$order->products[$i]['product_is_always_free_shipping'],
                        'products_quantity_order_min' => (float)$order->products[$i]['products_quantity_order_min'],
                        'products_quantity_order_units' => (float)$order->products[$i]['products_quantity_order_units'],
                        'products_quantity_order_max' => (float)$order->products[$i]['products_quantity_order_max'],
                        'products_quantity_mixed' => (int)$order->products[$i]['products_quantity_mixed'],
                        'products_mixed_discount_quantity' => (int)$order->products[$i]['products_mixed_discount_quantity']
                    );
                    zen_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
                    $order_products_id = zen_db_insert_id();

                    $attributes_exist = '0';

                    if (isset($order->products[$i]['attributes'])) {
                        $attributes_exist = '1';
                        for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++) {
                            if (DOWNLOAD_ENABLED == 'true') {
                                $attributes_query = "SELECT popt.products_options_name, poval.products_options_values_name,
                                                     pa.options_values_price, pa.price_prefix,
                                                     pa.product_attribute_is_free, pa.products_attributes_weight, pa.products_attributes_weight_prefix,
                                                     pa.attributes_discounted, pa.attributes_price_base_included, pa.attributes_price_onetime,
                                                     pa.attributes_price_factor, pa.attributes_price_factor_offset,
                                                     pa.attributes_price_factor_onetime, pa.attributes_price_factor_onetime_offset,
                                                     pa.attributes_qty_prices, pa.attributes_qty_prices_onetime,
                                                     pa.attributes_price_words, pa.attributes_price_words_free,
                                                     pa.attributes_price_letters, pa.attributes_price_letters_free, 
                                                     pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                                     FROM " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " .
                                    TABLE_PRODUCTS_ATTRIBUTES . " pa
                                                     LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                                     ON pa.products_attributes_id=pad.products_attributes_id
                                                     WHERE pa.products_id = '" . $order->products[$i]['id'] . "'
                                                     AND pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                                     AND pa.options_id = popt.products_options_id
                                                     AND pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                                     AND pa.options_values_id = poval.products_options_values_id
                                                     AND popt.language_id = '" . $_SESSION['languages_id'] . "'
                                                     AND poval.language_id = '" . $_SESSION['languages_id'] . "'";

                                $attributes_values = $db->Execute($attributes_query);
                            } else {
                                $attributes_values = $db->Execute("SELECT popt.products_options_name, poval.products_options_values_name,
                                                                  pa.options_values_price, pa.price_prefix,
                                                                  pa.product_attribute_is_free, pa.products_attributes_weight, pa.products_attributes_weight_prefix,
                                                                  pa.attributes_discounted, pa.attributes_price_base_included, pa.attributes_price_onetime,
                                                                  pa.attributes_price_factor, pa.attributes_price_factor_offset,
                                                                  pa.attributes_price_factor_onetime, pa.attributes_price_factor_onetime_offset,
                                                                  pa.attributes_qty_prices, pa.attributes_qty_prices_onetime,
                                                                  pa.attributes_price_words, pa.attributes_price_words_free,
                                                                  pa.attributes_price_letters, pa.attributes_price_letters_free
                                                                  FROM " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                                                  WHERE pa.products_id = '" . $order->products[$i]['id'] . "' AND pa.options_id = '" . (int)$order->products[$i]['attributes'][$j]['option_id'] . "' 
                                                                  AND pa.options_id = popt.products_options_id AND pa.options_values_id = '" . (int)$order->products[$i]['attributes'][$j]['value_id'] . "' 
                                                                  AND pa.options_values_id = poval.products_options_values_id AND popt.language_id = '" . $_SESSION['languages_id'] . "' 
                                                                  AND poval.language_id = '" . $_SESSION['languages_id'] . "'");
                            }
                            $sql_data_array = array('orders_id' => $insert_id,
                                'orders_products_id' => $order_products_id,
                                'products_options' => $attributes_values->fields['products_options_name'],
                                'products_options_values' => $attributes_values->fields['products_options_values_name'],
                                'options_values_price' => $attributes_values->fields['options_values_price'],
                                'price_prefix' => $attributes_values->fields['price_prefix']);

                            zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

                            if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && zen_not_null($attributes_values['products_attributes_filename'])) {
                                $sql_data_array = array('orders_id' => $insert_id,
                                    'orders_products_id' => $order_products_id,
                                    'orders_products_filename' => $attributes_values->fields['products_attributes_filename'],
                                    'download_maxdays' => $attributes_values->fields['products_attributes_maxdays'],
                                    'download_count' => $attributes_values->fields['products_attributes_maxcount']);

                                zen_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
                            }
                        }
                    }
                }

                $cart_CoinPayments_Standard_ID = $_SESSION['cart']->cartID . '-' . $insert_id;
                $_SESSION['cart_CoinPayments_Standard_ID'] = $cart_CoinPayments_Standard_ID;
            }
        }
        return false;
    }
    /**
     * @return string
     */
    public function process_button()
    {
        global $db, $order, $cart_CoinPayments_Standard_ID;
        $process_button_string = '';

        $client_id = MODULE_PAYMENT_COINPAYMENTS_CLIENT_ID;
        $client_secret = MODULE_PAYMENT_COINPAYMENTS_CLIENT_SECRET;

        $order_id = substr($cart_CoinPayments_Standard_ID, strlen($_SESSION['cart']->cartID) + 1);
        $invoice_id = sprintf('%s|%s', md5(zen_href_link('index.php', '', 'SSL', false)), $order_id);

        try {

            $currency_code = $order->info['currency'];
            $coin_currency = $this->api->getCoinCurrency($currency_code);

            $amount = number_format($order->info['total'], $coin_currency['decimalPlaces'], '', '');
            $display_value = $order->info['total'];
            $adm_folder_name = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where `configuration_title` LIKE 'Admin URL'");
            $invoice_params = array(
                'invoice_id' => $invoice_id,
                'currency_id' => $coin_currency['id'],
                'amount' => $amount,
                'display_value' => $display_value,
                'billing_data' => $order->billing,
                'notes_link' => sprintf(
                    "http://zencart/%s/%s|Store name: %s|Order #%s", $adm_folder_name->fields['configuration_value'],
                    'index.php?cmd=orders&origin=index&oID=' . $order_id . '&action=edit',
                    STORE_NAME,
                    $order_id),
            );
            if (MODULE_PAYMENT_COINPAYMENTS_WEBHOOKS == 'Enabled') {
                $resp = $this->api->createMerchantInvoice($client_id, $client_secret, $invoice_params);
                $invoice = array_shift($resp['invoices']);
            } else {
                $invoice = $this->api->createSimpleInvoice($client_id, $invoice_params);
            }

            $parameters = array(
                'invoice-id' => $invoice['id'],
                'success-url' => zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL'),
                'cancel-url' => zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'),
            );

            while (list($key, $value) = each($parameters)) {
                $process_button_string .= zen_draw_hidden_field($key, $value);
            }
        } catch (Exception $e) {

        }

        $js = <<<EOD
        <script type="text/javascript">
            $(function() {
                $('form[name="checkout_confirmation"]').submit(function() {
                    $(this).attr('method', 'get');
                });
            });
        </script>
EOD;

        return $process_button_string . $js;
    }

    /**
     *
     */
    public function before_process()
    {
        zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
        return false;
    }
    /**
     * @return bool
     */
    public function after_process()
    {
        return false;
    }
    /**
     * @return int
     */
    public function check()
    {
        global $db;
        $apiType = strtoupper($this->code);
        if (!isset($this->_check)) {
            $sql = "SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_" . $apiType . "_STATUS'";
            $check_query = $db->execute($sql);
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;
    }

    /**
     *
     */
    public function install()
    {
        global $db;
        $chars = preg_split("#/#", zen_href_link('index.php', '', 'SSL', false), -1, PREG_SPLIT_OFFSET_CAPTURE);
        $adm_url = $chars[3][0];
        $prep_status_id = $this->install_status('Preparing [CoinPayments.net]');
        $complete_status_id = $this->install_status('Complete [CoinPayments.net]');
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Enable Coinpayments Form Module', '" . $this->getModuleDefineName('_STATUS') . "', 'True', 'Do you want to accept Coinpayments payments?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Client ID', '" . $this->getModuleDefineName('_CLIENT_ID') . "', '', 'Your Coinpayments.net Client ID (You can find it on the My Account page)', '6', '1', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Client Secret', '" . $this->getModuleDefineName('_CLIENT_SECRET') . "', '', 'Your Client Secret (Set on the Edit Settings page on CoinPayments.net)', '6', '3', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) VALUES ('Payment Zone', '" .  $this->getModuleDefineName('_ZONE') . "', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '5', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Sort order of display.', '" .  $this->getModuleDefineName('_SORT_ORDER') . "', '0', 'Sort order of display. Lowest is displayed first.', '6', '4', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Preparing Order Status', '" .  $this->getModuleDefineName('_ORDER_STATUS_ID_PREPARING') . "', '0', '', '6', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Completed Order Status', '" .  $this->getModuleDefineName('_ORDER_STATUS_ID_COMPLETED') . "', '0', '', '6', '7', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Webhooks', '" .  $this->getModuleDefineName('_WEBHOOKS') . "', 'Disabled', 'Do you want to accept CoinPayments.net notifications?', '6', '2', 'zen_cfg_select_option(array(\'Disabled\', \'Enabled\'), ', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Debug Mode', '" .  $this->getModuleDefineName('_DEBUGGING') . "', 'Off', 'Would you like to enable debug mode?', '6', '8', 'zen_cfg_select_option(array(\'Off\', \'Log File\', \'Log and Email\'), ', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Admin URL', '" .  $this->getModuleDefineName('_ADMIN_URL') . "', '" . $adm_url . "', '', '6', '0', 'zen_cfg_select_option(array(\'$adm_url\'), ', now())");
    }
    /**
     *
     */
    public function remove()
    {
        global $db;
        $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . implode("', '",  $this->keys()) . "')");
    }

    /**
     * @return array
     */
    public function keys()
    {
        $apiType = strtoupper($this->code);
        $keylist = array(
            'STATUS',
            'CLIENT_ID',
            'WEBHOOKS',
            'CLIENT_SECRET',
            'SORT_ORDER',
            'SORT_ORDER',
            'ZONE',
            'ORDER_STATUS_ID_PREPARING',
            'ORDER_STATUS_ID_COMPLETED',
            'DEBUGGING',
            'ADMIN_URL'
        );

        $keys = array();
        foreach ($keylist as $key) {
            $keyName = 'MODULE_PAYMENT_' . $apiType . '_' . $key;
            $keys[] = $keyName;
        }
        return $keys;
    }

    protected function install_status($status_title)
    {
        global $db;
        $check_query = $db->Execute("select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name = '" . $status_title . "' limit 1");
        if ($check_query -> RecordCount() < 1) {
            $status_query = $db->Execute("select max(orders_status_id) as status_id from " . TABLE_ORDERS_STATUS);
            $status_id = $status_query -> fields['status_id'] + 1;
            $languages = zen_get_languages();

            foreach ($languages as $lang) {
                $db->Execute("insert into " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) values ('" . $status_id . "', '" . $lang['id'] . "', '" . $status_title . "')");
            }

            $flags_query = $db->Execute("describe " . TABLE_ORDERS_STATUS . " public_flag");
            if ($flags_query->RecordCount() == 1) {
                $db->Execute("update " . TABLE_ORDERS_STATUS . " set public_flag = 0 and downloads_flag = 0 where orders_status_id = '" . $status_id . "'");
            }
        } else {

            $status_id = $check_query->fields['orders_status_id'];
        }


        return $status_id;
    }

    /**
     * @param $defineTail
     * @return mixed|null
     */
    public function getModuleDefineValue($defineTail)
    {
        $defineName = 'MODULE_PAYMENT_' . strtoupper($this->code) . $defineTail;
        if (!defined($defineName)) {
            return null;
        }
        return constant($defineName);
    }

    /**
     * @param $defineTail
     * @return string
     */
    public function getModuleDefineName($defineTail)
    {
        $defineName = 'MODULE_PAYMENT_' . strtoupper($this->code) . $defineTail;
        return $defineName;
    }
}
