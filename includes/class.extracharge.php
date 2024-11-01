<?php

if ( ! defined( 'ABSPATH' ) ) exit;


class VPWOO_Voguepay_Plugin_Extra_Charge{

    public function __construct(){
 
        $this->id= 'woo-voguepay-plugin';

        add_action('admin_head', array($this, 'vpwoo_extra_charge_fields'));

        add_action( 'woocommerce_cart_calculate_fees', array( $this, 'vpwoo_calculate_fees' ), 10, 1 );

        wp_enqueue_script( 'wc-add-extra-charges', plugins_url( 'assets/woo-voguepay-cart.js', VPWOO_VOGUEPAY_BASE ), array('wc-checkout'), false, true );


        if(isset($_REQUEST['save'])) {
            $current_tab        = ( empty( $_GET['tab'] ) ) ? '' : sanitize_text_field( urldecode( $_GET['tab'] ) );
            $current_section    = ( empty( $_REQUEST['section'] ) ) ? '' : sanitize_text_field( urldecode( $_REQUEST['section'] ) );

            if($current_tab == 'checkout' && $current_section==$this->id) {

                update_option('vpwoo_extra_charge', sanitize_text_field($_REQUEST['vpwoo_extra_charge']));
                update_option('vpwoo_extra_charge_title', sanitize_text_field($_REQUEST['vpwoo_extra_charge_title']));
                update_option('vpwoo_extra_charge_type', sanitize_text_field($_REQUEST['vpwoo_extra_charge_type']));
                update_option('vpwoo_extra_charge_amount', sanitize_text_field($_REQUEST['vpwoo_extra_charge_amount']));
                update_option('vpwoo_extra_charge_percentage', sanitize_text_field($_REQUEST['vpwoo_extra_charge_percentage']));
                update_option('vpwoo_extra_charge_threshold', sanitize_text_field($_REQUEST['vpwoo_extra_charge_threshold']));
                update_option('vpwoo_extra_charge_maximum', sanitize_text_field($_REQUEST['vpwoo_extra_charge_maximum']));
            }
        }

    }


