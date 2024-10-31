<?php
/*
Plugin Name: Self Made Store
Plugin URI: https://github.com/MakeYourChoice1/Self-Made-Store
Description: Create a Self Made Store to display product information
Version: 3.1.1
Author: Miika Pulkkinen
Author URI: https://www.upwork.com/freelancers/~01757833d12d6f51e1
License: GPLv2
*/

/*  Copyright 2016   Miika Pulkkinen   (email : miika.pulkkinen1@outlook.com)

    This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklink St, Fifth Flood, Bostonm Ma 02110-1301 USA
*/

function smstore_load_bootstrap() {
    wp_enqueue_style( 'bootstrap', get_template_directory_uri() . '/css/bootstrap.min.css', array(), '3.3.6', 'all' );
}
add_action( 'wp_enqueue_scripts', 'smstore_load_bootstrap' );


register_activation_hook( __FILE__, 'smstore_install' );

function smstore_install() {
    $smstore_options_arr = array(
        'currency_sign' => '$',
        'weight_sign'   => 'kg'
    );

    update_option( 'smstore_options', $smstore_options_arr );
}


add_action( 'init', 'smstore_init' );

function smstore_init() {
    $labels = array(
        'name'                  => __( 'Products', 'smstore-plugin' ),
        'singular_name'         => __( 'Product', 'smstore-plugin' ),
        'add_new'               => __( 'Add New', 'smstore-plugin' ),
        'add_new_item'          => __( 'Add New Product', 'smstore-plugin' ),
        'edit_item'             => __( 'Edit Product', 'smstore-plugin' ),
        'new_item'              => __( 'New Product', 'smstore-plugin' ),
        'all_items'             => __( 'All Products', 'smstore-plugin' ),
        'view_item'             => __( 'View Product', 'smstore-plugin' ),
        'search_items'          => __( 'Search Products', 'smstore-plugin' ),
        'not_found'             => __( 'No products found', 'smstore-plugin' ),
        'not_found_in_trash'    => __( 'No products found in Trash', 'smstore-plugin' ),
        'menu_name'             => __( 'Products', 'smstore-plugin' )
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => true,
        'capability_type'       => 'post',
        'has_archive'           => true,
        'hierarchical'          => false,
        'menu_position'         => null,
        'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' )
    );

    register_post_type( 'smstore-products', $args );
}



add_action( 'admin_menu', 'smstore_menu' );

function smstore_menu() {
    add_options_page(
        __( 'Self Made Store Settings Page', 'smstore-plugin' ),
        __( 'Self Made Store Settings', 'smstore-plugin' ),
        'manage_options',
        'smstore-settings-page',
        'smstore_settings_page'
    );
}



function smstore_settings_page() {

    $smstore_options_arr = get_option( 'smstore_options' );

    $smstore_inventory = ( ! empty( $smstore_options_arr['show_inventory'] ) ) ?  $smstore_options_arr['show_inventory'] : '';
    $smstore_currency_sign = $smstore_options_arr['currency_sign'];
    $smstore_weight_sign = $smstore_options_arr['weight_sign'];

    ?>
    <div class="wrap">
        <h2><?php _e( 'Self Made Store Options', 'smstore-plugin' ) ?></h2>
        <form class="" action="options.php" method="post">
            <?php settings_fields( 'smstore-settings-group' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <?php _e( 'Show Product Inventory', 'smstore-plugin' ) ?>
                    </th>
                    <td>
                        <input type="checkbox" name="smstore_options[show_inventory]" <?php echo checked( $smstore_inventory, 'on' ); ?> />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <?php _e( 'Currency Sign', 'smstore-plugin' ) ?>
                    </th>
                    <td>
                        <input type="text" name="smstore_options[currency_sign]" value="<?php echo esc_attr( $smstore_currency_sign ); ?>" size="1" maxlength="1" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <?php _e( 'Weight Sign', 'smstore-plugin' ) ?>
                    </th>
                    <td>
                        <input type="text" name="smstore_options[weight_sign]" value="<?php echo esc_attr( $smstore_weight_sign ); ?>" size="1" maxlength="3" />
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'smstore-plugin' ); ?>" />
            </p>
        </form>
    </div>
    <?php
}




add_action( 'admin_init', 'smstore_register_settings' );

function smstore_register_settings() {
    register_setting( 'smstore-settings-group', 'smstore_options', 'smstore_sanitize_options' );
}

function smstore_sanitize_options( $options ) {
    $options['show_inventory'] = ( ! empty( $options['show_inventory'] ) ) ? sanitize_text_field( $options['show_inventory'] ) : '';
    $options['currency_sign'] = ( ! empty( $options['currency_sign'] ) ) ? sanitize_text_field( $options['currency_sign'] ) : '';
    $options['weight_sign'] = ( ! empty( $options['weight_sign'] ) ) ? sanitize_text_field( $options['weight_sign'] ) : '';

    return $options;
}




