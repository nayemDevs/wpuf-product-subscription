<?php
/*
Plugin Name: wpuf product subscription 
Plugin URI: https://nayemdevs.com/
Description: Add wpuf subscription package in your woocommerce product
Version: 0.1
Author: Nayem
Author URI: https://nayemdevs.com/
License: GPL2
*/

/**
 * Copyright (c) 2016 Your Nayem (email: nayemdevs@gmail.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */


/*Save the value on the backend*/

add_filter( 'job_manager_job_listing_data_fields', 'admin_add_salary_field' );

function admin_add_salary_field( $fields ) {
  $fields['_job_salary'] = array(
    'label'       => __( 'Price ($)', 'job_manager' ),
    'type'        => 'text',
    'placeholder' => 'Inser your pirce',
    'description' => ''
  );
  return $fields;
}

// wpuf meta box on the product area
add_filter( 'woocommerce_product_data_tabs', 'add_my_custom_product_data_tab' );
function add_my_custom_product_data_tab( $product_data_tabs ) {
    $product_data_tabs['wpuf-custom-tab'] = array(
        'label' => __( 'WPUF', 'woocommerce' ),
        'target' => 'my_custom_product_data',
    );
    return $product_data_tabs;
}

// Next provide the corresponding tab content by hooking into the 'woocommerce_product_data_panels' action hook


add_action( 'woocommerce_product_data_panels', 'add_my_custom_product_data_fields' );
function add_my_custom_product_data_fields() {
    global $woocommerce, $post;
    ?>
    <div id="my_custom_product_data" class="panel woocommerce_options_panel">
    <p class="form-field product_field_type">
        <label for="product_field_type"><?php _e( 'WPUF Subscription Package', 'woocommerce' ); ?></label>
        <select id="product_field_type" name="subscription" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>">
            <?php
            $sel_sub = get_post_meta( $post->ID, 'subscription', true );
            $subs = get_posts( array( 'post_type' => 'wpuf_subscription') );

            if ( $subs ) {
                foreach ( $subs as $key =>  $sub ) {
                    echo '<option value="' . $sub->ID . '" '. ( $sel_sub == $sub->ID ? 'selected' : '' ).' >' . esc_html( $sub->post_title ) . '</option>';
                }
            }
            ?>
        </select>
    </p>
    </div>
    <?php
}

// Save Fields
add_action( 'save_post', 'woo_add_custom_general_fields_save' );

function woo_add_custom_general_fields_save( $post_id ){

    if( get_post_type( $post_id)  != 'product' ) return;


    $woocommerce_select = $_POST['subscription'];
    if( !empty( $woocommerce_select ) )
        update_post_meta( $post_id, 'subscription', esc_attr( $woocommerce_select ) );
}

add_action( 'woocommerce_order_status_changed', 'woo_set_wpuf_subscrition', 10, 3 );


function woo_set_wpuf_subscrition( $order_id, $old_status, $new_status ) {

    if ( $new_status === 'completed' ) {
        $customer_id = get_post_meta( $order_id, '_customer_user', true );
        
        $order = new WC_Order( $order_id );
        $items = reset( $order->get_items() );

        $product_id = ( isset( $items['product_id'] ) && ! empty( $items['product_id'] ) ) ? $items['product_id'] : 0;
        
        if ( ! $product_id ) {
            return;
        }

        $pack_id = get_post_meta( $product_id, 'subscription', true );

        WPUF_Subscription::init()->new_subscription( $customer_id, $pack_id, null, false, nulll );

    }
}


