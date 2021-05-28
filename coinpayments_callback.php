<?php

require 'includes/application_top.php';

$report = true;
$error_msg = 'Unknown error';
global $db;

if (
    zen_not_null(MODULE_PAYMENT_COINPAYMENTS_CLIENT_ID) &&
    zen_not_null(MODULE_PAYMENT_COINPAYMENTS_CLIENT_SECRET) &&
    zen_not_null(MODULE_PAYMENT_COINPAYMENTS_WEBHOOKS) &&
    MODULE_PAYMENT_COINPAYMENTS_WEBHOOKS == 'Enabled'
) {

    require_once 'includes/modules/payment/coinpayments/AbstractCoinpaymentsAPI.php';
    $api = new AbstractCoinpaymentsAPI();
    $signature = $_SERVER['HTTP_X_COINPAYMENTS_SIGNATURE'];
    $content = file_get_contents('php://input');
    $request_data = json_decode($content, true);
    if ($api->checkDataSignature($signature, $content, $request_data['invoice']['status']) && isset($request_data['invoice']['invoiceId'])) {
        $invoice_str = $request_data['invoice']['invoiceId'];
        $invoice_str = explode('|', $invoice_str);
        $host_hash = array_shift($invoice_str);
        $invoice_id = array_shift($invoice_str);
        if ($host_hash == md5(zen_href_link('index.php', '', 'SSL', false))) {
            $display_value = $request_data['invoice']['amount']['displayValue'];
            $trans_id = $request_data['invoice']['id'];

            $order_query = $db->Execute("select orders_status, currency, currency_value from " . TABLE_ORDERS . " where orders_id = '" . $invoice_id . "'");
            $num_rows = $order_query->RecordCount();
            if ($num_rows > 0) {
                $report = false;
                if ($order_query) {
                    $status = $request_data['invoice']['status'];
                    if ($status == AbstractCoinpaymentsAPI::PAID_EVENT) {
                        $total_query = $db->Execute("select value from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$invoice_id . "' and class = 'ot_total' limit 1");
                        $comment_status = $status;
                        $comment_status .= ' (transaction ID: ' . $trans_id . ')';
                        $comment_status .= '; (' . sprintf("%.08f", $request_data['invoice']['amount']['displayValue']) . ' ' . $request_data['invoice']['amount']['currency'] . ')';

                        $new_status = MODULE_PAYMENT_COINPAYMENTS_ORDER_STATUS_ID_COMPLETED;

                        $sql_data_array = array('orders_id' => $invoice_id,
                            'orders_status_id' => $new_status,
                            'date_added' => 'now()',
                            'customer_notified' => '0',
                            'comments' => 'CoinPayments.net Notification Verified [' . $comment_status . ']');
                        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

                        $db->Execute("update " . TABLE_ORDERS . " set orders_status = '" . $new_status . "', last_modified = now() where orders_id = '" . (int)$invoice_id . "'");
                    } else {
                        if ($order_query->fields['orders_status'] == MODULE_PAYMENT_COINPAYMENTS_ORDER_STATUS_ID_PREPARING) {
                            $sql_data_array = array('orders_id' => (int)$invoice_id,
                                'orders_status_id' => MODULE_PAYMENT_COINPAYMENTS_ORDER_STATUS_ID_PREPARING,
                                'date_added' => 'now()',
                                'customer_notified' => '0',
                                'comments' => $status);
                            zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
                        }
                    }
                }
            }
        }

    }
}

require 'includes/application_bottom.php';
