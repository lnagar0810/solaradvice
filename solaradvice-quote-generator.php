<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://solaradvice.co.za
 * @since             1.0.0
 * @package           SolarAdvice_ROI
 *
 * @wordpress-plugin
 * Plugin Name:       SolarAdvice Quote Generator
 * Plugin URI:        https://solaradvice.co.za
 * Description:       Quote Generator.
 * Version:           1.0.0
 * Author:            SolarAdvice PTY LTD
 * Author URI:        https://solaradvice.co.za
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       solaradvice-quote-generator
 * Domain Path:       /languages
 */

 if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'PLUGIN_VERSION', '1.0.8' );

define('WPFP_PATH', plugin_dir_url(__FILE__) . '/solaradvice-quote-generator');


function quote_gen_enqueue_scripts() {
    if(is_product()) :
        global $post;
        $get_type = WC_Product_Factory::get_product_type($post->ID);
        $terms = get_the_terms( $post->ID, 'product_cat' );
            if($terms != null) :
                foreach ($terms as $term) : 
                    if($term->slug == 'hybrid-solar-power-kits' || $term->slug == 'off-grid-solar-power-kits' || $term->slug == 'load-shedding-kits') :
                        wp_enqueue_script( 'qg_js', plugin_dir_url( __FILE__ ) . 'assets/main.js', array('jquery', 'shoptimizer-bundles-child'), PLUGIN_VERSION );
                        wp_enqueue_style( 'qg_css', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), PLUGIN_VERSION );                
                    endif;
                endforeach;
            endif;
    endif;
}
add_action('wp_enqueue_scripts', 'quote_gen_enqueue_scripts', 999);


include_once(plugin_dir_path(__FILE__) . 'form-submit.php');

add_action( 'wp_ajax_get_a_quote_form', 'quote_gen_overlay' );
add_action( 'wp_ajax_nopriv_get_a_quote_form', 'quote_gen_overlay' );

function add_gen_quote() {
    echo do_shortcode('[quote-gen-overlay]');
}

