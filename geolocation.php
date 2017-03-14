<?php
/**
 * Plugin Name: Geolocation
 * Plugin URI: https://wordpress.org/plugins/geolocation/
 * Description: Displays post geotag information on an embedded map.
 * Version: 1.0.0
 * Author: Chris Boyd
 * Author URI: http://geo.chrisboyd.net
 * License: GPL2
 */

namespace TheFrosty;

/**
 * Class Geolocation
 *
 * @package TheFrosty
 */
class Geolocation {

    const VERSION = '1.0.0';
    const PREFIX = 'geolocation';
    const OPTION_GROUP = self::PREFIX . '-settings-group';
    const SHORTCODE = self::PREFIX;

    /** Singleton *************************************************************/
    private static $instance;

    /**
     * @return Geolocation
     */
    public static function init() {
        if ( ! isset( self::$instance ) || ! ( self::$instance instanceof Geolocation ) ) {
            self::$instance = new self;
            self::$instance->setup();
            self::$instance->hooks();
        }

        do_action( 'geolocation_loaded', self::$instance );

        return self::$instance;
    }

    public static function activation_hook() {
        self::init()->register_settings();
        add_option( 'geolocation_map_width', '350' );
        add_option( 'geolocation_map_height', '150' );
        add_option( 'geolocation_default_zoom', '16' );
        add_option( 'geolocation_map_position', 'after' );
        add_option( 'geolocation_wp_pin', '0' );
    }

    protected function setup() {
        defined( 'GEOLOCATION__FILE' ) || define( 'GEOLOCATION__FILE', __FILE__ );
    }

    protected function hooks() {
        add_action( 'init', function() {
            if ( is_admin() ) {
                add_action( 'admin_enqueue_scripts', [ $this, 'register_scripts' ] );
                add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
                add_action( 'admin_menu', [ $this, 'add_options_page' ] );
                add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
                add_action( 'save_post', [ $this, 'save_post' ], 10, 3 );
            } else {
                add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
                add_action( 'wp_enqueue_scripts', [ $this, 'frontend_scripts' ] );
                add_action( 'wp_footer', [ $this, 'add_geolocation_html' ] );

                add_filter( 'the_content', [ $this, 'display_location' ], 5 );
            }
            add_filter( 'script_loader_tag', [ $this, 'script_loader_tag' ], 10, 3 );
        } );
    }

    function script_loader_tag( $tag, $handle, $src ) {
        $defer_scripts = [
            'google-maps',
            'geolocation-admin',
            'geolocation',
        ];

        if ( in_array( $handle, $defer_scripts, true ) ) {
            return str_replace( '></script>', ' async defer></script>', $tag );
        }

        return $tag;
    }

    public function register_scripts() {
        wp_register_script(
            'google-maps',
            '//maps.googleapis.com/maps/api/js',
            null,
            null,
            true
        );

        wp_register_script(
            'geolocation-admin',
            plugins_url( 'assets/js/admin.js', __FILE__ ),
            [ 'google-maps', 'jquery' ],
            self::VERSION,
            true
        );

        wp_register_script(
            'geolocation-settings',
            plugins_url( 'assets/js/settings.js', __FILE__ ),
            [ 'jquery' ],
            self::VERSION,
            true
        );
    }

    /**
     * @param string $hook The current admin page.
     */
    public function admin_scripts( $hook ) {
        if ( ! in_array( $hook, [ 'post-new.php', 'post.php' ] ) ) {
            return;
        }

        wp_enqueue_script( 'geolocation-admin' );

        add_action( 'admin_footer', function() {
            global $post;

            if ( empty( $post ) || ! ( $post instanceof \WP_Post ) ) {
                return;
            }

            wp_localize_script(
                'geolocation-admin',
                'geolocation_object',
                [
                    'latitude' => $this->get_geo_longitude( $post->ID ),
                    'longitude' => $this->get_geo_latitude( $post->ID ),
                    'is_public' => $this->is_public( $post->ID ),
                    'is_enabled' => $this->is_enabled( $post->ID ),
                    'has_pin' => (bool) get_option( 'geolocation_wp_pin' ),
                    'zoom' => absint( get_option( 'geolocation_default_zoom' ) ),
                    'img_path' => esc_url( plugins_url( 'assets/img/zoom/', __FILE__ ) ),
                    'images' => [
                        'pin' => esc_url( plugins_url( 'img/wp_pin.png', __FILE__ ) ),
                        'pin_shadow' => esc_url( plugins_url( 'img/wp_pin_shadow.png', __FILE__ ) ),
                    ],
                ]
            );
        } );
    }

    public function add_options_page() {
        $hook = add_options_page(
            'Geolocation Settings',
            'Geolocation',
            'manage_options',
            'geolocation',
            function() {
                include __DIR__ . '/templates/settings-page.php';
            }
        );

        add_action( 'admin_print_scripts-' . $hook, function() {
            wp_enqueue_script( 'geolocation-settings' );
            wp_localize_script(
                'geolocation-settings',
                'geolocation',
                [
                    'img_path' => esc_url( plugins_url( 'assets/img/zoom/', __FILE__ ) ),
                ]
            );
        } );

        $this->register_settings();
    }

