<?php
/*
 * Plugin Name: Макса плагин
 * Description: Описание супер-плагина
 * Version: 1.1.1
 * Author: Max Zhyvotovskyi
 * Author URI: https://maxjulya.info
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Text Domain: trumax
 * Domain Path: /languages
 *
 * Network: true
 */


function mercury_scripts(){

}

function wp8c_register_scripts(){
    wp_register_script('wp8c_scripts', plugins_url('assets/js/admin.js', __FILE__));
}
add_action('admin_enqueue_scripts', 'wp8c_register_scripts');

function wp8c_load_assets(){
    wp_enqueue_script('wp8c_scripts');
//    wp_enqueue_style('mercury-media', get_theme_file_uri('/css/media.css'), array(), '3.3.1');
}
add_action('admin_enqueue_scripts', 'wp8c_load_assets');



// Метабокс загрузки картинок
if(1){
    class_exists('Kama_Post_Meta_Box') && new Kama_Post_Meta_Box( array(
        'id'         => '_qimages',
        'title'      => 'Загрузка картинок и выбор основной',
        'post_type'  => 'product',
        'fields'     => array(
            '_thumbnail_id' => array('callback' => '_qimages_callback' ),
        ),
    ) );

    // выводит все картинки и кнопку
    function _qimages_callback( $args, $post, $name, $val ){
        echo '
		<div class="wp-media-buttons" style="padding:4em 1em 1em 0; __float:none;">
			<button type="button" id="qimages__add" class="button add_media" data-post_id="'. $post->ID .'" data-attr_name="'. $name .'"><span class="wp-media-buttons-icon"></span> Добавить/удалить картинку</button>
		</div>';

        echo ___qimages_callback_images( $post->ID, $name, $val );

        if( ! defined('DOING_AJAX') )
            add_action('admin_print_footer_scripts', '__qimages_callback_js');
    }

    function ___qimages_callback_images( $post_id, $name, $val ){
        $out = '';
        $out .= '<span class="images__wrap">';

        $images = get_children([ 'post_parent' => $post_id ]);
        if( ! $images )
            $out .= 'Картинок нет';
        else
            foreach( $images as $img ){
                $out .= '
				<label style="text-align:center; float:left; margin-right:.5em;">
					<input type="radio" name="'. esc_attr($name) .'" value="'. $img->ID .'" '. checked($img->ID, $val, 0) .'><br>
					<img src="'. wp_get_attachment_image_url( $img->ID, 'thumbnail' ) .'" width="100" height="100" alt="">
				</label>';
            }

        $out .= '</span>';

        return $out;
    }

    function __qimages_callback_js(){
        ?>
        <script>
            jQuery(document).ready(function($){
                var frame;

                $('#qimages__add').click( function( event ) {
                    event.preventDefault();

                    var $el = $(this),
                        attr_name = $el.data('attr_name'),
                        post_id = $el.data('post_id'),
                        $imgs_cont = $el.closest('.inside').find('.images__wrap');

                    if( frame ){  frame.open();  return;  }

                    // Create the media frame.
                    frame = wp.media.frames.questImgAdd = wp.media({
                        states: [
                            new wp.media.controller.Library({
                                title:     'Загрузка изображений квеста',
                                library:   wp.media.query({ type: 'image', post_parent: post_id }),
                                multiple:  false,
                                date:      false
                            })
                        ],

                        // submit button.
                        button: {
                            text: 'Сделать эту картинку главной', // Set the text of the button.
                        }
                    });

                    var selectClose_func = function(){
                        var $checked_radio = $imgs_cont.find('input[type=radio]:checked'),
                            attach_id = $checked_radio.length ? $checked_radio.val() : 0;

                        // если это выбор из окна
                        if( this.toString() === 'select' ){
                            var selected = frame.state().get( 'selection' ).first();
                            if( selected )  attach_id = selected.id;
                        }

                        var data = {
                            action: 'refresh_quest_images',
                            post_id: post_id,
                            attr_name: attr_name,
                            attach_id: attach_id
                        }
                        //console.log( data );
                        // AJAX
                        $imgs_cont.html('Обновляю...');
                        $.post( ajaxurl, data, function(resp) {
                            if( resp !== '' ){
                                $imgs_cont.html( resp );
                            }
                        });
                    };

                    frame.on('select', selectClose_func, 'select' );
                    frame.on('close', selectClose_func, 'close' );

                    // set selected
                    frame.on('open', function(){
                        var $checked = $imgs_cont.find('input[type=radio]:checked');
                        if( $checked.length )
                            frame.state().get('selection').add( wp.media.attachment($checked.val()) );
                    });

                    frame.open();
                });

            });
        </script>
        <?php
    }

    if( defined('DOING_AJAX') && DOING_AJAX ){
        add_action('wp_ajax_refresh_quest_images', 'ajax_refresh_quest_images_cb');

        function ajax_refresh_quest_images_cb(){
            $post_id = (int) $_POST['post_id'];
            $name    = $_POST['attr_name'];
            $val     = (int) $_POST['attach_id'];

            if( ! current_user_can('edit_post', $post_id ) ) die;

            echo ___qimages_callback_images( $post_id, $name, $val );

            die;
        }
    }
}