add_action( 'add_meta_boxes', 'smstore_register_meta_box' );

function smstore_register_meta_box() {
    add_meta_box(
        'smstore-product-meta',
        __( 'Product Information', 'smstore-plugin' ),
        'smstore_meta_box',
        'smstore-products',
        'side',
        'default'
    );
}


function smstore_meta_box( $post ) {
    $smstore_meta = get_post_meta( $post->ID, '_smstore_product_data', true );

    $smstore_sku = ( ! empty( $smstore_meta['sku'] ) ) ? $smstore_meta['sku'] : '';
    $smstore_price = ( ! empty( $smstore_meta['price'] ) ) ? $smstore_meta['price'] : '';
    $smstore_weight = ( ! empty( $smstore_meta['weight'] ) ) ? $smstore_meta['weight'] : '';
    $smstore_color = ( ! empty( $smstore_meta['color'] ) ) ? $smstore_meta['color'] : '';
    $smstore_inventory = ( ! empty( $smstore_meta['inventory'] ) ) ? $smstore_meta['inventory'] : '';

    wp_nonce_field( 'meta-box-save', 'smstore-plugin' );

    echo '<table>';
    echo '<tr>';
    echo '<td>' . __('Sku', 'smstore-plugin') . ':</td>
          <td><input type="text" name="smstore_product[sku]" value="' .esc_attr( $smstore_sku ). '" size="10"></td>';
    echo '</tr><tr>';
    echo '<td>' . __('Price', 'smstore-plugin') . ':</td>
          <td><input type="text" name="smstore_product[price]" value="' .esc_attr( $smstore_price ). '" size="5"></td>';
    echo '</tr></tr>';
    echo '<td>' . __('Weight', 'smstore-plugin') . ':</td>
          <td><input text="text" name="smstore_product[weight]" value="' .esc_attr( $smstore_weight ). '" size="5"></td>';
    echo '</tr><tr>';
    echo '<td>' . __('Color', 'smstore-plugin') . ':</td>
          <td><input type="text" name="smstore_product[color]" value="' .esc_attr( $smstore_color ). '" size="5"></td>';
    echo '</tr><tr>';
    echo '<td>Inventory:</td><td><select name="smstore_product[inventory]" id="smstore_product[inventory]">
            <option value="In Stock"'
                .selected( $smstore_inventory, 'In Stock', false ) . '>'
                .__( 'In Stock', 'smstore-plugin' ) . '</option>
            <option value="Out of Stock"'
                .selected( $smstore_inventory, 'Out of Stock', false ) . '>'
                .__( 'Out of Stock', 'smstore-plugin' ). '</option>
            <option value="Discontinued"'
                .selected( $smstore_inventory, 'Discontinued', false ) . '>'
                .__( 'Discontinued', 'smstore-plugin' ). '</option>
        </select></td>';
    echo '</tr>';


    echo '<tr><td colspan="2"><hr></td></tr>';
    echo '<tr><td colspan="2"><strong>'
        . __( 'Shortcode Legend', 'smstore-plugin' ) . '</strong></td></tr>';
    echo '<tr><td>' .__( 'Sku', 'smstore-plugin' ). ':</td><td>[smstore show=sku]</td></tr>';
    echo '<tr><td>' .__( 'Price', 'smstore-plugin' ). ':</td><td>[smstore show=price]</td></tr>';
    echo '<tr><td>' .__( 'Weight', 'smstore-plugin' ). ':</td><td>[smstore show=weight]</td></tr>';
    echo '<tr><td>' .__( 'Color', 'smstore-plugin' ). ':</td><td>[smstore show=color]</td></tr>';
    echo '<tr><td>' .__( 'Inventory', 'smstore-plugin' ). ':</td><td>[smstore show=inventory]</td></tr>';
    echo '</table>';
}


add_action( 'save_post', 'smstore_save_meta_box' );

function smstore_save_meta_box( $post_id ) {

    if( get_post_type( $post_id ) == 'smstore-products' && isset( $_POST['smstore_product'] ) ) {

        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return;

        wp_verify_nonce( 'meta-box-save', 'smstore-plugin' );

        $smstore_product_data = $_POST['smstore_product'];

        $smstroe_product_data = array_map( 'sanitize_text_field', $smstore_product_data );

        update_post_meta( $post_id, '_smstore_product_data', $smstore_product_data );
    }

}



add_shortcode( 'smstore', 'smstore_shortcode' );

