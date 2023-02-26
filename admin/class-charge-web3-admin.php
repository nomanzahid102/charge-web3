<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://rapidev.tech
 * @since      1.0.0
 *
 * @package    Charge_Web3
 * @subpackage Charge_Web3/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Charge_Web3
 * @subpackage Charge_Web3/admin
 * @author     Abdul Wahab <rockingwahab9@gmail.com>
 */
class Charge_Web3_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    public function aw_add_gateway_class($gateways)
    {
        $gateways[] = 'WC_ChargeWeb3_Gateway';
        return $gateways;
    }

    public function aw_template_redirect()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'verify-payment' && isset($_GET['key'])) {

            $key = sanitize_text_field($_GET['key']);
            $id = wc_get_order_id_by_order_key($key);
            $payment_id = get_post_meta($id, 'aw_payment_id', true);
            $this->aw_check_status($id, $payment_id);
        }
    }

    public function aw_check_status($order_id, $payment_id)
    {
        $url = "https://api.chargeweb3.com/app-store/v1/payments/payment_link/$payment_id";
        $response = wp_remote_get($url);
        if (!is_wp_error($response) && (200 === wp_remote_retrieve_response_code($response))) {
            $data = json_decode($response['body'], true);

            $status = $data['status'];

            if ($status == "Successful") {

                $order = wc_get_order($order_id);

                if ($order->get_status() == 'pending') {
                    $order->payment_complete();
                    $order->reduce_order_stock();
                    $order->add_order_note('Hey, your order is paid! Thank you!', true);
                }
            }

        }
    }


}

add_action('plugins_loaded', 'aw_init_gateway_class');

function aw_init_gateway_class()
{

    class WC_ChargeWeb3_Gateway extends WC_Payment_Gateway
    {

        /**
         * Class constructor, more about it in Step 3
         */
        public function __construct()
        {

            $this->id = 'charge-web-3'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'Charge Web3';
            $this->method_description = 'Description of Charge Web3 payment gateway'; // will be displayed on the options page

            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );

            // Method with all the options fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');

            $this->api_secret = $this->get_option('api_secret');
            $this->public_key = $this->get_option('public_key');
            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        }

        /**
         * Plugin options, we deal with it in Step 3 too
         */
        public function init_form_fields()
        {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'label' => 'Enable Charge Web3',
                    'type' => 'checkbox',
                    'description' => '',
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default' => 'Charge Web3',
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default' => 'Pay with your wallet via our super-cool payment gateway.',
                ),
                'public_key' => array(
                    'title' => 'Live Public Key',
                    'type' => 'text'
                ),
                'api_secret' => array(
                    'title' => 'Live API Secret Key',
                    'type' => 'password'
                )
            );

        }

        /**
         * You will need it if you want your custom credit card form, Step 4 is about it
         */
        public function payment_fields()
        {

            if ($this->description) {
                echo wpautop(wp_kses_post($this->description));
            }


            echo '<div class="clear"></div></fieldset>';

        }

        /*
         * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
         */
        public function payment_scripts()
        {


        }

        /*
         * Fields validation, more in Step 5
         */
        public function validate_fields()
        {


        }

        /*
         * We're processing the payments here, everything about it is in Step 5
         */
        public function process_payment($order_id)
        {

            global $woocommerce;

            // we need it to get any order detailes
            $order = wc_get_order($order_id);


            /*
             * Array with parameters for API interaction
             */
            $args = array(

                'headers' => array(
                    'Content-Type' => 'application/json',
                    'API-SECRET' => $this->api_secret
                )
            );

            /*
             * Your API interaction could be built with wp_remote_post()
             */

            $token_url = "https://api.chargeweb3.com/app-store/v1/payments/payment_link/allowed_tokens?apiKey=$this->public_key";
            $response = wp_remote_get($token_url, $args);


            if (!is_wp_error($response) && (200 === wp_remote_retrieve_response_code($response))) {
                $body = json_decode($response['body'], true);

                $token_data = $body[0];
                $token = $token_data['tokenAddress'];
                $currency = 'USDC';
                $payment_link_url = "https://api.chargeweb3.com/app-store/v1/payments/payment_link?apiKey=$this->public_key";

                $data = [
                    "title" => "Payment",
                    "description" => "Payment for order #" . $order_id,
                    "amount" => floatval($order->get_total()),
                    "tokenAddress" => $token,
                    "tokenSymbol" => "USDC",
                    "apiKey" => $this->public_key,
                    "redirectUrl" => $order->get_checkout_order_received_url() . '&action=verify-payment'
                ];

                $args['body'] = json_encode($data);

                $response = wp_remote_post($payment_link_url, $args);


                if (!is_wp_error($response) && (201 === wp_remote_retrieve_response_code($response))) {

                    $payment_link_data = json_decode($response['body'], true);
                    $_id = $payment_link_data['_id'];
                    $payment_url = "https://chargeweb3.com/links/$_id";

                    update_post_meta($order_id, 'aw_payment_id', $_id);


                    // we received the payment
                    //$order->payment_complete();
                    //$order->reduce_order_stock();

                    // some notes to customer (replace true with false to make it private)
                    //$order->add_order_note( 'Hey, your order is paid! Thank you!', true );

                    // Empty cart
                    $woocommerce->cart->empty_cart();

                    wp_schedule_single_event(time() + 300, 'aw_check_status', array($order_id, $_id));


                    // Redirect to the thank you page
                    return array(
                        'result' => 'success',
                        'redirect' => $payment_url
                    );

                } else {
                    wc_add_notice('Connection error. 1', 'error');
                    return;
                }

            } else {
                wc_add_notice('Connection error. 2', 'error');
                return;
            }
        }


        /*
         * In case you need a webhook, like PayPal IPN etc
         */
        public function webhook()
        {

            $order = wc_get_order(sanitize_text_field($_GET['id']));
            $order->payment_complete();
            $order->reduce_order_stock();
            update_option('webhook_debug', $_GET);

        }
    }
}