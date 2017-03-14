<?php

$zoom_image = get_option( 'geolocation_default_zoom' );
if ( get_option( 'geolocation_wp_pin' ) ) {
    $zoom_image = 'wp_' . $zoom_image . '.png';
} else {
    $zoom_image = $zoom_image . '.png';
}
?>
<style type="text/css">
    #Geo__zoom_level_sample {
        background: url('<?php echo esc_url(plugins_url('img/zoom/'.$zoom_image, GEOLOCATION__FILE)); ?>');
        width: 390px;
        height: 190px;
        border: 1px #999 solid;
    }

    #Geo__preload {
        display: none;
    }

    .dimensions strong {
        width: 50px;
        float: left;
    }

    .dimensions input {
        width: 50px;
        margin-right: 5px;
    }

    .zoom label {
        width: 50px;
        margin: 0 5px 0 2px;
    }

    .position label {
        margin: 0 5px 0 2px;
    }
</style>
<div class="wrap">
    <h2>Geolocation Plugin Settings</h2>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
    <?php settings_fields( \TheFrosty\Geolocation::OPTION_GROUP ); ?>
    <table class="form-table">
        <tr valign="top">
        <tr valign="top">
            <th scope="row">Dimensions</th>
            <td class="dimensions">
                <strong>Width:</strong>
                <input type="text" name="geolocation_map_width"
                       value="<?php echo esc_attr( get_option( 'geolocation_map_width' ) ); ?>"/>px<br/>
                <strong>Height:</strong>
                <input type="text" name="geolocation_map_height"
                       value="<?php echo esc_attr( get_option( 'geolocation_map_height' ) ); ?>"/>px
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Position</th>
            <td class="position">
                <input type="radio" id="geolocation_map_position_before"
                       name="geolocation_map_position"
                       value="before"<?php checked( get_option( 'geolocation_map_position' ), 'before' ); ?>>
                <label
                        for="geolocation_map_position_before">Before the post.</label><br/>

                <input type="radio" id="geolocation_map_position_after"
                       name="geolocation_map_position"
                       value="after"<?php checked( get_option( 'geolocation_map_position' ), 'after' ); ?>>
                <label
                        for="geolocation_map_position_after">After the post.</label><br/>
                <input type="radio" id="geolocation_map_position_shortcode"
                       name="geolocation_map_position"
                       value="shortcode"<?php checked( get_option( 'geolocation_map_position' ), 'shortcode' ); ?>>
                <label
                        for="geolocation_map_position_shortcode">Wherever I put the <strong>[geolocation]</strong>
                    shortcode.</label>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Default Zoom Level</th>
            <td class="zoom">
                <input type="radio" id="geolocation_default_zoom_globe"
                       name="geolocation_default_zoom"
                       value="1"<?php checked( get_option( 'geolocation_default_zoom' ), '1' ); ?>
                       onclick="Geolocation_Settings.swap_zoom_sample(this.id);"><label
                        for="geolocation_default_zoom_globe">Globe</label>

                <input type="radio" id="geolocation_default_zoom_country"
                       name="geolocation_default_zoom"
                       value="3"<?php checked( get_option( 'geolocation_default_zoom' ), '3' ); ?>
                       onclick="Geolocation_Settings.swap_zoom_sample(this.id);"><label
                        for="geolocation_default_zoom_country">Country</label>
                <input type="radio" id="geolocation_default_zoom_state"
                       name="geolocation_default_zoom"
                       value="6"<?php checked( get_option( 'geolocation_default_zoom' ), '6' ); ?>
                       onclick="Geolocation_Settings.swap_zoom_sample(this.id);"><label
                        for="geolocation_default_zoom_state">State</label>
                <input type="radio" id="geolocation_default_zoom_city"
                       name="geolocation_default_zoom"
                       value="9"<?php checked( get_option( 'geolocation_default_zoom' ), '9' ); ?>
                       onclick="Geolocation_Settings.swap_zoom_sample(this.id);"><label
                        for="geolocation_default_zoom_city">City</label>
                <input type="radio" id="geolocation_default_zoom_street"
                       name="geolocation_default_zoom"
                       value="16"<?php checked( get_option( 'geolocation_default_zoom' ), '16' ); ?>
                       onclick="Geolocation_Settings.swap_zoom_sample(this.id);"><label
                        for="geolocation_default_zoom_street">Street</label>
                <input type="radio" id="geolocation_default_zoom_block"
                       name="geolocation_default_zoom"
                       value="18"<?php checked( get_option( 'geolocation_default_zoom' ), '18' ); ?>
                       onclick="Geolocation_Settings.swap_zoom_sample(this.id);"><label
                        for="geolocation_default_zoom_block">Block</label>
                <br/>
                <div id="Geo__zoom_level_sample"></div>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"></th>
            <td class="position">
                <input type="checkbox" id="geolocation_wp_pin" name="geolocation_wp_pin"
                       value="1" <?php checked( get_option( 'geolocation_wp_pin' ), 1 ); ?>
                       onclick="Geolocation_Settings.pin_click();"><label for="geolocation_wp_pin">
                    Show your support for WordPress by using the WordPress map pin.</label>
            </td>
        </tr>
    </table>

    <?php submit_button(); ?>
    <input type="hidden" name="action" value="update"/>
    <input type="hidden" name="page_options"
           value="geolocation_map_width,geolocation_map_height,geolocation_default_zoom,geolocation_map_position,geolocation_wp_pin"/>
</form>

<div id="Geo__preload">
    <img src="<?php echo esc_url( plugins_url( 'assets/img/zoom/1.png', GEOLOCATION__FILE ) ); ?>"/>
    <img src="<?php echo esc_url( plugins_url( 'assets/img/zoom/3.png', GEOLOCATION__FILE ) ); ?>"/>
    <img src="<?php echo esc_url( plugins_url( 'assets/img/zoom/6.png', GEOLOCATION__FILE ) ); ?>"/>
    <img src="<?php echo esc_url( plugins_url( 'assets/img/zoom/9.png', GEOLOCATION__FILE ) ); ?>"/>
    <img src="<?php echo esc_url( plugins_url( 'assets/img/zoom/16.png', GEOLOCATION__FILE ) ); ?>"/>
    <img src="<?php echo esc_url( plugins_url( 'assets/img/zoom/18.png', GEOLOCATION__FILE ) ); ?>"/>

    <img src="<?php echo esc_url( plugins_url( 'assets/img/zoom/wp_1.png', GEOLOCATION__FILE ) ); ?>"/>
    <img src="<?php echo esc_url( plugins_url( 'assets/img/zoom/wp_3.png', GEOLOCATION__FILE ) ); ?>"/>
    <img src="<?php echo esc_url( plugins_url( 'assets/img/zoom/wp_6.png', GEOLOCATION__FILE ) ); ?>"/>
    <img src="<?php echo esc_url( plugins_url( 'assets/img/zoom/wp_9.png', GEOLOCATION__FILE ) ); ?>"/>
    <img src="<?php echo esc_url( plugins_url( 'assets/img/zoom/wp_16.png', GEOLOCATION__FILE ) ); ?>"/>
    <img src="<?php echo esc_url( plugins_url( 'assets/img/zoom/wp_18.png', GEOLOCATION__FILE ) ); ?>"/>
</div>
