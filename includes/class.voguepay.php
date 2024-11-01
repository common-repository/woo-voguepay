<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VPWOO_Voguepay_Plugin extends WC_Payment_Gateway {

    public $allowed_currencies=array('NGN','USD','GHS','GBP','EUR','ZAR');

    public function __construct(){
       
        $this->id 					= 'woo-voguepay-plugin';
        $this->icon 				= apply_filters('vpwoo_voguepay_icon', plugins_url( 'assets/pay-via-voguepay.png' , VPWOO_VOGUEPAY_BASE ) );
        $this->has_fields 			= true;
        $this->order_button_text 	= __('Make Payment','woo-voguepay-lang');
        $this->url 		         	= 'https://voguepay.com/';
        $this->notify_url        	= WC()->api_request_url( 'VPWOO_Voguepay_Plugin' );
        $this->method_title     	= 'VoguePay';
        $this->method_description  	= __('Voguepay provide services for to accept online payments from local and international customers using Mastercard, Visa, Verve Cards and other payment options', 'woo-voguepay-lang');

        //On return page, attempt to requery incase callback failed
        if(isset($_POST['transaction_id']) && isset($_GET['key']))
        {
           $res = wp_remote_post( $this->notify_url, ['body'=>$_POST] );
          if( !is_wp_error($res) && isset($res['body'])){ if($res['body']=="OK") header("Refresh:0");}
        }

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user set variables
        $this->title 					= $this->get_option( 'title' );
        $this->description 				= $this->get_option( 'description' );
        $this->merchant_id 		        = $this->get_option( 'merchant_id' );
        $this->store_id 				= $this->get_option( 'store_id' );
        $this->developer_code 			= $this->get_option( 'developer_code' );
        $this->inline_text 			    = $this->get_option( 'inline_text' );
        $this->memo 			        = $this->get_option( 'memo' );
        $this->enabled            	    = $this->get_option( 'enabled' )=='yes'?true:false;
        $this->demo                     = $this->get_option( 'demo' ) === 'yes' ? true : false;
        $this->method                   = $this->get_option( 'method' ) === 'inline' ?  'inline': 'redirect';

        $this->customer_name      		= $this->get_option( 'customer_name' ) === 'yes' ? true : false;
        $this->customer_email      		= $this->get_option( 'customer_email' ) === 'yes' ? true : false;
        $this->customer_phone      		= $this->get_option( 'customer_phone' ) === 'yes' ? true : false;

        $this->billing_address          = $this->get_option( 'billing_address' ) === 'yes' ? true : false;


        // Check if the gateway can be used
        if ( ! $this->is_valid_for_use() ) {
            $this->enabled = false;
        }

 //      if($this->extra_charges_class===null) $this->extra_charges_class=new VPWOO_Voguepay_Plugin_Extra_Charges();

        //Hooks
        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );

        // Payment listener/API hook
        add_action( 'woocommerce_api_vpwoo_voguepay_plugin', array( $this, 'check_voguepay_response' ) );
    }



    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {

        $form_fields = array(
            'enabled' => array(
                'title'       => __('Enable/Disable', 'woo-voguepay-lang'),
                'label'       => __('Enable Voguepay', 'woo-voguepay-lang'),
                'type'        => 'checkbox',
                'description' => __('Enable as a payment option on the checkout page.', 'woo-voguepay-lang'),
                'default'     => 'yes',
                'desc_tip'    => true
            ),
            'merchant_id' => array(
                'title' 		=> __('VoguePay Merchant ID', 'woo-voguepay-lang'),
                'type' 			=> 'text',
                'description' 	=> __('Enter Your VoguePay Merchant ID Eg. 0123-093384', 'woo-voguepay-lang') ,
                'default' 		=> '',
                'desc_tip'      => true
            ),
            'demo' => array(
                'title'       => __('Demo Mode', 'woo-voguepay-lang'),
                'label'       => __('Enable Demo Mode', 'woo-voguepay-lang'),
                'type'        => 'checkbox',
                'description' => __('Demo mode enables you to test payments before going live. <br />Note that actual funds won\'t be recieved.', 'woo-voguepay-lang'),
                'default'     => 'no',
                'desc_tip'    => true
            ),
            'store_id' => array(
                'title' 		=> __('Store ID (Optional)', 'woo-voguepay-lang'),
                'type' 			=> 'text',
                'description' 	=> __('Enter Your Store ID here, if you have created a unique store on your Voguepay.', 'woo-voguepay-lang') ,
                'default' 		=> '',
                'desc_tip'      => true
            ),
            'title' => array(
                'title' 		=> __('Title', 'woo-voguepay-lang'),
                'type' 			=> 'text',
                'description' 	=> __('Payment title on checkout page.', 'woo-voguepay-lang'),
                'desc_tip'      => true,
                'default' 		=> 'VoguePay'
            ),
            'description' => array(
                'title' 		=> __('Description', 'woo-voguepay-lang'),
                'type' 			=> 'textarea',
                'description' 	=> __('Payment description on checkout page.', 'woo-voguepay-lang'),
                'desc_tip'      => true,
                'default' 		=> __('Make payment using your credit card', 'woo-voguepay-lang')
            ),

            'method' => array(
                'title'       => __('Payment Method', 'woo-voguepay-lang'),
                'type'        => 'select',
                'description' => '',
                'default'     => 'redirect',
                'desc_tip'    => false,
                'options'     => array(
                    'redirect'   	=>__( 'Redirect Method', 'woo-voguepay-lang'),
                    'inline' 	=> __('Inline Method', 'woo-voguepay-lang')
                )
            ),

            'inline_text' => array(
                'title'       => __('Inline Load Message (Optional)', 'woo-voguepay-lang'),
                'type' 			=> 'text',
                'description' 	=> __('This message shows when the checkout page is loading', 'woo-voguepay-lang'),
                'desc_tip'      => true,
                'default' 		=> ''
            ),

            'memo' => array(
                'title'       => __('Memo Description', 'woo-voguepay-lang'),
                'type'        => 'select',
                'description' => __('Eg. Payment for Order ID:: 001 - ', 'woo-voguepay-lang').get_bloginfo('name'),
                'desc_tip'      => true,
                'default'     => 'website',
                'options'     => array(
                    'website'   	=> __('Show website name', 'woo-voguepay-lang'),
                    'product' 	=> __('Show product name', 'woo-voguepay-lang')
                )
            ),

            'customer_name'  => array(
                'title'       => __('Customer Name', 'woo-voguepay-lang'),
                'label'       => __('Send Customer Name to VoguePay', 'woo-voguepay-lang'),
                'type'        => 'checkbox',
                'description' => __('If checked, the customer full name will be sent during transaction', 'woo-voguepay-lang'),
                'default'     => 'no',
                'desc_tip'    => true
            ),
            'customer_email'  => array(
                'title'       => __('Customer Email', 'woo-voguepay-lang'),
                'label'       => __('Send Customer Email to VoguePay', 'woo-voguepay-lang'),
                'type'        => 'checkbox',
                'description' => __('If checked, the customer email address will be sent during transaction', 'woo-voguepay-lang'),
                'default'     => 'no',
                'desc_tip'    => true
            ),
            'customer_phone'  => array(
                'title'       => __('Customer Phone', 'woo-voguepay-lang'),
                'label'       => __('Send Customer Phone to VoguePay', 'woo-voguepay-lang'),
                'type'        => 'checkbox',
                'description' => __('If checked, the customer phone will be sent during transaction', 'woo-voguepay-lang'),
                'default'     => 'no',
                'desc_tip'    => true
            ),
            'billing_address'  => array(
                'title'       => __('Billing Address', 'woo-voguepay-lang'),
                'label'       => __('Send Order Billing Address to VoguePay', 'woo-voguepay-lang'),
                'type'        => 'checkbox',
                'description' => __('If checked, the order billing address will be sent during transaction', 'woo-voguepay-lang'),
                'default'     => 'no',
                'desc_tip'    => true
            ),
 
        );

        $this->form_fields = $form_fields;

    }


    public function is_valid_for_use() {

        if( ! in_array( get_woocommerce_currency(), $this->allowed_currencies ) ) {
            $this->msg = __('Voguepay doesn\'t support your store currency, kindly contact our support team', 'woo-voguepay-lang').' <a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=general">'.__('here','woo-voguepay-lang').'</a>';
            return false;
        }


        return true;
    }

    /**
     * Check if this gateway is enabled
     */
    public function is_available() {

        if ( $this->enabled == "yes" ) {

            if ( ! $this->merchant_id && !$this->demo ) {
                return false;
            }
            return true;
        }

        return false;
    }



    /**
     * Process the payment and return the result
     **/
    public function process_payment( $order_id ) {

        $response = $this->get_payment_link( $order_id );

        if( 'success' == $response['result'] ) {

            return array(
                'result' => 'success',
                'redirect' => $response['redirect']
            );

        } else {

            if($response==-1||$response==-4) wc_add_notice( __("Invalid configuration, confirm your merchant ID", 'woo-voguepay-lang'), 'error' );
            else if($response==-3||$response==-14) wc_add_notice( __("Merchant ID is incorrect", 'woo-voguepay-lang'), 'error' );
            else wc_add_notice( __("Unable to complete payment request", 'woo-voguepay-lang'), 'error' );
           
            return array(
                'result' 	=> 'fail',
                'redirect'	=> ''
            );

        }
    }


    public function payment_scripts()
    {

        if (!is_checkout_pay_page() || !$this->enabled || $this->method!=='inline') {
            return;
        }

        wp_enqueue_script( 'jquery' );

        wp_enqueue_script( 'vpwoo_voguepay', $this->url.'js/voguepay.js', array( 'jquery' ));
        wp_enqueue_script( 'vpwoo_voguepay_inline', plugins_url( 'assets/woo-voguepay.js', VPWOO_VOGUEPAY_BASE ), array( 'jquery', 'vpwoo_voguepay' ));

    }
   
    /**
     * Get Voguepay payment link
     **/
    public function get_payment_link( $order_id ) {
       $order = wc_get_order( $order_id );

        $voguepay_args = $this->get_voguepay_args( $order );
        $voguepay_redirect  = $this->url.'?p=linkToken&';
        $voguepay_redirect .= http_build_query( $voguepay_args );

        $args = array(
            'timeout'   => 100
        );

        $request = wp_remote_get( $voguepay_redirect, $args );
        
        $valid_url = strpos( $request['body'], $this->url.'pay' );

        if ( ! is_wp_error( $request ) &&  $valid_url !== false ) {
            $redirect_url=$request['body'];

            if($this->method=='inline'){
                $bnl=array_pop(explode('/',$redirect_url));
                $redirect_url=$order->get_checkout_payment_url( true ).'&bnl='.$bnl;
            }

            $response = array(
                'result'	=> 'success',
                'redirect'	=> $redirect_url
            );

        } else {
             
            //Check response for response error codes 
            $s2s_code=trim($request['body']);
            if(is_numeric($s2s_code)) return $s2s_code;
             
           
            //Attempt method 2 submission
            $redirect_url=$order->get_checkout_payment_url( true ).'&vpm2';
            $response = array(
                'result'	=> 'success',
                'redirect'	=> $redirect_url
            );

        }

        return $response;
    }

    /**
     * Displays the payment page
     */
    public function receipt_page( $order_id ) {
 
        $order = wc_get_order( $order_id );
        
        if(isset($_GET['vpm2']))
        {
            $voguepay_args = $this->get_voguepay_args( $order );
            ?>
            
               <form id="vpm2_form" method="post" action="<?php echo $this->url; ?>pay">
                <?php foreach ($voguepay_args as $key=>$val){ ?>
                  <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $val; ?>" />
                <?php } ?>
               </form>
                <button class="button alt" onclick="document.getElementById('vpm2_form').submit();"><?php echo $this->order_button_text; ?></button> 
                <a class="button cancel" href="<?php echo esc_url( $order->get_cancel_order_url() ); ?>"><?php echo __('Cancel order', 'woo-voguepay-lang')?></a>
              
            
            <?php
             
        }else{
         
            $url=$this->url.'pay/bnlink/'.sanitize_text_field($_GET['bnl']);
    
            echo '<p>'.__('Thank you for your order, please click the payment button below to proceed.','woo-voguepay-lang').'</p>';
    
            echo '<div>
                    <form id="order_review" method="post" action="'. WC()->api_request_url( 'VPWOO_Voguepay_Plugin' ) .'"></form>
                    <button class="button alt" onclick="vp_inline(\''.$url.'\',\''.((strlen($this->inline_text)>0)?(str_replace("'","\'",$this->inline_text)):"").'\',\''.$order->get_cancel_order_url().'\',\''.$this->get_return_url( $order ).'\')">'.$this->order_button_text.'</button> 
                    <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">'.__('Cancel order','woo-voguepay-lang').'</a>
                  </div>';
        }
    }
    /**
     * Get voguepay args
     **/
    public function get_voguepay_args( $order ) {

        $memo=' - '.get_bloginfo('name');

        if($this->memo=='product')
        {
            $memo='';
            $items=$order->get_items();
            foreach ( $items as $item ) {
                $memo.=' - '.($item['name']);
            }
        }

        //Restrict it to 100 characters
        $memo = strlen($memo) > 150 ? substr($memo,0,150)."..." : $memo;

        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
        $voguepay_args = array(
            'v_merchant_id' 		=> ($this->demo)?'demo':trim($this->merchant_id),
            'cur' 					=> get_woocommerce_currency(),
            'memo'					=> "Payment for Order ID:: $order_id".$memo,
            'total' 				=> $order->get_total(),
            'merchant_ref'			=> $order_id.'-'.get_woocommerce_currency().'-'.$order->get_total(),
            'notify_url'			=> $this->notify_url,
            'success_url'			=> $this->get_return_url( $order ),
            'fail_url'				=> $this->get_return_url( $order )
        );

        if(!empty($this->store_id)) $voguepay_args['store_id']=$this->store_id;
        /*
         * if you are a developer, you can generate your developer code and replace mine below or ignore to give me credits :) 
         * Remember to also give this plugin a good review also watch out for updates
         */ 
        $voguepay_args['developer_code']='5b3d29f078165';
        if($this->customer_name){
            $first_name  	= method_exists( $order, 'get_billing_first_name' ) ? $order->get_billing_first_name() : $order->billing_first_name;
            $last_name  	= method_exists( $order, 'get_billing_last_name' ) ? $order->get_billing_last_name() : $order->billing_last_name;
            $voguepay_args['name']=$first_name.' '.$last_name;
        }
        if($this->customer_email)$voguepay_args['email']=method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : $order->billing_email;
        if($this->customer_phone)$voguepay_args['phone']=method_exists( $order, 'get_billing_phone' ) ? $order->get_billing_phone() : $order->billing_phone;
        if($this->billing_address) {
            $billing_address 	= $order->get_formatted_billing_address();
            $billing_address 	= esc_html( preg_replace( '#<br\s*/?>#i', ', ', $billing_address ) );
            $address_split= explode(',',$billing_address);
            if(isset($address_split[1])) $voguepay_args['address'] =$address_split[1];
            if(isset($address_split[2])) $voguepay_args['city'] =$address_split[2];
            if(isset($address_split[3])) $voguepay_args['state'] =$address_split[3];
            if(isset($address_split[4])) $voguepay_args['zipcode'] =$address_split[4];
        }

         $push_args=[];
         foreach($voguepay_args as $key=>$val) $push_args[$key]=str_replace("'","",$val);

        $voguepay_args = apply_filters( 'vpwoo_voguepay_args', $push_args );

        return $voguepay_args;
    }


    public function check_voguepay_response() {

        if( isset( $_POST['transaction_id'] ) ) {

            $transaction_id = sanitize_text_field($_POST['transaction_id']);

            $args = array( 'timeout' => 60 );

            if( $this->demo ) {

                $json = wp_remote_get( $this->url .'?v_transaction_id='.$transaction_id.'&type=json&demo=true', $args );

            } else {

                $json = wp_remote_get( $this->url .'?v_transaction_id='.$transaction_id.'&type=json', $args );

            }

            $transaction 	= json_decode( $json['body'], true );
            
            foreach($transaction as $key =>$val) $transaction[$key]=sanitize_text_field($val);
            
            $transaction_id = $transaction['transaction_id'];
            $ref_split 		= explode('-', $transaction['merchant_ref'] );

            $order_id 		= (int) $ref_split[0];

            $order 			= wc_get_order($order_id);
            $order_total	= $order->get_total();

            if(count($order->get_data()['meta_data'])>2) {
                 if($transaction_id ==  $order->get_data()['meta_data'][2]->get_data()['value']) {
                    echo "Callback already processed";
                    die();
                }
            }
            if($transaction_id == $order->data['transaction_id']) {
                echo "Callback already processed";
                die();
            }

            $amount_paid_currency 	= $ref_split[1];
            $amount_paid 	= $ref_split[2];

          
            if( $transaction['status'] == 'Approved' ) {

                if( $transaction['merchant_id'] != $this->merchant_id && $transaction['merchant_id']!='demo' ) {

                    //Update the order status
                    $order->update_status('on-hold', ''); 

                    //Error Note
                    $message = __('Thank you for shopping with us.','woo-voguepay-lang').'<br />'.__('Your payment transaction was successful, but the amount was paid to the wrong merchant account.','woo-voguepay-lang').'<br />'.__('Your order is currently on-hold.','woo-voguepay-lang').'<br />'.__('Kindly contact us for more information regarding your order and payment status.','woo-voguepay-lang');
                    $message_type = 'notice';

                    //Add Admin Order Note
                    $order->add_order_note( __('Look into this order.', 'woo-voguepay-lang').'<br />'.__('This order is currently on hold', 'woo-voguepay-lang').'<br />'.__('Reason: Possible fradulent attempt. Transaction ID:', 'woo-voguepay-lang').$transaction_id);

                    add_post_meta( $order_id, 'transaction_id', $transaction_id, true );

                    // Reduce stock levels
                    $order->reduce_order_stock();

                    // Empty cart
                    wc_empty_cart();

                    echo 'Merchant ID mis-match';

                }
                else {

                    // check if the amount paid is equal to the order amount.
                    if( $amount_paid < $order_total ) {

                        //Update the order status
                        $order->update_status( 'on-hold', '' );

                        //Error Note
                        $message = __('Thank you for shopping with us', 'woo-voguepay-lang').'<br />'.__('Your payment transaction was successful, but the amount paid is not the same as the total order amount', 'woo-voguepay-lang').'<br />'.__('Your order is currently on-hold', 'woo-voguepay-lang').'<br />'.__('Kindly contact us for more information regarding your order and payment status', 'woo-voguepay-lang');
                        $message_type = 'notice';

                        //Add Admin Order Note
                        $order->add_order_note(__('Look into this order', 'woo-voguepay-lang').'<br />'.__('This order is currently on hold', 'woo-voguepay-lang').'<br />'.__('Reason: Amount paid is less than the total order amount', 'woo-voguepay-lang').'<br />'.__('Voguepay Transaction ID: ', 'woo-voguepay-lang').$transaction_id);

                        add_post_meta( $order_id, 'transaction_id', $transaction_id, true );

                        // Reduce stock levels
                        $order->reduce_order_stock();

                        // Empty cart
                        wc_empty_cart();

                        echo 'Total amount mis-match';

                    }
                    else {

                        $order->payment_complete( $transaction_id );

                        //Add admin order note
                        $order->add_order_note( __('Payment Via Voguepay', 'woo-voguepay-lang').'<br />'.__('Transaction ID: ', 'woo-voguepay-lang').$transaction_id );

                        $message = __('Payment was successful', 'woo-voguepay-lang');
                        $message_type = 'success';

                        // Empty cart
                        wc_empty_cart();

                        echo 'OK';
                    }
                }

                $voguepay_message = array(
                    'message'		=> $message,
                    'message_type' 	=> $message_type
                );

                update_post_meta( $order_id, 'message', $voguepay_message );



            }
            else {

                $message = __('Payment failed', 'woo-voguepay-lang');
                $message_type = 'error';

                $transaction_id = $transaction['transaction_id'];

                //Add Admin Order Note
                $order->add_order_note($message.'<br />'.__('Voguepay Transaction ID: ', 'woo-voguepay-lang').$transaction_id.'<br/>'.__('Reason: ', 'woo-voguepay-lang').$transaction['response_message']);

                //Update the order status
                $order->update_status( 'failed', '' );

                $voguepay_message = array(
                    'message'		=> $message,
                    'message_type' 	=> $message_type
                );

                update_post_meta( $order_id, 'message', $voguepay_message );

                add_post_meta( $order_id, 'transaction_id', $transaction_id, true );

                echo "OK";
            }

        } else echo 'Failed to process';

        die();
    }

    /**
     * Admin Panel Options
     */
    public function admin_options() { ?>
        <h2>Voguepay
            <?php
            if ( function_exists( 'wc_back_link' ) ) {
                wc_back_link( __('Return to payments', 'woo-voguepay-lang'), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
            }
            ?>
        </h2>

        <?php
        if ( $this->is_valid_for_use() ){

            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';

        }
        else {	 ?>
            <div class="inline error"><p><strong><?php echo __('Voguepay Payment Gateway Disabled', 'woo-voguepay-lang') ?></strong>: <?php echo $this->msg ?></p></div>

        <?php }

    }



}
