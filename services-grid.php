<?php
/**
 * Plugin Name: Services Grid
 * Description: CPT «Послуги» + шорткод [services_grid] з AJAX-пагінацією, фільтром за ціною, слайдером та lazyload. Галерея працює без ACF Pro.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: services-grid
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SG_SERVICES_PLUGIN_VERSION', '1.0.0' );
define( 'SG_SERVICES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SG_SERVICES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SG_SERVICES_PER_PAGE', 8 );

/**
 * Реєстрація CPT «Послуги»
 */
function sg_register_services_cpt() {

    $labels = array(
        'name'               => __( 'Послуги', 'services-grid' ),
        'singular_name'      => __( 'Послуга', 'services-grid' ),
        'add_new'            => __( 'Додати нову', 'services-grid' ),
        'add_new_item'       => __( 'Додати нову послугу', 'services-grid' ),
        'edit_item'          => __( 'Редагувати послугу', 'services-grid' ),
        'new_item'           => __( 'Нова послуга', 'services-grid' ),
        'view_item'          => __( 'Переглянути послугу', 'services-grid' ),
        'search_items'       => __( 'Пошук послуг', 'services-grid' ),
        'not_found'          => __( 'Послуг не знайдено', 'services-grid' ),
        'not_found_in_trash' => __( 'У кошику послуг не знайдено', 'services-grid' ),
        'all_items'          => __( 'Усі послуги', 'services-grid' ),
        'menu_name'          => __( 'Послуги', 'services-grid' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => array( 'slug' => 'services' ),
        'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-hammer',
        'show_in_rest'       => true,
    );

    register_post_type( 'service', $args );
}
add_action( 'init', 'sg_register_services_cpt' );

/**
 * Активація / деактивація: flush rewrite, щоб працювали URL CPT
 */
function sg_services_activate() {
    sg_register_services_cpt();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sg_services_activate' );

function sg_services_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'sg_services_deactivate' );

/**
 * Admin notice, якщо ACF не активний (використовується тільки для поля «Ціна»)
 */
add_action( 'admin_notices', 'sg_services_acf_notice' );
function sg_services_acf_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'Плагін Services Grid потребує встановленого та активного Advanced Custom Fields (ACF) для поля «Ціна». Без нього поле ціни буде недоступне.', 'services-grid' );
        echo '</p></div>';
    }
}

/**
 * Реєстрація ACF-поля «Ціна»
 */
function sg_register_services_acf_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( array(
        'key'    => 'group_sg_services',
        'title'  => 'Поля послуги',
        'fields' => array(
            array(
                'key'           => 'field_sg_service_price',
                'label'         => 'Ціна',
                'name'          => 'service_price',
                'type'          => 'number',
                'instructions'  => 'Вкажіть ціну послуги',
                'required'      => 0,
                'min'           => 0,
                'step'          => 1,
                'prepend'       => '',
                'append'        => '₴',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'service',
                ),
            ),
        ),
    ) );
}
add_action( 'acf/init', 'sg_register_services_acf_fields' );

/**
 * Мета-бокс «Галерея зображень» без ACF Pro
 */