    function vpwoo_extra_charge_fields(){

        $current_tab        = ( empty( $_GET['tab'] ) ) ? '' : sanitize_text_field( urldecode( $_GET['tab'] ) );
        $current_section    = ( empty( $_REQUEST['section'] ) ) ? '' : sanitize_text_field( urldecode( $_REQUEST['section'] ) );

        if($current_tab != 'checkout' || $current_section!=$this->id) return;



        $extra_charge = get_option( 'vpwoo_extra_charge');
        $extra_charge_title = get_option( 'vpwoo_extra_charge_title');
        $extra_charge_type = get_option( 'vpwoo_extra_charge_type');
        $extra_charge_amount = abs(get_option('vpwoo_extra_charge_amount'));
        $extra_charge_percentage = abs(get_option('vpwoo_extra_charge_percentage'));
        $extra_charge_threshold  = abs(get_option('vpwoo_extra_charge_threshold'));
        $extra_charge_maximum = abs(get_option('vpwoo_extra_charge_maximum'));

        if($extra_charge_amount==0) $extra_charge_amount='';
        if($extra_charge_percentage==0) $extra_charge_percentage='';
        if($extra_charge_threshold==0) $extra_charge_threshold='';
        if($extra_charge_maximum==0) $extra_charge_maximum='';

        ?>


            <script>
                jQuery(document).ready(function($){

                    $data='<style> .extra_charge_box{display: none}  </style>';

                    $data +='<table class="form-table">';

                    $data += "<tr valign=\"top\">";
                    $data += "<th scope=\"row\" class=\"titledesc\"><?php echo __('Extra Charge', 'woo-voguepay-lang'); ?></th>";
                    $data += "<td class=\"forminp\">";
                    $data += "<fieldset>";
                    $data += "<select name=\"vpwoo_extra_charge\" id=\"extra_charge_select\">";
                    $data += "<option value=\"yes\"><?php echo __('Yes', 'woo-voguepay-lang'); ?></option>";
                    $data += "<option value=\"no\" <?php if($extra_charge=="no" || $extra_charge=='') echo 'selected'; ?>><?php echo __('No', 'woo-voguepay-lang'); ?></option>";
                    $data += "</select><br /></fieldset></td></tr>";


                    $data += "<tr valign=\"top\" class=\"extra_charge_box\">";
                    $data += "<th scope=\"row\" class=\"titledesc\"><?php echo __('Extra Charges Title', 'woo-voguepay-lang'); ?></th>";
                    $data += "<td class=\"forminp\">";
                    $data += "<fieldset>";
                    $data += "<input name=\"vpwoo_extra_charge_title\" class=\"input-text regular-input\" type=\"text\" value=\"<?php echo $extra_charge_title?>\" placeholder=\"<?php echo __('Eg. Processing fee', 'woo-voguepay-lang'); ?>\"/>";
                    $data += "<br /></fieldset></td></tr>";


                    $data += "<tr valign=\"top\" class=\"extra_charge_box\">";
                    $data += "<th scope=\"row\" class=\"titledesc\"><?php echo __('Extra Charge Type', 'woo-voguepay-lang'); ?></th>";
                    $data += "<td class=\"forminp\">";
                    $data += "<fieldset>";
                    $data += "<select name=\"vpwoo_extra_charge_type\" id=\"extra_charge_type_select\">";
                    $data += "<option value=\"fixed\"><?php echo __('Fixed Amount', 'woo-voguepay-lang'); ?></option>";
                    $data += "<option value=\"percentage\" <?php if($extra_charge_type=='percentage') echo 'selected'; ?>> <?php echo __('Percentage added to total', 'woo-voguepay-lang'); ?> </option>";
                    $data += "</select><br /></fieldset></td></tr>";



                    $data += "<tr valign=\"top\" class=\"extra_charge_box\" id=\"extra_charge_fixed\">";
                    $data += "<th scope=\"row\" class=\"titledesc\"><?php echo __('Extra Charge Amount', 'woo-voguepay-lang'); ?></th>";
                    $data += "<td class=\"forminp\">";
                    $data += "<fieldset>";
                    $data += "<input   class=\"input-text regular-input\" name=\"vpwoo_extra_charge_amount\" type=\"text\" placeholder=\"<?php echo __('Eg. 10', 'woo-voguepay-lang'); ?>\" value=\"<?php echo $extra_charge_amount?>\"/>";
                    $data += "<br /></fieldset></td></tr>";

                    $data += "<tr valign=\"top\" class=\"extra_charge_box\" id=\"extra_charge_percentage\" style=\"display: none;\">";
                    $data += "<th scope=\"row\" class=\"titledesc\"><?php echo __('Extra Charge Percentage (%)', 'woo-voguepay-lang'); ?></th>";
                    $data += "<td class=\"forminp\">";
                    $data += "<fieldset>";
                    $data += "<input   class=\"input-text regular-input\" name=\"vpwoo_extra_charge_percentage\" type=\"text\" placeholder=\"<?php echo __('Eg. 1.5', 'woo-voguepay-lang'); ?>\" value=\"<?php echo $extra_charge_percentage?>\"/>";
                    $data += "<br /></fieldset></td></tr>";


                    $data += "<tr valign=\"top\" class=\"extra_charge_box\">";
                    $data += "<th scope=\"row\" class=\"titledesc new\"> <?php echo __('Add extra charge to total amount less than (Optional)', 'woo-voguepay-lang'); ?> </th>";
                    $data += "<td class=\"forminp\">";
                    $data += "<fieldset>";
                    $data += "<input  class=\"input-text regular-input\" name=\"vpwoo_extra_charge_threshold\" type=\"text\" placeholder=\"<?php echo __('Eg. 100','woo-voguepay-lang') ?>\" value=\"<?php echo $extra_charge_threshold?>\" placeholder=\"0\"/>";
                    $data += "<br /></fieldset></td></tr>";


                    $data += "<tr valign=\"top\" class=\"extra_charge_box\">";
                    $data += "<th scope=\"row\" class=\"titledesc new\"> <?php echo __('Maximum extra charge that can be added (Optional)', 'woo-voguepay-lang'); ?> </th>";
                    $data += "<td class=\"forminp\">";
                    $data += "<fieldset>";
                    $data += "<input  class=\"input-text regular-input\" name=\"vpwoo_extra_charge_maximum\" type=\"text\" placeholder=\"<?php echo __('Eg. 10.50','woo-voguepay-lang') ?>\" value=\"<?php echo $extra_charge_maximum?>\" placeholder=\"0\"/>";
                    $data += "<br /></fieldset></td></tr>";


                    $data+='</div>';

                    $data += '</table>';
                    $data+=" <?php echo __('If you like this plugin please leave us a rating.', 'woo-voguepay-lang'); ?> <a target=\"_blank\" href=\"https://wordpress.org/support/plugin/woo-voguepay/reviews?rate=5#new-post\">★★★★★</a> <?php echo __('Thanks!','woo-voguepay-lang') ?>";

                    $('.form-table:last').after($data);


                    $('#extra_charge_select').change(function () {
                       var selected=$(this).val();
                       if(selected=='yes') {
                           $('.extra_charge_box').show();
                           $('#extra_charge_type_select').trigger('change');
                       }
                       else  $('.extra_charge_box').hide();
                    });

                    $('#extra_charge_type_select').change(function () {
                       var selected=$(this).val();

                       if( $('#extra_charge_select').val()=='no') return;

                        $('#extra_charge_fixed').hide();
                        $('#extra_charge_percentage').hide();

                       if(selected=='fixed') $('#extra_charge_fixed').show();
                        else  $('#extra_charge_percentage').show();

                    });


                    $('#extra_charge_select').trigger('change');
                    $('#extra_charge_type_select').trigger('change');

                });


            </script>
            <?php

    }

