<?php

add_action('init','GAQ_super_function',999);

function GAQ_super_function(){
  // GAQ_create_get_a_quote();
  add_action('wp_ajax_GAQ_submit_quote_form','GAQ_submit_quote_form');
  add_action('wp_ajax_nopriv_GAQ_submit_quote_form','GAQ_submit_quote_form');
}

add_action('wp_footer','GAQ_remove_quote_overlay');

  function GAQ_remove_quote_overlay(){
  echo "<script>
        const $ = jQuery;

        $(document).click(function(event) {
          if (!$(event.target).closest(\".quote-gen-overlay\").length) {
            $(\"body\").find(\".quote-gen-overlay\").hide();
            $(\"body\").find(\"#k-overlay\").hide();
          }
        });

        setTimeout(()=>{
          if($(\"#ywraq_checkout_quote\").hasClass(\"disabled\")) {
            $(\"#ywraq_checkout_quote\").removeClass(\"disabled\");
          }
        },3000);

        $('.woocommerce .woocommerce-message').each(function() {
          $(this).hide();
        });
        $('.woocommerce .woocommerce-error').each(function() {
          $(this).hide();
        });


        $('#ywraq_checkout_quote_btn').on('click', function(e) {

          e.preventDefault();

          $(\"#k-overlay\").remove();

          var form = $('form.cart');

          
                    
          form.block({ message: null, overlayCSS: { background: '#fff', opacity: 0.6 } });
          // Empty the cart call kcp_empty_cart_func
          $.ajax({
            type : \"get\",
            url : '".site_url('/wp-admin/admin-ajax.php')."',
            data : {action: \"kcp_empty_cart_func\"},
            success: function(response) {
              
              var formData = new FormData(form[0]);
              var item_id = form.find('[name=add-to-cart]').val();
              formData.append('add-to-cart', item_id);
              localStorage.setItem('formData',JSON.stringify(Object.fromEntries(formData.entries())));
              localStorage.setItem('cartId',item_id);
              quotePopup.close();

              // Add to cart function
              $.ajax({
              url:  wc_add_to_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'custom_add_to_cart' ),
              xhrFields: {
                withCredentials: true
             },
              data: formData,
              type: 'POST',
              processData: false,
              contentType: false,
              complete: function( response ) {
                response = response.responseJSON;
                $(document.body).trigger(\"wc_fragment_refresh\");
                $('.woocommerce-error,.woocommerce-message').remove();
                quotePopup.close();
                var out_of_stock = false;
                var product_name = '';
                if ($('p.stock.out-of-stock.insufficient-stock')[0]){
                    product_name = $('p.stock.out-of-stock.insufficient-stock').html();
                }else{
                   product_name = $(\"h1.product_title\").html();
                }
                let cart_data = localStorage.getItem('formData');
                if(response.cart_hash ==''){
                  var out_of_stock = true;
                }
                $(\"<div id='k-overlay' style='position: fixed;width: 100%;height: 100%;background-color:rgba(0,0,0,0.5);z-index: 99;'></div>\").insertBefore(jQuery( \"#page\") );

                $.ajax({
                  type : \"get\",
                  url : '".site_url('/wp-admin/admin-ajax.php')."',
                  data : {action: \"get_a_quote_form\",out_of_stock:out_of_stock,product_name:product_name,product_id:item_id,cart_data:cart_data},
                  success: function(response) {
                    form.unblock();
                    $(\"#k-overlay\").append(response);
                    quotePopup.close();
                  },
                  error: function(error){
                    $(\"#k-overlay\").remove();
                  }
                }) ; 
            }
          });
        }
      });
    });
    $(document).on('submit', 'form.get_quote_form', function(e) {
          e.preventDefault();
          
          var form = $( this ), 
           action = form.attr( 'action' ),
           type = form.attr( 'method' ),
           data = {};

          let cart_data = localStorage.getItem('formData');
          form.block({ message: null, overlayCSS: { background: '#fff', opacity: 0.6 } });
          
           form.find( '[name]' ).each( function( i , v ){
              var input = $( this ), 
              name = input.attr( 'name' ),
              value = input.val();
              data[name] = value;
           });
           

           $.ajax({
              type : \"post\",
              url : '".site_url('/wp-admin/admin-ajax.php')."',
              data : {action: \"GAQ_submit_quote_form\" , data:data,cart_data:cart_data},
              success: function(response) {
                localStorage.removeItem('formData');
                form.unblock();
                // $(\"#ajax_response_text\").html(response);
                $('.quote-gen-overlay').html(response);
                $('.quote-gen-overlay').css('height','auto');

              },
              error: function(error){
                form.unblock();
              }
            });

        });
  </script>";
 }

function GAQ_submit_quote_form(){

  global $woocommerce,$WC;

  if(isset($_POST['data'])){
    
    $formData = $_POST['data'];
    $metaValues =[];
    foreach($formData as $fieldKey => $fieldValue){
      if(strpos($fieldKey, 'meta[')!== false){
          $widget_id = str_replace(array('meta['), '',$fieldKey);
          $metaValues[$widget_id] = $fieldValue;
      }
    }
    $cartData = json_decode(stripslashes($_POST['cart_data']),true);
    $is_out_of_stock = $formData['is_out_of_stock'];
    
    $email = is_email($formData['email']);
    if(!$email){
      echo 'Email is not valid!'; 
      wp_die();
    }

    $billing_address = array(
      'first_name' => $formData['name'],
      // 'last_name' => $formData['name'],
      'email'      => $formData['email'],
      'phone'      => $formData['phone'],
      'address_1'  => $formData['address']." ".$formData['suburb'],
      'address_2'  => $formData['suburb_2'],
      'city'       => $formData['province'],
      );


    $cart = WC()->cart;

    if($is_out_of_stock){
      $out_of_stock_order = wc_create_order();
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

      $product_qty = array_combine($product_Arr, $quantity_Arr);

      $province = $formData['province'];
      if($province == 'gauteng'){
        $Solar_Panel_cost = 100;
        $Battery_cost = 150;
      }elseif($province == 'durban'){
        $Solar_Panel_cost = 200;
        $Battery_cost = 250;        
      }elseif($province == 'western-province'){
        $Solar_Panel_cost = 300;
        $Battery_cost = 350;
      }

      $shpping['province'] = $province;
      foreach($product_qty as $product_id => $qty) {
        $_product = wc_get_product($product_id);
        $product_type = $_product->get_attribute('pa_product-type');

        if($product_type == 'Solar Panel' || $product_type == 'Battery'){
            $shpping['shipping'][$product_id]['qty'] = $qty;
            if($product_type == 'Solar Panel'){
              $shpping['shipping'][$product_id]['type'] = 'Panels';
              $shpping['shipping'][$product_id]['single_cost'] = $Solar_Panel_cost;
              $shpping['shipping'][$product_id]['cost'] = $Solar_Panel_cost * $qty;
            }elseif($product_type == 'Battery'){
              $shpping['shipping'][$product_id]['type'] = 'Battery';
              $shpping['shipping'][$product_id]['single_cost'] = $Battery_cost;
              $shpping['shipping'][$product_id]['cost'] = $Battery_cost * $qty;
            }
        }
      }

      $shpping_cost = array_sum(array_column($shpping['shipping'],'cost'));

      for($i=0;$i<count($product_Arr);$i++){
        
        if($product_Arr[$i] !="" && $quantity_Arr[$i]){
          $out_of_stock_order->add_product( wc_get_product($product_Arr[$i]),$quantity_Arr[$i]);
        }
      }

      $order_id = $out_of_stock_order->get_id();
    }else{
      
      $checkout = WC()->checkout();
      $order_id = $checkout->create_order(array());
    }
    
    $order = wc_get_order($order_id);
    $order->set_address( $billing_address, 'billing' ); 
    $order->update_status('ywraq-pending');

    $order->update_meta_data( 'ywraq_raq_status','pending' );
    $order->update_meta_data( 'ywraq_raq', 'yes' );
    $order->update_meta_data( 'ywraq_customer_name', $formData['name'] );
    $order->update_meta_data( 'ywraq_customer_email', $formData['email'] );
    $order->update_meta_data( '_ywraq_from_checkout', 1 );
    $order->update_meta_data( 'solaradice_shipping', $shpping);

    foreach($metaValues as $key => $custom_meta_field_value){
      $order->update_meta_data( $key, $custom_meta_field_value );
    }


    $item = new WC_Order_Item_Shipping();
    $item->set_method_title( "Road Freight" );
    $item->set_total( $shpping_cost );

    $order->add_item( $item );
    $order->calculate_totals();
    $result = $order->save();

    $_order_shipping_tax = get_post_meta($order_id,'_order_shipping_tax',true);
    $_order_total = get_post_meta($order_id,'_order_total',true);
    $_order_tax = get_post_meta($order_id,'_order_tax',true);

    update_post_meta($order_id, '_order_total', $_order_total-$_order_shipping_tax);
    update_post_meta($order_id, '_order_tax', $_order_tax-$_order_shipping_tax);
    update_post_meta($order_id, 'custom_shipping_tax',$_order_shipping_tax);
    // update_post_meta($order_id, '_order_shipping_tax','');
    
    $mailer = WC()->mailer();
    $mails = $mailer->get_emails();
    do_action( 'create_pdf',$order_id,);
    // exit;


    if ( ! empty( $mails ) ) {
        foreach ( $mails as $mail ) {
            if ( $mail->id == 'ywraq_send_quote' ) {
               $mail->trigger($order_id);  
            }
         }
    }

    $cart->empty_cart();

    echo "<div class='success-message'>
            <span class='close-quote-gen-overlay'>&times;</span>
            <span class='success-email-icon'></span>
            <h1>Check your Inbox!</h1>
            <p>We’ve sent your quote straight to you, <strong>what next?</strong></p>
            <br>
            <h3>Finance your solution</h3>
            <p>Pay off your system over 60 months</p>
            <a href=".site_url('/finance-application')." class='button finance'>Apply Today</a>
          </div>
          <script>quotePopup.close();</script>";
    
    wp_die();
    // wc_add_notice('Quote Request Sent Successfully','success');
    // return ;
  }else{
    echo "Something went wrong! Try Later";
    wp_die();
  }
}

function load_close_js() { ?>
  <script type="text/javascript">
    jQuery(document).ready(function($) {
      $('.close-quote-gen-overlay').on('click', function(e) {
        $(this).parent().remove();
      });
    });
  </script>
<?php
}

add_action('woocommerce_after_add_to_cart_button' ,'GAQ_get_a_quote_button');
function GAQ_get_a_quote_button(){
  if(is_product()) :
    global $post;
    $get_type = WC_Product_Factory::get_product_type($post->ID);
    $terms = get_the_terms( $post->ID, 'product_cat' );
        if($terms != null) :
            foreach ($terms as $term) : 
                if($term->slug == 'hybrid-solar-power-kits' || $term->slug == 'off-grid-solar-power-kits' || $term->slug == 'load-shedding-kits') :
                  $button_style = get_option( 'ywraq_raq_checkout_button_style', 'button' );
                  $label_button = get_option( 'ywraq_checkout_quote_button_label', __( 'Instant Quote', 'yith-woocommerce-request-a-quote' ) );

                  echo wp_kses_post( apply_filters( 'ywraq_quote_button_checkout_html', '<button type="button" name="show_quote" class="button alt" id="ywraq_checkout_quote_btn" value="Get an Instant Quote" data-value="' . esc_attr( $label_button ) . '">Get an Instant Quote</button>' ) );
                endif;
            endforeach;
        endif;
  endif;
}

function ace_ajax_add_to_cart_handler1() {

  WC_Form_Handler::add_to_cart_action();
  WC_AJAX::get_refreshed_fragments();

}
add_action( 'wc_ajax_custom_add_to_cart', 'ace_ajax_add_to_cart_handler1' );
add_action( 'wc_ajax_nopriv_custom_add_to_cart', 'ace_ajax_add_to_cart_handler1' );



function kcp_empty_cart(){
  $cart = WC()->cart;
  $cart->empty_cart();
  return $cart;
}

add_action( 'wp_ajax_kcp_empty_cart_func', 'kcp_empty_cart' );
add_action( 'wp_ajax_nopriv_kcp_empty_cart_func', 'kcp_empty_cart' );


