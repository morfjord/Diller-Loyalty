<?php
    /**
     * Provide a admin area view for the plugin
     *
     * This file is used to markup the admin-facing aspects of the plugin.
     *
     * @link       https://diller.no/contact-us
     * @since      2.0.0
     *
     * @package    Diller_Loyalty
     * @subpackage Diller_Loyalty/admin/partials
     */

    // Exit if accessed directly
    if ( ! defined( "ABSPATH" ) ) {
        exit;
    }

    $store_settings = get_partial_view_data();
    $store_pin = get_partial_view_param("store_pin");
    $api_key = get_partial_view_param("api_key");

	?>

    <form autocomplete="off" id="diller_connect_form" method="post" action="">
        <div class="diller-tab-container" data-tab-name="connect">
            <h2>
                <?php esc_html_e( "Connect your store", "diller-loyalty" ); ?>
                <?php if(DillerLoyalty()->get_auth()->is_authenticated()): ?>
                    <span class="diller--success" style="font-size: smaller;color:#155724; font-weight: bold;">(<?php echo esc_html__("Connected successfully", "diller-loyalty" ); ?>)</span>
                <?php endif; ?>
            </h2>
            <div id="diller-forms-description">
                <p><?php esc_html_e('Contact your partner to get your API-Key and Store ID.','diller-loyalty'); ?></p>
            </div>

            <div class="inside">
                <table class="form-table">
                    <?php if(DillerLoyalty()->get_auth()->is_authenticated()): ?>
                    <tr>
                        <th scope="row"><?php esc_html_e( "Store name", "diller-loyalty" ); ?></th>
                        <td>
                            <input id="diller_store_name" type="text" class="regular-text" value="<?php echo esc_attr(DillerLoyalty()->get_store()->get_store_name()); ?>" readonly />
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th scope="row"><?php esc_html_e( "API-Key", "diller-loyalty" ); ?></th>
                        <td>
                            <input id="diller_x_api_key" type="password" class="regular-text" name="diller_x_api_key" value="<?php echo esc_attr( $api_key); ?>" /><br />
                            <p class="description"><?php esc_html_e( "Your API-Key (32 chars long)", "diller-loyalty" ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( "Store Pin", "diller-loyalty" ); ?></th>
                        <td>
                            <input id="diller_store_pin" type="text" class="regular-text" name="diller_store_pin" value="<?php echo esc_attr( $store_pin); ?>" /><br />
                            <p class="description"><?php esc_html_e( "Your store PIN (6-digit long)", "diller-loyalty" ); ?></p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" id="diller_connect_store_submit" name="diller_connect_store_submit" class="button-primary" value="<?php echo (DillerLoyalty()->get_auth()->is_authenticated() ? esc_attr_e( "Reconnect", "diller-loyalty" ) :  esc_attr_e( "Connect", "diller-loyalty" )); ?>" />
                    <?php wp_nonce_field( "diller_save_admin_settings", "diller_admin_settings_nonce" ); ?>
                </p>
            </div><!-- end of inside -->
        </div>
    </form>
