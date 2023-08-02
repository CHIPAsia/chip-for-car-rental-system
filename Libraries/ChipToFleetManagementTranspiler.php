<?php

use FleetManagement\Models\Configuration\ConfigurationInterface;
use FleetManagement\Models\Language\LanguageInterface;
use FleetManagement\Models\Chip\ChipPaymentsTable;

require_once( 'FleetManagementPaymentInterface.php' );

if( !class_exists( 'ChipToFleetManagementTranspiler' ) ) {
  class ChipToFleetManagementTranspiler implements \FleetManagementPaymentInterface {
    const PAYMENT_METHOD                = true;
    protected $conf                     = null;
    protected $lang                     = null;
    protected $settings                 = array();
    protected $debugMode                = 0;
    protected $fields                   = array();
    protected $use_ssl                  = true;
    protected $paymentMethodId          = 0;
    protected $paymentMethodCode        = 'CHIP';
    protected $businessEmail            = '';
    protected $payInCurrencyRate        = 1.0000;
    protected $payInCurrencyCode        = 'MYR';
    protected $payInCurrencySymbol      = 'RM';
    protected $currencyCode             = 'MYR';
    protected $currencySymbol           = 'RM';
    // Testing (true) or regular domain (false)
    protected $useSandbox               = false;
    protected $checkCertificate         = false;
    protected $companyName              = '';
    protected $companyPhone             = '';
    protected $companyEmail             = '';
    protected $paymentCancelledPageId   = 0;
    protected $orderConfirmedPageId     = 0;
    protected $sendNotifications        = 0;
    protected $publicKey                = '';
    protected $privateKey               = '';

    /**
      * @param ConfigurationInterface &$paramConf
      * @param LanguageInterface &$paramLang
      * @param array $paramSettings
      * @param array $paramPaymentMethodDetails
      */
    public function __construct(ConfigurationInterface &$paramConf, LanguageInterface &$paramLang, array $paramSettings, array $paramPaymentMethodDetails) {
      // Set class settings
      $this->conf = $paramConf;
      // Already sanitized before in it's constructor. Too much sanitization will kill the system speed
      $this->lang = $paramLang;
      // Set saved settings
      $this->settings = $paramSettings;

      // Process payment method details
      $this->paymentMethodId = isset($paramPaymentMethodDetails['payment_method_id']) ? abs(intval($paramPaymentMethodDetails['payment_method_id'])) : 0;
      $this->paymentMethodCode = isset($paramPaymentMethodDetails['payment_method_code']) ? sanitize_text_field($paramPaymentMethodDetails['payment_method_code']) : "";
      $this->businessEmail = isset($paramPaymentMethodDetails['payment_method_email']) ? sanitize_email($paramPaymentMethodDetails['payment_method_email']) : "";
      $this->payInCurrencyRate = isset($paramPaymentMethodDetails['pay_in_currency_rate']) ? floatval($paramPaymentMethodDetails['private_key']) : 1.000;
      $this->payInCurrencyCode = !empty($paramPaymentMethodDetails['pay_in_currency_code']) ? sanitize_text_field($paramPaymentMethodDetails['pay_in_currency_code']) : '';
      $this->payInCurrencySymbol = !empty($paramPaymentMethodDetails['pay_in_currency_symbol']) ? sanitize_text_field($paramPaymentMethodDetails['pay_in_currency_symbol']) : '';
      $this->useSandbox = !empty($paramPaymentMethodDetails['sandbox_mode']) ? true : false;
      $this->checkCertificate = !empty($paramPaymentMethodDetails['check_certificate']) ? true : false;
      $this->publicKey = !empty($paramPaymentMethodDetails['public_key']) ? sanitize_text_field($paramPaymentMethodDetails['public_key']) : '';
      $this->privateKey = !empty($paramPaymentMethodDetails['private_key']) ? sanitize_text_field($paramPaymentMethodDetails['private_key']) : '';

      // Process settings
      $this->currencyCode = isset($paramSettings['conf_currency_code']) ? sanitize_text_field($paramSettings['conf_currency_code']) : 'MYR';
      $this->currencySymbol = isset($paramSettings['conf_currency_symbol']) ? sanitize_text_field($paramSettings['conf_currency_symbol']) : 'RM';
      $this->companyName = isset($paramSettings['conf_company_name']) ? sanitize_text_field($paramSettings['conf_company_name']) : 'Product Name';
      $this->companyPhone = isset($paramSettings['conf_company_phone']) ? sanitize_text_field($paramSettings['conf_company_phone']) : '';
      $this->companyEmail = isset($paramSettings['conf_company_email']) ? sanitize_email($paramSettings['conf_company_email']) : '';
      $this->paymentCancelledPageId = isset($paramSettings['conf_cancelled_payment_page_id']) ? abs(intval($paramSettings['conf_cancelled_payment_page_id'])) : 0;
      $this->orderConfirmedPageId = isset($paramSettings['conf_confirmation_page_id']) ? abs(intval($paramSettings['conf_confirmation_page_id'])) : 0;
      $this->sendNotifications = isset($paramSettings['conf_send_emails']) ? abs(intval($paramSettings['conf_send_emails'])) : 0;

      $this->create_necessary_table();
    }

    private function create_necessary_table() {
      if (!get_option( 'chip_car_rental_table' )) {
        $chip_table = new ChipPaymentsTable($this->conf, $this->lang, $this->settings, get_current_blog_id());
        $chip_table->create();
        update_option('chip_car_rental_table', 'set');
      }
    }
        
    public function inDebug() {
      return ($this->debugMode >= 1 ? true : false);
    }

    public function getDescriptionHTML($paramCurrentDescription, $paramTotalPayNow = '0.00') {
      return $paramCurrentDescription;
    }

    /**
      * @param string $paramOrderCode
      * @param string $paramTotalPayNow
      * @return array(
      *   'payment_completed_transaction_id' => 0, // '0' if no transactions were processed
      *   'currency_code' => '',  // Leave blank if no transactions were processed
      *   'currency_symbol' => '',  // Leave blank if no transactions were processed
      *   'amount' => 0.00,  // Leave blank if no transactions were processed
      *   'errors' => array(), // Array
      *   'debug_messages' => array(),  // Array
      *   'trusted_output_html' => '', // String, leave blank if no data needs to be returned
      * );
      */
    public function getProcessingPage($paramOrderCode, $paramTotalPayNow = '0.00') {

      $validPositiveTotalPayNow = $paramTotalPayNow > 0 ? floatval($paramTotalPayNow) : 0.00;
      $secret_key = $this->privateKey;

      // if ( !$paramTotalPayNow > 0 ) {
        
      // }

      $notifyURL = site_url().'/?__'.$this->conf->getPluginPrefix().'api=1&ext_code='.$this->conf->getExtCode();
      $notifyURL .= '&ext_action=payment-callback&payment_method_id='.$this->paymentMethodId;
      $notifyURL .= '&paramOrderCode=' . $paramOrderCode;

      $params = [
        'client' => [
          'email' => $_POST['customer_email'],
          'full_name' => substr( $_POST['customer_first_name'], 0, 128),
        ],
        'purchase' => [
          'products' => [
            [
              'name' => substr($this->conf->getExtName(), 0, 256),
              'price' => round($validPositiveTotalPayNow * 100),
            ]
          ],
          'currency' => $this->payInCurrencyCode,
          'timezone' => 'Asia/Kuala_Lumpur',
        ],
        'brand_id' => $this->publicKey,
        'reference' => substr( $paramOrderCode, 0, 128 ),
        'success_callback' => $notifyURL,
        'success_redirect' => $notifyURL . '&chip_redirect=true',
        'cancel_redirect' => $notifyURL . '&chip_redirect=true',
        'failure_redirect' => $notifyURL . '&chip_redirect=true',
      ];

      $process = curl_init( 'https://gate.chip-in.asia/api/v1/purchases/' );
      curl_setopt($process, CURLOPT_HEADER , 0);
      curl_setopt($process, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , "Authorization: Bearer $secret_key" ));
      curl_setopt($process, CURLOPT_TIMEOUT, 30);
      curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($process, CURLOPT_POSTFIELDS, json_encode($params) ); 

      $return = curl_exec($process);
      curl_close($process);

      $chip_purchase = json_decode($return, true);

      $output = '<h2>CHIP purchase creation failed</h2>';
      $errors = [];

      if (is_array($chip_purchase) AND isset($chip_purchase['checkout_url'])) {
        $output = '<script type="text/javascript">window.location.replace("'.$chip_purchase['checkout_url'].'");</script>';

        $chip_table = new ChipPaymentsTable($this->conf, $this->lang, $this->settings, get_current_blog_id());
        $chip_table_name = $chip_table->getTableName();
        
        
        global $wpdb;

        $wpdb->query( 
          $wpdb->prepare( 
            "INSERT INTO $chip_table_name (
              purchase_slug, purchase_reference
            ) values
            (
              '%s', '%s'
            )",
            $chip_purchase['id'],
            $chip_purchase['reference']
          )
        );
      } else {
        $errors = $chip_purchase;
        $chip_purchase = array();
      }

      return array(
        'payment_completed_transaction_id' => 0,
        'currency_code' => $this->payInCurrencyCode,
        'currency_symbol' => $this->payInCurrencySymbol,
        'amount' => $validPositiveTotalPayNow,
        'errors' => $errors,
        'debug_messages' => $chip_purchase,
        'trusted_output_html' => $output,
      );
    }

    /**
      * Stripe does not use API callback process
      * @return array(
      *   'authorized' => false, // Bool
      *   'order_code' => '', // return '' if no order were processed
      *   'transaction_id' => 0, // '0' if no transactions were processed
      *   'currency_code' => '',  // Leave blank if no transactions were processed
      *   'currency_symbol' => '',  // Leave blank if no transactions were processed
      *   'amount' => 0.00,  // Leave blank if no transactions were processed
      *   'errors' => array(), // Array
      *   'debug_messages' => array(),  // Array
      *   'trusted_output_html' => '', // String, leave blank if no data needs to be returned
      * );
      */
    public function processCallback() {
      if ( isset($_SERVER['HTTP_X_SIGNATURE']) ) {
        $content = file_get_contents( 'php://input' );
        $payment_unverified = json_decode( $content, true );
        $purchase_id = $payment_unverified['id'];
      } elseif ( isset($_GET['chip_redirect']) AND isset($_GET['paramOrderCode']) ) {
        global $wpdb;

        $chip_table = new ChipPaymentsTable($this->conf, $this->lang, $this->settings, get_current_blog_id());
        $chip_table_name = $chip_table->getTableName();

        $wpdb_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $chip_table_name WHERE purchase_reference = %s", $_GET['paramOrderCode'] ) );
        $purchase_id = $wpdb_row->purchase_slug;
      } else {
        exit('Unexpected return');
      }

      $process = curl_init( 'https://gate.chip-in.asia/api/v1/purchases/' . $purchase_id . '/' );

      $secret_key = $this->privateKey;

      curl_setopt($process, CURLOPT_HEADER , 0);
      curl_setopt($process, CURLOPT_HTTPHEADER, array("Authorization: Bearer $secret_key" ));
      curl_setopt($process, CURLOPT_TIMEOUT, 30);
      curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);

      $return = curl_exec($process);
      curl_close($process);

      $payment = json_decode($return, true);

      $paramOrderCode = $payment['reference'];

      $objOrdersObserver = new \FleetManagement\Models\Order\OrdersObserver($this->conf, $this->lang, $this->settings);
      $objInvoicesObserver = new \FleetManagement\Models\Invoice\InvoicesObserver($this->conf, $this->lang, $this->settings);
      $objOrderNotificationsObserver = new \FleetManagement\Models\Order\OrderNotificationsObserver($this->conf, $this->lang, $this->settings);

      $paramOrderCode = sanitize_text_field($_GET['paramOrderCode']);

      $orderId = $objOrdersObserver->getIdByCode($paramOrderCode);

      $objOrder = new \FleetManagement\Models\Order\Order($this->conf, $this->lang, $this->settings, $orderId);
      $overallInvoiceId = $objInvoicesObserver->getIdByParams('OVERALL', $orderId, -1);
      $objOverallInvoice = new \FleetManagement\Models\Invoice\Invoice($this->conf, $this->lang, $this->settings, $overallInvoiceId);
      $customerId = $objOrder->getCustomerId();
      $objCustomer = new \FleetManagement\Models\Customer\Customer($this->conf, $this->lang, $this->settings, $customerId);

      $action = '';
      $paramTransactionAmount = '';
      $errorMessages = array();

      $confirmURL = $this->orderConfirmedPageId > 0 ? $this->lang->getTranslatedURL($this->orderConfirmedPageId) : site_url();
      $cancelURL = $this->paymentCancelledPageId > 0 ? $this->lang->getTranslatedURL($this->paymentCancelledPageId) : site_url();

      $url = $confirmURL;

      if ( $payment['status'] == 'paid' ){

        $action = 'PAYMENT_COMPLETED';

        if ( $objOrder->isPaid() ) {
          // The order has been paid. Do nothing
        } else {
          $paramTransactionAmount = abs(floatval($payment['payment']['amount'] / 100));

          $transactionDateI18n = date_i18n(get_option('date_format'), time() + get_option('gmt_offset') * 3600, true);
          $transactionTimeI18n = date_i18n(get_option('time_format'), time() + get_option('gmt_offset') * 3600, true);
          $transactionType = $this->lang->getText('LANG_TRANSACTION_TYPE_PAYMENT_TEXT');
          $transactionAmount = sanitize_text_field($this->payInCurrencyCode) . ' ' . floatval($paramTransactionAmount);
  
          $paymentHTML_ToAppend = '<!-- EXTERNAL TRANSACTION DETAILS -->
          <br /><br />
          <h2>Reservation confirmed</h2>
          <p>Thank you. We received your payment. Your reservation is now confirmed.</p>
          <table style="font-family:Verdana, Geneva, sans-serif; font-size: 12px; background-color:#eeeeee; width:840px; border:none;" cellpadding="5" cellspacing="1">
          <tr>
          <td align="left" width="30%" style="font-weight:bold; background-color:#ffffff; padding-left:5px;">' . $this->lang->escHTML('LANG_TRANSACTION_DATE_TEXT') . '</td>
          <td align="left" style="background-color:#ffffff; padding-left:5px;">' . $transactionDateI18n . ' ' . $transactionTimeI18n . '</td>
          </tr>
          <tr>
          <td align="left" style="font-weight:bold; background-color:#ffffff; padding-left:5px;">' . $this->lang->escHTML('LANG_TRANSACTION_TYPE_TEXT') . '</td>
          <td align="left" style="background-color:#ffffff; padding-left:5px;">' . esc_html($transactionType) . '</td>
          </tr>
          <tr>
          <td align="left" style="font-weight:bold; background-color:#ffffff; padding-left:5px;">' . $this->lang->escHTML('LANG_TRANSACTION_AMOUNT_TEXT') . '</td>
          <td align="left" style="background-color:#ffffff; padding-left:5px;">' . esc_html($transactionAmount) . '</td>
          </tr>
          <tr>
          <td align="left" style="font-weight:bold; background-color:#ffffff; padding-left:5px;">CHIP Purchase ID</td>
          <td align="left" style="background-color:#ffffff; padding-left:5px;">' . esc_html($payment['id']) . '</td>
          </tr>
          <tr>
          <td align="left" style="font-weight:bold; background-color:#ffffff; padding-left:5px;">Payment Method</td>
          <td align="left" style="background-color:#ffffff; padding-left:5px;">' . esc_html(strtoupper($payment['transaction_data']['payment_method'])) . '</td>
          </tr>
          </table>';
  
          
          $markedAsPaid = $objOrder->confirm($payment['id'], $objCustomer->getEmail());
          $appended = $objOverallInvoice->appendHTML_ToFinalInvoice($paymentHTML_ToAppend);
          
          $emailNotificationSent = false;
          if ($this->sendNotifications) {
            $emailNotificationSent = $objOrderNotificationsObserver->sendOrderConfirmedNotifications($orderId, true);
          }

          if($markedAsPaid && $this->sendNotifications == 1 && $emailNotificationSent === false)
          {
              $errorMessages[] = 'Failed: Reservation was marked as paid, but system was unable to send the confirmation email!';
          } else if($markedAsPaid === false)
          {
              $errorMessages[] = 'Failed: Reservation was not marked as paid!';
          } else if($appended === false)
          {
              $errorMessages[] = 'Failed: Transaction data was not appended to invoice!';
          }
        }
      } else {
        $url = $cancelURL;
        $errorMessages['message'] = 'payment not successful';
      }

      return array(
        'authorized' => true,
        'order_code' => $paramOrderCode,
        'action' => $action,
        'transaction_id' => $payment['id'],
        'currency_code' => $payment['payment']['currency'],
        'currency_symbol' => $this->payInCurrencySymbol,
        'amount' => $paramTransactionAmount,
        'errors' => $errorMessages,
        'debug_messages' => $payment,
        'trusted_output_html' => header('location:' . $url),
      );
    }
  }
}