add_action('admin_enqueue_scripts', 'true_include_myuploadscript');

function true_include_myuploadscript($hook)
{
    // дальше у нас идут скрипты и стили загрузчика изображений WordPress
    if (!did_action('wp_enqueue_media')) {
        wp_enqueue_media();
    }
    // само собой - меняем admin.js на название своего файла
    wp_enqueue_script('myuploadscript', get_stylesheet_directory_uri() . '/admin.js', array('jquery'), null, false);
}

function true_image_uploader_field($args)
{
    // следующая строчка нужна только для использования на страницах настроек
    $value = get_option($args['name']);
    // следующая строчка нужна только для использования в мета боксах
    $value = $args['value'];
    $default = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAOEAAADhCAMAAAAJbSJIAAAAMFBMVEXp7vG6vsG3u77s8fTCxsnn7O/f5OfFyczP09bM0dO8wMPk6ezY3eDd4uXR1tnJzdBvAX/cAAACVElEQVR4nO3b23KDIBRA0ShGU0n0//+2KmO94gWZ8Zxmr7fmwWEHJsJUHw8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAwO1MHHdn+L3rIoK6eshsNJ8kTaJI07fERPOO1Nc1vgQm2oiBTWJ+d8+CqV1heplLzMRNonED+4mg7L6p591FC+133/xCRNCtd3nL9BlxWP++MOaXFdEXFjZ7r8D9l45C8y6aG0cWtP/SUGhs2d8dA/ZfGgrzYX+TVqcTNRRO9l+fS5eSYzQs85psUcuzk6igcLoHPz2J8gvzWaH/JLS+95RfOD8o1p5CU5R7l5LkfKEp0mQ1UX7hsVXqDpRrifILD/3S9CfmlUQFhQfuFu0STTyJ8gsP3PH7GVxN1FC4t2sbBy4TNRTu7LyHJbqaqKFw+/Q0ncFloo7CjRPwMnCWqKXQZ75El4nKC9dmcJaou9AXOE5UXbi+RGeJygrz8Uf+GewSn9uXuplnWDZJ7d8f24F/s6iq0LYf9olbS3Q8i5oKrRu4S9ybwaQ/aCkqtP3I28QDgeoK7TBya/aXqL5COx67PTCD2grtdOwH+pQV2r0a7YVBgZoKwwIVFQYG6ikMDVRTGByopjD8ATcKb0UhhRTe77sKs2DV7FKSjId18TUEBYVyLhUThWfILHTDqmI85/2RWWjcE/bhP6OD7maT3h20MHsA47JC3PsW0wcwLhv9t0OOPOIkCn21y2bXXwlyylxiYMPk1SuCSmpfK8bNQvIrpAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADwNX4BCbAju9/X67UAAAAASUVORK5CYII=';

    if ($value && ($image_attributes = wp_get_attachment_image_src($value, array(150, 110)))) {
        $src = $image_attributes[0];
    } else {
        $src = $default;
    }
    echo '<div>
            <img data-src="' . $default . '" src="' . $src . '" width="150" />
            <div>
                <input type="hidden" name="' . $args['name'] . '" id="' . $args['name'] . '" value="' . $value . '" />
                <button type="submit" class="upload_image_button button">Загрузить</button>
                <button type="submit" class="remove_image_button button">×</button>
            </div>
	    </div>';

}