function quote_gen_overlay($post) {
    global $woocommerce;
    global $post;
    $get_type = WC_Product_Factory::get_product_type($post->ID);
    $terms = get_the_terms( $post->ID, 'product_cat' );

    // echo '<pre>';
    // print_r($woocommerce);
    // echo '</pre>';

    $out_of_stock = $_GET['out_of_stock'];
    $product_name = $_GET['product_name'];
    $product_id = $_GET['product_id'];
     $cartData = json_decode(stripslashes($_GET['cart_data']),true);

    $product_Arr = [];
    $quantity_Arr = [];
      
    foreach($cartData as $key => $value){
        if(strpos($key, 'wccp_component_selection') !== false){
          $product_Arr[]=$value;
        }
        if(strpos($key, 'wccp_component_quantity') !== false){
          $quantity_Arr[]=$value;
        }
    }
    
    $html = '<div class="quote-gen-overlay">
        <span class="close-quote-gen-overlay">&times;</span>
        <h2>Get an Instant Quote</h2>
        <p class="subline">Get started on your Solar Power journey</p>
        <hr>
        <h4>Your Solar Solution</h4>' ;

    // if($out_of_stock === "true"){
    //     $html .= '<span style="color:red;">* '.$product_name.' is out of stock.</span>';
    // }
    $html .= '<div class="poduct-quote-container" style="margin-top: 10px;">';
    $html .= '<div class="product-quote-thumb">';

    if($out_of_stock === "false"){
        foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $cart_item ) {
            $woocommerce->cart->set_quantity($cart_item_key, round($cart_item['quantity']/2));
        }
        $items = $woocommerce->cart->get_cart();
        
        $products_ids_array = array();

        foreach( $items as $cart_item ){
            $products_ids_array[] = $cart_item['product_id'];
            $html .= '<img src="' . get_the_post_thumbnail_url($products_ids_array[0], 'medium') . '" />';
            break;
        }
    }

    if($out_of_stock === "true"){
       $html .= '<img src="' . get_the_post_thumbnail_url($product_Arr[0], 'medium') . '" />';
    }

    $html .= '</div>';
    $html .= '<div class="product-quote-items">';
    $html .= '<h5>Kit Spec</h5>';
    $html .= '<ul>';
    if($out_of_stock === "false"){
        foreach($items as $item => $values) { 
            $_product = wc_get_product( $values['data']->get_id()); 
            $html .= '<li>' . $values['quantity'] . ' x ' . $_product->get_title().'</li>';
        }
    }
    if($out_of_stock === "true"){
        for($i=0;$i<count($product_Arr);$i++){
        if($product_Arr[$i] !="" && $quantity_Arr[$i]){
            $_product = wc_get_product($product_Arr[$i]);
            $html .= '<li>' . $quantity_Arr[$i] . ' x ' . $_product->get_title().'</li>';
        }
      }
    }
    $html .= '</ul>';
    $html .= '<a class="display-list-link">Show all items</a>';
    $html .= '</div>';
    $html .= '</div>';

    
    $html .= '<hr>';
    $html .= '<h3>Step 1: Your Details</h3>' .
    '<form method="post" class="get_quote_form">' . 
        '<div class="col">' .
            '<div class="cell">' .
                '<label for="name">Full name</label>' .
                '<input type="text" placeholder="Enter Full name" id="name" name="name" required>' .
            '</div>' .
            '<div class="cell">' .
                '<label for="email">Email</label>' .
                '<input type="email" placeholder="Enter email" id="email" name="email" required>' .
            '</div>' .
        '</div>' .
        '<div class="col">' .
            '<div class="cell" style="display: grid;">' .
                '<label for="phone">Phone Number</label>' .
                '<input type="text" placeholder="" id="phone" name="phone" required>' .
            '</div>' .
            '<div class="cell" style="display: grid;">' .
                '<label for="bill">Monthly Electricity Bill</label>' .
                '<input type="text" placeholder="" id="bill" name="meta[monthly-bill]">' .
            '</div>' .
        '</div>' .
        '<div class="col">' .
            '<div class="cell">' .
                '<label for="address">Home address</label>' .
                '<input type="text" placeholder="House number, street name" id="address" name="address" required>' .
            '</div>' .
            '<div class="cell">' .
                '<label for="suburb" class="suburb">&nbsp;</label>' .
                '<input type="text" placeholder="Suburb" id="suburb" name="suburb" required>' .
            '</div>' .
        '</div>' .
        '<div class="col second-section">' .
            '<div class="cell">' .
                '<select name="province" id="province" required>
                    <option value="gauteng">Gauteng</option>
                    <option value="western-province">Western Provice</option>
                    <option value="durban">Durban</option>
                </select>' .
            '</div>' .
            '<div class="cell">' .
                '<input type="text" placeholder="Postcode" id="suburb_2" name="suburb_2" >' .
            '</div>' .
        '</div>';

        if($terms != null) :
            foreach ($terms as $term) : 
                if($term->slug != 'load-shedding-kits') :
                    $html .= '<input type="hidden" name="product_type" value="' . $term->slug . '">' .
                            '<hr>' .
                                '<h3>Step 2: Your Home</h3>' .
                                    '<div class="col">' .
                                        '<div class="cell">' .
                                            '<label for="roof-type">What type of roof do you have?</label>' .
                                                '<select name="meta[roof-type]" id="roof-type" required>
                                                    <option value="flat">Flat Roof</option>
                                                    <option value="tiled">Tiled Roof</option>
                                                    <option value="ibr">IBR</option>
                                                    <option value="other">Other</option>
                                                </select>' .
                                        '</div>' .
                                        '<div class="cell">' .
                                            '<h3>Do you have Solar Water Heating?</h3>' .
                                            '<div class="col radio-area">' .
                                                '<div class="cell">' .
                                                    '<input type="radio" id="yes" name="meta[have-solar]" value="yes" checked >
                                                    <label for="yes">Yes</label>' .
                                                '</div>' .
                                            
                                                '<div class="cell">' .
                                                    '<input type="radio" id="no" name="meta[have-solar]" value="no">
                                                    <label for="no">No</label>' .
                                                '</div>' .
                                                '<div class="cell">' .
                                                    '<input type="radio" id="other" name="meta[have-solar]" value="other">
                                                    <label for="other">Other</label>' .
                                                '</div>' .
                                            '</div>' .
                                        '</div>' .
                                    '</div>';
                endif;
            endforeach;
        endif;    
$html .= '<hr>' .
            '<input type="hidden" value="'.$out_of_stock.'" name="is_out_of_stock"> '.
            '<input type="hidden" value="true" name="ywraq_checkout_quote"> '.
            '<input type="submit" class="button get_quote_form_btn" name="get_a_quote" value="Submit">'  .
        '</form>' . 
    '</div>';

echo $html;
wp_die();

}
add_shortcode( 'quote-gen-overlay', 'quote_gen_overlay' );
                   

/*
* Add shppping on ordere edit page.
*/