function smstore_shortcode( $atts, $content = null ) {
    global $post;

    extract( shortcode_atts( array(
        "show" => ''
    ), $atts ) );

    $smstore_options_arr = get_option( 'smstore_options' );

    $smstore_product_data = get_post_meta( $post->ID, '_smstore_product_data', true );

    if( $show == 'sku' ) {
        $smstore_show = ( ! empty( $smstore_product_data['sku'] ) ) ? $smstore_product_data['sku'] : '';
    }elseif( $show == 'price' ) {
        $smstroe_show = $smstore_options_arr['currency_sign'];
        $smstore_show = ( ! empty( $smstore_product_data['price'] ) ) ? $smstore_show . $smstore_product_data['price'] : '';
    }elseif( $show == 'weight' ) {
        $smstore_show = ( ! empty( $smstore_product_data['weight'] ) ) ? $smstore_product_data['weight'] : '';
    }elseif( $show == 'color' ) {
        $smstore_show = ( ! empty( $smstore_product_data['color'] ) ) ? $smstore_product_data['color'] : '';
    }elseif( $show == 'inventory' ) {
        $smstore_show = ( ! empty( $smstore_product_data['inventory'] ) ) ? $smstore_product_data['inventory'] : '';
    }

    return $smstore_show;
}



add_action( 'widgets_init', 'smstore_register_widgets' );

function smstore_register_widgets() {
    register_widget( 'smstore_widget' );
}

class smstore_widget extends WP_Widget {

    function __construct() {
        $widget_ops = array(
            'classname'     => 'smstore-widget-class',
            'description'   => __( 'Display SMStore Products', 'smstore-plugin' )
        );
        parent::__construct( 'smstore_widget', __( 'Products Widget', 'smstore-plugin' ), $widget_ops );
    }


    function form( $instance ) {
        $defaults = array(
            'title'             => __( 'Products', 'smstore-plugin' ),
            'number_products'   => '3',
        );
        $instance = wp_parse_args( (array) $instance, $defaults );
        $title = $instance['title'];
        $number_products = $instance['number_products'];
        ?>
            <p><?php _e('Title', 'smstore-plugin') ?>: <input class="widefat" type="text" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr( $title ); ?>" />
            </p>
            <p><?php _e('Number of Products', 'smstore-plugin'); ?>: <input type="text" name="<?php echo $this->get_field_name( 'number_products' ) ?>" value="<?php echo absint( $number_products ); ?>" size="2" maxlenght="2" />
            </p>
        <?php
    }


    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['number_products'] = absint( $new_instance['number_products'] );

        return $instance;
    }


    function widget( $args, $instance ) {
        global $post;

        extract( $args );

        echo $before_widget;
        $title = apply_filters( 'widget_title', $instance['title'] );
        $number_products = $instance['number_products'];

        if( ! empty( $title ) ) {
            echo $before_title . esc_attr( $title ) . $after_title;
        }


        $args = array(
            'post_type'         => 'smstore-products',
            'posts_per_page'    => absint( $number_products )
        );

        $displayProducts = new WP_Query();
        $displayProducts->query( $args );

        ?>
        <style media="screen">

            .each-product-section {
                margin-bottom: 15px;
            }
            .each-product-section p {
                margin: 0;
                padding: 0;

            }
            .each-product-section img {
                width: 100px;
                height: 100px;

            }
        </style>
        <?php

        while( $displayProducts->have_posts() ) : $displayProducts->the_post();
            ?>
            <div class="each-product-section col-xs-6 col-sm-12 col-md-12 col-lg-6" style="min-height:212px;">
            <?php

            $smstore_options_arr = get_option( 'smstore_options' );


            $smstore_product_data = get_post_meta( $post->ID, '_smstore_product_data', true );

            $smstore_price = ( ! empty( $smstore_product_data['price'] ) ) ? $smstore_product_data['price'] : '';
            $smstore_weight = ( ! empty( $smstore_product_data['weight'] ) ) ? $smstore_product_data['weight'] : '';
            $smstore_inventory = ( ! empty( $smstore_product_data['inventory'] ) ) ? $smstore_product_data['inventory'] : '';
            ?>
            <div class="md-col-6 col-lg-12" style="margin:0; padding:0;">
                <?php the_post_thumbnail(); ?>
            </div>
            <div class="md-col-6 col-lg-12" style="margin:0; padding:0;">
                <p>
                    <a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?> Product Information"><?php the_title(); ?></a>
                </p>
                <?php
                echo '<p>' . __( 'Price', 'smstore-plugin' ) . ': ' .$smstore_options_arr['currency_sign'] . $smstore_price . '</p>';
                if( ! empty( $smstore_product_data['weight'] ) ){
                    echo '<p>' . __( 'Weight', 'smstore-plugin' ) . ': ' . $smstore_weight . ' ' . $smstore_options_arr['weight_sign'] . '</p>';
                }


                if( $smstore_options_arr['show_inventory'] ) {
                    echo "<p>" . __( 'Stock', 'smstore-plugin' ) . ': ' . $smstore_inventory . '</p>';
                }
                ?>
            </div>
            </div>
            <?php
            echo "";
        endwhile;

        wp_reset_postdata();

        echo $after_widget;
    }
}