//Добавляем метабокс
add_action('add_meta_boxes', 'true_meta_boxes_u');

function true_meta_boxes_u()
{
    add_meta_box('truediv', 'Задать изображение товара', 'true_print_box_u', 'product', 'normal', 'high');
}

//Заполняем метабокс
function true_print_box_u($post)
{
    if (function_exists('true_image_uploader_field')) {
        true_image_uploader_field(array(
            'name' => 'uploader_custom',
            'value' => get_post_meta($post->ID, 'uploader_custom', true),
        ));
    }
}

//Сохраняем данные произвольного поля
add_action('save_post', 'true_save_box_data_u');
function true_save_box_data_u($post_id)
{

    // ... тут различные проверки на права пользователя, на тип поста, на то, что не автосохранение и т д

    if (isset($_POST['uploader_custom'])) {
        update_post_meta($post_id, 'uploader_custom', absint($_POST['uploader_custom']));
    }
    return $post_id;

}


function art_woo_add_custom_fields()
{
    global $product, $post;

    echo '<div class="options_group" id="textareaInput"> ';

    woocommerce_wp_text_input(
        array(
            'id' => 'wp8c_custom_product_date_field',
            'placeholder' => 'Publicatiedatum',
            'label' => __('Время создания продукта:', 'woocommerce'),
            'type' => 'date',
            'date-type' => 'years'
        )
    );

    woocommerce_wp_select(
        [
            'id' => 'wp8c_select',
            'label' => 'Выпадающий список',
            'options' => [
                'rare' => __('rare', 'woocommerce'),
                'frequent' => __('frequent', 'woocommerce'),
                'unusual' => __('unusual', 'woocommerce'),
            ],
        ]
    );

    echo '<p><input type="button" class="clear_fields_button button tagadd button-secondary" onclick="clearFieldsFunction()" value="reset">
          </p>
          <p class="new_sub_button"></p>
          </div>';
    $thumbnail_id = (int) get_post_meta( $post->ID, '_thumbnail_id', true );
    var_dump($thumbnail_id);


    echo '<script>
        function clearFieldsFunction() {
            document.getElementById("_regular_price").value = "";
            document.getElementById("_sale_price").value = "";
            document.getElementById("_sale_price_dates_from").value = "";
            document.getElementById("_sale_price_dates_to").value = "";
            document.getElementById("wp8c_custom_product_date_field").value = "";
            document.getElementById("wp8c_select").value = "rare";           
        }
                        
            let clone = document.querySelector(\'#publish\').cloneNode( true );    
            document.querySelector(\'.new_sub_button\').appendChild( clone );

        </script>';

}

add_action('woocommerce_product_options_general_product_data', 'art_woo_add_custom_fields');


function art_woo_custom_fields_save($post_id)
{
    $woocommerce_select = $_POST['wp8c_select'];
    if (!empty($woocommerce_select)) {

        update_post_meta($post_id, 'wp8c_select', esc_attr($woocommerce_select));
    }

    $woocommerce_date_field = $_POST['wp8c_custom_product_date_field'];
    if (!empty($woocommerce_date_field)) {

        update_post_meta($post_id, 'wp8c_custom_product_date_field', esc_attr($woocommerce_date_field));
    }
}

add_action('woocommerce_process_product_meta', 'art_woo_custom_fields_save', 10);


function art_get_text_field_before_add_card()
{
    global $post, $product;
    $product_date_field = get_post_meta($post->ID, 'wp8c_custom_product_date_field', true);
    $select_field = get_post_meta($post->ID, 'wp8c_select', true);

    if ($product_date_field) { ?>
        <div class="number-field">
            <strong>Продукт создан: </strong>
            <?php echo $product_date_field; ?>
        </div>
    <?php }

    if ($select_field) {
        ?>
        <div class="text-field">
            <strong>Тип продукта: </strong>
            <?php echo $select_field; ?>
        </div>
    <?php }

}

add_action('woocommerce_before_add_to_cart_form', 'art_get_text_field_before_add_card');