function solaradice_order_edit_shipping_cost_details($order_id){
    global $woocommerce,$WC;
    if(!$order_id){
        return;
    }

    $order = wc_get_order($order_id);;
    $solaradice_shipping = $order->get_meta('solaradice_shipping');
    $label = __( 'Shipping', 'woocommerce' );
    $shpping_cost = array_sum(array_column($solaradice_shipping['shipping'],'cost'));

    if($shpping_cost){
    ?>
        <style>
            tr.shipping {
                display: none;
            }
        </style>
        <tr>
            <td class="label" width="1%"><?php echo $label; ?>:</td>
            <td width="1%"></td>
            <td class="shipping-total" width="1%"><span class="woocommerce-Price-amount amount"><strong><?php echo ucfirst($solaradice_shipping['province']); ?></strong></span></strong></td>
        </tr>
        <?php
        foreach ($solaradice_shipping['shipping'] as $key => $shipping) {
        ?>
        <tr>
            <td class="label" width="1%"><?php echo $shipping['type']; ?> <strong> x </strong> <?php echo $shipping['qty']; ?></td>
            <td width="1%" ></td>
            <td class="shipping-costs" width="1%"><span class="woocommerce-Price-amount amount"><?php echo get_woocommerce_currency_symbol().$shipping['cost'] ?></span></td>
        </tr>
        <?php } ?>
    <?php
    }
}
add_action('woocommerce_admin_order_items_after_line_items','solaradice_order_edit_shipping_cost_details');

function solaradice_order_edit_shipping_cost(){
    global $woocommerce,$WC;
    if($_GET['post']){
        $order_id = $_GET['post'];
    }elseif($_REQUEST['order_id']){
        $order_id = $_REQUEST['order_id'];
    }
    $label = __( 'Shipping Tax', 'woocommerce' );
    $custom_shipping_tax = get_post_meta($order_id,'custom_shipping_tax',true);
    $_order_shipping_tax = get_post_meta($order_id,'_order_shipping_tax',true);
    $_order_total = get_post_meta($order_id,'_order_total',true);
    if($_order_shipping_tax){
        update_post_meta($order_id, '_order_total', $_order_total-$custom_shipping_tax);
    }
    update_post_meta($order_id, '_order_shipping_tax','');
    
    if($custom_shipping_tax){
    ?>
        <tr>
            <td class="label"><?php echo $label; ?>:</td>
            <td width="1%"></td>
            <td class="shipping-total">
                <span class="woocommerce-Price-amount amount">
                    <bdi><span class="woocommerce-Price-currencySymbol"> - <?php echo get_woocommerce_currency_symbol(); ?></span><?php echo $custom_shipping_tax; ?></bdi>
                </span>
            </td>
        </tr>
    <?php
    } 
}
add_action('woocommerce_admin_order_totals_after_tax','solaradice_order_edit_shipping_cost');

/*
* Add shipping into invoice pdf
*/

function solaradice_pdf_shipping_cost($document){

    if(!$document->get_id()){
        return;
    }

    $order_id = $document->get_id();

    $order = wc_get_order($order_id);
    $solaradice_shipping = $order->get_meta('solaradice_shipping');
    $label = __( 'Shipping', 'woocommerce' );
    
    if($solaradice_shipping){
        $shpping_cost = array_sum(array_column($solaradice_shipping['shipping'],'cost'));
    ?>
        <tr>
            <td class="left-content column-product"><?php echo $label; ?>:</td>
            <td class="right-content column-total">
                <span class="woocommerce-Price-amount amount">
                    <strong><?php echo get_woocommerce_currency_symbol().$shpping_cost ?></strong>
                </span>
            </td>
        </tr>
    <?php
    }
}
add_action('yith_pdf_invoice_before_total','solaradice_pdf_shipping_cost',99,2);

function solaradice_pdf_shipping_cost_after($document){

    if(!$document->order->get_id()){
        return;
    }

    $order_id = $document->order->get_id();

    if(!$order_id){
        return;
    }

    $order = wc_get_order($order_id);
    $solaradice_shipping = $order->get_meta('solaradice_shipping');
    $label = __( 'Shipping', 'woocommerce' );
    
    if($solaradice_shipping){
        $shpping_cost = array_sum(array_column($solaradice_shipping['shipping'],'cost'));
    ?>  
        <div class="pdf-shipping-content" style="display: flex;flex-direction: column;align-items: flex-start;justify-content: flex-start">            
            <style>
                tr.shipping {
                    display: none;
                }
            </style>
            <div>
                <div class="left-content column-product"><?php echo $label; ?>:</div>
                <div class="right-content column-total">
                    <span class="woocommerce-Price-amount amount">
                        <strong><?php echo ucfirst($solaradice_shipping['province']); ?></strong>
                    </span>
                </div>
            </div>
            <?php
            foreach ($solaradice_shipping['shipping'] as $key => $shipping) {
            ?>
            <div>
                <div class="left-content column-product"><?php echo $shipping['type']; ?><strong> x </strong> <?php echo $shipping['qty']; ?></div>
                <div class="right-content column-total">
                    <span class="woocommerce-Price-amount amount"><?php echo get_woocommerce_currency_symbol().$shipping['cost'] ?></span>
                </div>
            </div>
            <?php } ?>
        </div>
    <?php
    }
}
add_action('yith_ywpi_invoice_template_totals','solaradice_pdf_shipping_cost_after',99,2);