    public function add_meta_box() {
        add_meta_box(
            'geolocation',
            __( 'Geolocation', 'geolocation' ),
            function() {
                include __DIR__ . '/templates/meta-box.php';
            },
            'post',
            'advanced'
        );
    }

    /**
     * @param int $post_id
     * @param \WP_Post $post
     * @param bool $update
     *
     * @return int
     */
    public function save_post( $post_id, \WP_Post $post, $update ) {
        // Check authorization, permissions, autosave, etc
        if ( ! wp_verify_nonce( $_POST['geolocation_nonce'], plugin_basename( __FILE__ ) ) ) {
            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        if ( 'page' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }

        $latitude  = $this->clean_coordinate( $_POST['geolocation-latitude'] );
        $longitude = $this->clean_coordinate( $_POST['geolocation-longitude'] );
        $address   = $this->reverse_geocode( $latitude, $longitude );
        $public    = $_POST['geolocation-public'];
        $on        = $_POST['geolocation-on'];

        if ( ! empty( $this->clean_coordinate( $latitude ) ) &&
             ! empty( $this->clean_coordinate( $longitude ) )
        ) {
            update_post_meta( $post_id, 'geo_latitude', $latitude );
            update_post_meta( $post_id, 'geo_longitude', $longitude );

            if ( esc_html( $address ) !== '' ) {
                update_post_meta( $post_id, 'geo_address', $address );
            }

            if ( $on ) {
                update_post_meta( $post_id, 'geo_enabled', true );

                if ( $public ) {
                    update_post_meta( $post_id, 'geo_public', true );
                } else {
                    update_post_meta( $post_id, 'geo_public', false );
                }
            } else {
                update_post_meta( $post_id, 'geo_enabled', false );
                update_post_meta( $post_id, 'geo_public', true );
            }
        }

        return $post_id;
    }

    public function frontend_scripts() {
        wp_register_style(
            'geolocation',
            plugins_url( 'assets/css/style.css', __FILE__ ),
            [ 'google-jsapi', 'google-maps', 'jquery' ],
            self::VERSION,
            true
        );

        wp_register_script(
            'geolocation',
            plugins_url( 'assets/js/geolocation.js', __FILE__ ),
            [ 'google-jsapi', 'google-maps', 'jquery' ],
            self::VERSION,
            true
        );

        if ( is_singular( 'post' ) ) {
            global $post;

            if ( empty( $post ) || ! ( $post instanceof \WP_Post ) ) {
                return;
            }

            wp_enqueue_style( 'geolocation' );
            wp_enqueue_script( 'geolocation' );
            wp_localize_script(
                'geolocation',
                'geolocation_object',
                [
                    'latitude' => $this->get_geo_longitude( $post->ID ),
                    'longitude' => $this->get_geo_latitude( $post->ID ),
                    'is_public' => $this->is_public( $post->ID ),
                    'is_enabled' => $this->is_enabled( $post->ID ),
                    'has_pin' => (bool) get_option( 'geolocation_wp_pin' ),
                    'zoom' => absint( get_option( 'geolocation_default_zoom' ) ),
                    'images' => [
                        'pin' => esc_url( plugins_url( 'img/wp_pin.png', __FILE__ ) ),
                        'pin_shadow' => esc_url( plugins_url( 'img/wp_pin_shadow.png', __FILE__ ) ),
                    ],
                ]
            );
        }
    }

    public function add_geolocation_html() {
        echo '<div id="map" class="geolocation-map" style="width:' .
             absint( get_option( 'geolocation_map_width' ) ) . 'px;height:' .
             absint( get_option( 'geolocation_map_height' ) ) . 'px;"></div>';
    }

    function display_location( $content ) {
        global $post, $shortcode_tags;

        // Backup current registered shortcodes and clear them all out
        $orig_shortcode_tags = $shortcode_tags;
        $shortcode_tags      = [];
        $geo_shortcode       = '[' . self::SHORTCODE . ']';
        $latitude            = $this->get_geo_latitude( $post->ID );
        $longitude           = $this->get_geo_longitude( $post->ID );
        $address             = $this->get_geo_address( $post->ID );
        $public              = $this->is_public( $post->ID );
        $on                  = $this->is_enabled( $post->ID );

        if ( ( ! empty( $latitude ) && ! empty( $longitude ) ) &&
             ( $public === true && $on === true )
        ) {

            if ( empty( $address ) ) {
                $address = $this->reverse_geocode( $latitude, $longitude );
            }

            $html = '<a class="geolocation-link" href="javascript:;" id="geolocation-' .
                    $post->ID . '" name="' . $latitude . ',' .
                    $longitude . '">Posted from ' . esc_html( $address ) . '.</a>';

            switch ( esc_attr( get_option( 'geolocation_map_position' ) ) ) {
                case 'before':
                    $content = str_replace( $geo_shortcode, '', $content );
                    $content = $html . '<br/><br/>' . $content;
                    break;
                case 'after':
                    $content = str_replace( $geo_shortcode, '', $content );
                    $content = $content . '<br/><br/>' . $html;
                    break;
                case 'shortcode':
                    $content = str_replace( $geo_shortcode, $html, $content );
                    break;
            }
        } else {
            $content = str_replace( $geo_shortcode, '', $content );
        }

        // Put the original shortcodes back
        $shortcode_tags = $orig_shortcode_tags;

        return $content;
    }

    protected function register_settings() {
        $args = [ 'sanitize_callback' => 'intval' ];
        register_setting( self::OPTION_GROUP, 'geolocation_map_width', $args );
        register_setting( self::OPTION_GROUP, 'geolocation_map_height', $args );
        register_setting( self::OPTION_GROUP, 'geolocation_default_zoom', $args );
        register_setting( self::OPTION_GROUP, 'geolocation_map_position' );
        register_setting( self::OPTION_GROUP, 'geolocation_wp_pin' );
    }

    protected function check_minimum_settings() {
        if ( get_option( 'geolocation_map_width' ) == '0' ) {
            update_option( 'geolocation_map_width', '450' );
        }

        if ( get_option( 'geolocation_map_height' ) == '0' ) {
            update_option( 'geolocation_map_height', '200' );
        }

        if ( get_option( 'geolocation_default_zoom' ) == '0' ) {
            update_option( 'geolocation_default_zoom', '16' );
        }

        if ( get_option( 'geolocation_map_position' ) == '0' ) {
            update_option( 'geolocation_map_position', 'after' );
        }
    }

    /**
     * @param int $post_id
     *
     * @return string
     */
    private function get_geo_latitude( $post_id ) {
        return $this->clean_coordinate( $this->get_post_meta( $post_id, 'geo_latitude' ) );
    }

    /**
     * @param int $post_id
     *
     * @return string
     */
    private function get_geo_longitude( $post_id ) {
        return $this->clean_coordinate( $this->get_post_meta( $post_id, 'geo_longitude' ) );
    }

    /**
     * @param int $post_id
     *
     * @return string
     */
    private function get_geo_address( $post_id ) {
        return $this->get_post_meta( $post_id, 'geo_address' );
    }

    /**
     * @param int $post_id
     *
     * @return bool
     */
    private function is_enabled( $post_id ) {
        return $this->get_post_meta( $post_id, 'geo_enabled' ) === true;
    }

    /**
     * @param int $post_id
     *
     * @return bool
     */
    private function is_public( $post_id ) {
        return $this->get_post_meta( $post_id, 'geo_public' ) === true;
    }

    /**
     * @param int $post_id
     * @param string $key
     *
     * @return mixed
     */
    private function get_post_meta( $post_id, $key ) {
        return get_post_meta( $post_id, $key, true );
    }

    private function get_option() {
        return get_option();
    }

    /**
     * @param string $coordinate
     *
     * @return string
     */
    private function clean_coordinate( $coordinate ) {
        $pattern = '/^(\-)?(\d{1,3})\.(\d{1,15})/';
        preg_match( $pattern, $coordinate, $matches );

        return isset( $matches[0] ) ? $matches[0] : '';
    }

    /**
     * @param string $latitude
     * @param string $longitude
     *
     * @return string
     */
    private function reverse_geocode( $latitude, $longitude ) {
        $url = add_query_arg(
            [ 'latlng' => $latitude . ',' . $longitude ],
            'https://maps.google.com/maps/api/geocode/json'
        );

        $response = wp_remote_get( $url );

        if ( is_array( $response ) && ! is_wp_error( $response ) ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );

            foreach ( $body['results'] as $result ) {
                foreach ( $result['address_components'] as $address_component ) {
                    if ( in_array( 'locality', $address_component['types'], true ) &&
                         in_array( 'political', $address_component['types'], true )
                    ) {
                        $city = $address_component['long_name'];
                    } elseif (
                        in_array( 'administrative_area_level_1', $address_component['types'], true ) &&
                        in_array( 'political', $address_component['types'], true )
                    ) {
                        $state = $address_component['long_name'];
                    } elseif (
                        in_array( 'country', $address_component['types'], true ) &&
                        in_array( 'political', $address_component['types'], true )
                    ) {
                        $country = $address_component['long_name'];
                    }
                }
            }
        } else {
            return '';
        }

        if ( ! empty( $city ) && ! empty( $state ) && ! empty( $country ) ) {
            $address = $city . ', ' . $state . ', ' . $country;
        } elseif ( ! empty( $city ) && ! empty( $state ) ) {
            $address = $city . ', ' . $state;
        } elseif ( ! empty( $state ) && ! empty( $country ) ) {
            $address = $state . ', ' . $country;
        } elseif ( ! empty( $country ) ) {
            $address = $country;
        }

        return isset( $address ) ? $address : '';
    }
}

add_action( 'plugins_loaded', [ 'TheFrosty\Geolocation', 'init' ] );
register_activation_hook( __FILE__, [ 'TheFrosty\Geolocation', 'activation_hook' ] );
