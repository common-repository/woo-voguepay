<?php
/**
 * Plugin Name
 *
 * @package     WooVoguepay
 * @author      kunlexzy
 *
 * @wordpress-plugin
 * Plugin Name: VoguePay WooCommerce
 * Plugin URI:  https://wordpress.org/plugins/woo-voguepay/
 * Description: VoguePay plugin for WooCommerce.
 * Version:     1.5.4
 * Author:      kunlexzy
 * Author URI:  https://voguepay.com/3445-0056682
 * Text Domain: woo-voguepay-lang 
 * Domain Path: /languages/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'ABSPATH' ) )
    exit;

define( 'VPWOO_VOGUEPAY_BASE', __FILE__ );
define( 'VPWOO_VOGUEPAY_VERSION', '1.5.0' );

function vpwoo_voguepay_init()
{
    if (!class_exists('WC_Payment_Gateway')) return;
     
    //Load text domain
    $res=load_plugin_textdomain('woo-voguepay-lang', false, dirname(plugin_basename(__FILE__)) . '/languages');
 
 //ob_clean();
 
 //echo dirname(plugin_basename(__FILE__)) . '/languages/<br/>';
 //var_dump($res);
 //die();
 
    require_once dirname( __FILE__ ) . '/includes/class.voguepay.php';
    require_once dirname( __FILE__ ) . '/includes/class.extracharge.php';


}
add_action( 'plugins_loaded', 'vpwoo_voguepay_init', 99 );


/**
 * Add Settings link to the plugin entry in the plugins menu
 **/
function vpwoo_voguepay_plugin_action_links( $links ) {

    $settings_link = array(
        'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo-voguepay-plugin' ) . '" title="'.__('Voguepay settings', 'woo-voguepay-lang').'">'.__('Settings', 'woo-voguepay-lang').'</a>'
    );

    return array_merge( $links, $settings_link );

}
add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'vpwoo_voguepay_plugin_action_links' );


/**
 * Add Voguepay Gateway to WC
 **/
function vpwoo_add_voguepay_gateway($methods) {
    $methods[] = 'VPWOO_Voguepay_Plugin';
    return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'vpwoo_add_voguepay_gateway' );
 
 
function vpwoo_message() {

    if( get_query_var( 'order-received' ) ){

        $order_id 		= absint( get_query_var( 'order-received' ) );
        $order 			= wc_get_order( $order_id );
        $payment_method = method_exists( $order, 'get_payment_method' ) ? $order->get_payment_method() : $order->payment_method;

        if( is_order_received_page() &&  ( 'vpwoo_gateway' == $payment_method ) ) {

            $voguepay_message 	= get_post_meta( $order_id, 'message', true );

            if( ! empty( $voguepay_message ) ) {

                $message 			= $voguepay_message['message'];
                $message_type 		= $voguepay_message['message_type'];

                delete_post_meta( $order_id, 'message' );

                wc_add_notice( $message, $message_type );

            }
        }

    }

}
add_action( 'wp', 'vpwoo_message' );

/**
 * Check if voguepay settings are filled
 */
function vpwoo_admin_notices() {

    $settings = get_option( 'woocommerce_woo-voguepay-plugin_settings' );

    if ( $settings['enabled'] == 'no' ) {
        return;
    }

    // Check required fields
    if ( empty($settings['merchant_id']) && $settings['demo']!='yes' ) {
        echo '<div class="error"><p>' . sprintf(__('Please enter your Voguepay Merchant ID <a href="%s">here</a> to be able to use the payment plugin.', 'woo-voguepay-lang'), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=voguepay-woocommerce-plugin' ) ) . '</p></div>';
        return;
    }

}
add_action( 'admin_notices', 'vpwoo_admin_notices' );