function sg_service_gallery_add_metabox() {
    add_meta_box(
        'sg_service_gallery',
        __( 'Галерея зображень послуги', 'services-grid' ),
        'sg_service_gallery_metabox_callback',
        'service',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'sg_service_gallery_add_metabox' );

function sg_service_gallery_metabox_callback( $post ) {
    wp_nonce_field( 'sg_save_service_gallery', 'sg_service_gallery_nonce' );

    $value = get_post_meta( $post->ID, '_sg_service_gallery_ids', true );
    $ids   = array();

    if ( ! empty( $value ) ) {
        $ids = array_filter( array_map( 'intval', explode( ',', $value ) ) );
    }
    ?>
    <div id="sg-service-gallery-wrapper">
        <input type="hidden" id="sg_service_gallery_ids" name="sg_service_gallery_ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" />
        <div class="sg-service-gallery-images">
            <?php if ( ! empty( $ids ) ) : ?>
                <?php foreach ( $ids as $attachment_id ) :
                    $thumb = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
                    if ( ! $thumb ) {
                        continue;
                    }
                    ?>
                    <div class="sg-service-gallery-item" data-id="<?php echo esc_attr( $attachment_id ); ?>">
                        <img src="<?php echo esc_url( $thumb[0] ); ?>" alt="" />
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <p>
            <button type="button" class="button sg-service-gallery-add">
                <?php esc_html_e( 'Додати зображення', 'services-grid' ); ?>
            </button>
            <button type="button" class="button sg-service-gallery-clear">
                <?php esc_html_e( 'Очистити галерею', 'services-grid' ); ?>
            </button>
        </p>
        <p class="description">
            <?php esc_html_e( 'Галерея реалізована без ACF Pro: виберіть одне або декілька зображень через медіа-бібліотеку.', 'services-grid' ); ?>
        </p>
    </div>
    <?php
}

/**
 * Збереження галереї
 */
function sg_service_gallery_save( $post_id ) {

    if ( ! isset( $_POST['sg_service_gallery_nonce'] ) || ! wp_verify_nonce( $_POST['sg_service_gallery_nonce'], 'sg_save_service_gallery' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['post_type'] ) && 'service' === $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    $ids_raw = isset( $_POST['sg_service_gallery_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['sg_service_gallery_ids'] ) ) : '';
    $ids     = array_filter( array_map( 'intval', explode( ',', $ids_raw ) ) );

    if ( ! empty( $ids ) ) {
        update_post_meta( $post_id, '_sg_service_gallery_ids', implode( ',', $ids ) );
    } else {
        delete_post_meta( $post_id, '_sg_service_gallery_ids' );
    }
}
add_action( 'save_post_service', 'sg_service_gallery_save' );

/**
 * Автозаповнення excerpt, якщо його не ввели вручну
 */
add_action( 'save_post_service', 'sg_services_auto_excerpt', 20, 2 );
function sg_services_auto_excerpt( $post_id, $post ) {

    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    if ( ! empty( $post->post_excerpt ) ) {
        return;
    }

    $content = strip_tags( $post->post_content );
    $content = preg_replace( '/\s+/', ' ', $content );
    $words   = explode( ' ', $content );
    $excerpt = implode( ' ', array_slice( $words, 0, 25 ) );

    remove_action( 'save_post_service', 'sg_services_auto_excerpt', 20 );
    wp_update_post( array(
        'ID'           => $post_id,
        'post_excerpt' => $excerpt,
    ) );
    add_action( 'save_post_service', 'sg_services_auto_excerpt', 20, 2 );
}

/**
 * Реєстрація фронтових стилів/скриптів
 */
function sg_register_assets() {
    wp_register_style(
        'sg-services-grid',
        SG_SERVICES_PLUGIN_URL . 'assets/css/services-grid.css',
        array(),
        SG_SERVICES_PLUGIN_VERSION
    );

    wp_register_script(
        'sg-services-grid',
        SG_SERVICES_PLUGIN_URL . 'assets/js/services-grid.js',
        array( 'jquery' ),
        SG_SERVICES_PLUGIN_VERSION,
        true
    );

    wp_localize_script(
        'sg-services-grid',
        'ServicesGridSettings',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'services_grid_nonce' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'sg_register_assets' );

/**
 * Admin scripts для галереї
 */
function sg_admin_assets( $hook ) {
    global $post;

    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
        return;
    }

    if ( ! $post || 'service' !== $post->post_type ) {
        return;
    }

    wp_enqueue_media();

    wp_enqueue_style(
        'sg-services-admin',
        SG_SERVICES_PLUGIN_URL . 'assets/css/services-grid-admin.css',
        array(),
        SG_SERVICES_PLUGIN_VERSION
    );

    wp_enqueue_script(
        'sg-services-admin',
        SG_SERVICES_PLUGIN_URL . 'assets/js/services-grid-admin.js',
        array( 'jquery' ),
        SG_SERVICES_PLUGIN_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'sg_admin_assets' );

/**
 * Отримати WP_Query для Services
 */
function sg_get_services_query( $page = 1, $min_price = null, $max_price = null ) {

    $args = array(
        'post_type'      => 'service',
        'post_status'    => 'publish',
        'posts_per_page' => SG_SERVICES_PER_PAGE,
        'paged'          => max( 1, (int) $page ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $meta_query = array();

    if ( $min_price !== null && $min_price !== '' ) {
        $meta_query[] = array(
            'key'     => 'service_price',
            'value'   => floatval( $min_price ),
            'type'    => 'NUMERIC',
            'compare' => '>=',
        );
    }

    if ( $max_price !== null && $max_price !== '' ) {
        $meta_query[] = array(
            'key'     => 'service_price',
            'value'   => floatval( $max_price ),
            'type'    => 'NUMERIC',
            'compare' => '<=',
        );
    }

    if ( ! empty( $meta_query ) ) {
        if ( count( $meta_query ) === 1 ) {
            $args['meta_query'] = $meta_query;
        } else {
            $args['meta_query'] = array_merge(
                array(
                    'relation' => 'AND',
                ),
                $meta_query
            );
        }
    }

    return new WP_Query( $args );
}

/**
 * Рендер карток послуг
 */
function sg_output_services_cards( $query ) {
    if ( ! $query->have_posts() ) {
        echo '<p class="sg-no-services">' . esc_html__( 'Послуги не знайдено.', 'services-grid' ) . '</p>';
        return;
    }

    while ( $query->have_posts() ) {
        $query->the_post();

        $post_id = get_the_ID();
        $title   = get_the_title();
        $excerpt = get_the_excerpt();

        $price = function_exists( 'get_field' ) ? get_field( 'service_price', $post_id ) : '';

        $meta_val    = get_post_meta( $post_id, '_sg_service_gallery_ids', true );
        $gallery_ids = array();

        if ( ! empty( $meta_val ) ) {
            $gallery_ids = array_filter( array_map( 'intval', explode( ',', $meta_val ) ) );
        }

        if ( empty( $gallery_ids ) && has_post_thumbnail( $post_id ) ) {
            $thumb_id = get_post_thumbnail_id( $post_id );
            if ( $thumb_id ) {
                $gallery_ids = array( $thumb_id );
            }
        }
        ?>
        <div class="sg-card">
            <div class="sg-card-inner">
                <?php if ( ! empty( $gallery_ids ) ) : ?>
                    <div class="sg-slider" data-index="0">
                        <div class="sg-slider-track">
                            <?php foreach ( $gallery_ids as $attachment_id ) :
                                $src = wp_get_attachment_image_url( $attachment_id, 'medium_large' );
                                if ( ! $src ) {
                                    continue;
                                }
                                $alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                                if ( ! $alt ) {
                                    $alt = $title;
                                }
                                ?>
                                <div class="sg-slide">
                                    <img
                                        src="<?php echo esc_url( $src ); ?>"
                                        alt="<?php echo esc_attr( $alt ); ?>"
                                        loading="lazy"
                                    />
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ( count( $gallery_ids ) > 1 ) : ?>
                            <button type="button" class="sg-slider-nav sg-prev" aria-label="<?php esc_attr_e( 'Попереднє зображення', 'services-grid' ); ?>">‹</button>
                            <button type="button" class="sg-slider-nav sg-next" aria-label="<?php esc_attr_e( 'Наступне зображення', 'services-grid' ); ?>">›</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="sg-card-content">
                    <h3 class="sg-card-title">
                        <?php echo esc_html( $title ); ?>
                    </h3>

                    <?php if ( $excerpt ) : ?>
                        <div class="sg-card-excerpt">
                            <?php echo wp_kses_post( wpautop( $excerpt ) ); ?>
                        </div>
                    <?php endif; ?>

                    <div class="sg-card-price-row">
                        <?php if ( $price !== '' && $price !== null ) : ?>
                            <span class="sg-card-price-label">
                                <?php esc_html_e( 'Ціна:', 'services-grid' ); ?>
                            </span>
                            <span class="sg-card-price-value">
                                <?php
                                echo esc_html(
                                    sprintf(
                                        __( '%s грн', 'services-grid' ),
                                        number_format_i18n( $price, 0 )
                                    )
                                );
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    wp_reset_postdata();
}

/**
 * Шорткод [services_grid]
 */
function sg_services_grid_shortcode( $atts ) {
    wp_enqueue_style( 'sg-services-grid' );
    wp_enqueue_script( 'sg-services-grid' );

    $atts = shortcode_atts(
        array(),
        $atts,
        'services_grid'
    );

    $query    = sg_get_services_query( 1 );
    $has_more = ( $query->max_num_pages > 1 );

    ob_start();
    ?>
    <div class="sg-services-wrapper" data-current-page="1">
        <form class="sg-filter-form" onsubmit="return false;">
            <div class="sg-filter-fields">
                <label>
                    <span><?php esc_html_e( 'Мін. ціна', 'services-grid' ); ?></span>
                    <input type="number" name="min_price" min="0" step="1" />
                </label>
                <label>
                    <span><?php esc_html_e( 'Макс. ціна', 'services-grid' ); ?></span>
                    <input type="number" name="max_price" min="0" step="1" />
                </label>
                <button type="button" class="sg-filter-apply">
                    <?php esc_html_e( 'Застосувати фільтр', 'services-grid' ); ?>
                </button>
            </div>
        </form>

        <div class="sg-grid">
            <?php sg_output_services_cards( $query ); ?>
        </div>

        <?php if ( $has_more ) : ?>
            <button type="button" class="sg-load-more">
                <?php esc_html_e( 'Завантажити ще', 'services-grid' ); ?>
            </button>
        <?php endif; ?>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode( 'services_grid', 'sg_services_grid_shortcode' );

/**
 * AJAX handler для пагінації та фільтра
 */
function sg_ajax_services_grid() {
    check_ajax_referer( 'services_grid_nonce', 'security' );

    $page      = isset( $_POST['page'] ) ? max( 1, (int) $_POST['page'] ) : 1;
    $min_price = isset( $_POST['min_price'] ) ? sanitize_text_field( wp_unslash( $_POST['min_price'] ) ) : '';
    $max_price = isset( $_POST['max_price'] ) ? sanitize_text_field( wp_unslash( $_POST['max_price'] ) ) : '';

    $query = sg_get_services_query( $page, $min_price, $max_price );

    ob_start();
    sg_output_services_cards( $query );
    $html = ob_get_clean();

    $has_more = ( $query->max_num_pages > $page );

    wp_send_json_success(
        array(
            'html'     => $html,
            'has_more' => $has_more,
        )
    );
}
add_action( 'wp_ajax_services_grid_load', 'sg_ajax_services_grid' );
add_action( 'wp_ajax_nopriv_services_grid_load', 'sg_ajax_services_grid' );