    function vpwoo_calculate_fees( $totals ) {
        global $woocommerce;

        if($woocommerce->session->chosen_payment_method==$this->id){

            $extra_charge = get_option( 'vpwoo_extra_charge');
            $extra_charge_title = get_option( 'vpwoo_extra_charge_title');
            $extra_charge_type = get_option( 'vpwoo_extra_charge_type');
            $extra_charge_amount = abs(get_option('vpwoo_extra_charge_amount'));
            $extra_charge_percentage = abs(get_option('vpwoo_extra_charge_percentage'));
            $extra_charge_threshold  = abs(get_option('vpwoo_extra_charge_threshold'));
            $extra_charge_maximum = abs(get_option('vpwoo_extra_charge_maximum'));

            if($extra_charge=='yes'){
                $extracharge=0;

                if(empty($extra_charge_title)) $extra_charge_title=__('Extra charge','woo-voguepay-lang');

                if($extra_charge_type=='fixed' && $extra_charge_amount>0) $extracharge=$extra_charge_amount;

                if($extra_charge_type=='percentage' && $extra_charge_percentage>0 && $extra_charge_percentage<=100) {
                    $extracharge=($totals -> cart_contents_total*$extra_charge_percentage)/100;
                    $extra_charge_title.=' - '.$extra_charge_percentage.'%';
                }

                if($extracharge>$extra_charge_maximum && $extra_charge_maximum>0){
                    $extracharge=$extra_charge_maximum;
                    $extra_charge_title.=' ('.__('capped','woo-voguepay-lang').': '.get_woocommerce_currency_symbol(get_option('woocommerce_currency')).' '.$extra_charge_maximum.')';
                }

                if($totals -> cart_contents_total>=$extra_charge_threshold && $extra_charge_threshold>0) $extracharge=0;

                if($extracharge>0) $woocommerce->cart->add_fee($extra_charge_title,$extracharge);

            }

        }
        return $totals;
    }


}


new VPWOO_Voguepay_Plugin_Extra_Charge();
