<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://diller.no/contact-us
 * @since      2.0.0
 *
 * @package    Loyalty
 * @subpackage Loyalty/admin/partials
 */

// Exit if accessed directly
if ( ! defined( "ABSPATH" ) ) {
	exit;
}

$store_settings = get_partial_view_data();

$default_date_placeholder = get_partial_view_param("default_date_placeholder");
$default_postal_code_format = get_partial_view_param("default_postal_code_format"); // See https://www.geonames.org/postal-codes/ for formats
$stamps_enabled = get_partial_view_param("stamps_enabled");
$enable_recaptcha = get_partial_view_param("enable_recaptcha");
$min_enrollment_age = get_partial_view_param("min_enrollment_age");
$join_checkboxes_placement = get_partial_view_param("join_checkboxes_placement");

$phone_configs = get_partial_view_param("phone");
$enable_phone_number_lookup = $phone_configs["enable_number_lookup"];
$phone_country_option = $phone_configs["country_option"];
$phone_countries = $phone_configs["countries"];
$preferred_countries = $phone_configs["preferred_countries"];
$default_country_code = $phone_configs["default_country_code"];
$intl_tel_input_plugin_enabled = $phone_configs["intl_tel_input_plugin_enabled"];
$test_mode_enabled = get_partial_view_param("test_mode_enabled");

$all_country_options = array(
    "all" => __("Allow phone numbers from all countries", "diller-loyalty"),
    "all_except" => __("Allow phone numbers from all countries, except for…", "diller-loyalty"),
    "specific" => __("Allow phone numbers from specific countries", "diller-loyalty"),
);

?>

<form id="settings_form" class="diller-form" autocomplete="off" method="post" action="">
    <div class="diller-tab-container" data-tab-name="settings">
        <div class="diller-mb-3">
            <h2><?php esc_html_e( "Phone", "diller-loyalty" ); ?></h2>
            <div id="diller-forms-description">
                <p>Define and configure phone related settings</p>
            </div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="enable_phone_number_lookup"><?php esc_html_e( "Phone lookup - 1881.no", "diller-loyalty" ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="phone[enable_number_lookup]" id="enable_phone_number_lookup" value="1" <?php checked($enable_phone_number_lookup === true); ?> >
                            <?php esc_html_e( "Enable", "diller-loyalty" ); ?><br />
                            <p class="description"><?php esc_html_e( "If checked, Diller will try to fetch name and address automatically from 1881.no, by using the phone number.", "diller-loyalty" ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( "Default phone country code", "diller-loyalty" ); ?></th>
                        <td>
                            <input type="text" name="phone[default_country_code]" id="phone_default_country_code" class="small-text" value="<?php echo esc_attr( $default_country_code ); ?>" /><br />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="phone_intl_plugin_input_enabled"><?php esc_html_e( "Enhanced phone field", "diller-loyalty" ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="phone[intl_tel_input_plugin_enabled]" id="phone_intl_plugin_input_enabled" value="1" <?php checked( $intl_tel_input_plugin_enabled === true); ?>
                                   data-show-hide="tr_phone_countries,tr_phone_preferred_countries,tr_phone_country_options" >
			                <?php esc_html_e( "Enable", "diller-loyalty" ); ?><br />
                            <p class="description"><?php esc_html_e( "If checked, form displays an enhanced phone input field, with dropdown to pick country codes and validate phone numbers", "diller-loyalty" ); ?></p>
                        </td>
                    </tr>
                    <tr id="tr_phone_country_options" style="<?php echo esc_attr(!$intl_tel_input_plugin_enabled ? "display: none;" : ""); ?>">
                        <th><?php esc_html_e( "Phone numbers allowed", "diller-loyalty" ); ?></th>
                        <td>
                            <select name="phone[country_option]" id="phone_country_options" class="" tabindex="-1" aria-hidden="true" data-select2="phone_countries">
                                <?php foreach ( $all_country_options as $key => $label ) : ?>
                                    <option <?php selected( $key, $phone_country_option ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e( "This option lets you define countries for which phone numbers are allowed or not.", "diller-loyalty" ); ?></p>
                        </td>
                    </tr>
                    <tr id="tr_phone_countries" style="<?php echo esc_attr( "all" === $phone_country_option || !$intl_tel_input_plugin_enabled ? "display: none;" : ""); ?>">
                        <th><?php esc_html_e( "Countries", "diller-loyalty" ); ?></th>
                        <td>
                            <select id="phone_countries" name="phone[countries][]" multiple>
                            <?php foreach ( WC()->countries->get_countries() as $code => $label ) : ?>
                                <option <?php selected( in_array(strtolower($code), $phone_countries)); ?> value="<?php echo esc_attr( strtolower($code) ); ?>"><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                            </select>
                            <p class="description"><?php // Auto-filled with jquery, as this is dynamically displayed ?></p>
                        </td>
                    </tr>
                    <tr id="tr_phone_preferred_countries" style="<?php echo esc_attr( "specific" === $phone_country_option || !$intl_tel_input_plugin_enabled ? "display: none;" : ""); ?>">
                        <th><?php esc_html_e( "Preferred countries", "diller-loyalty" ); ?></th>
                        <td>
                            <select id="phone_preferred_countries" name="phone[preferred_countries][]" multiple>
	                            <?php foreach ( WC()->countries->get_countries() as $code => $label ) : ?>
                                    <option <?php selected( in_array(strtolower($code), $preferred_countries)); ?> value="<?php echo esc_attr( strtolower($code) ); ?>"><?php echo esc_html( $label ); ?></option>
	                            <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e( "Choose one or more countries, to display on the top of the list", "diller-loyalty" ); ?></p>
                        </td>
                    </tr>
                </table>
            </div><!-- end of inside -->
        </div>

        <div class="diller-mb-3">
            <h2><?php esc_html_e( "Formats", "diller-loyalty" ); ?></h2>
            <div id="diller-forms-description">
                <p><?php esc_html_e( "Configure date formats, etc", "diller-loyalty" ); ?></p>
            </div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e( "Default date placeholder", "diller-loyalty" ); ?></th>
                        <td>
                            <input type="text" id="default_date_placeholder" class="regular-text" name="default_date_placeholder" value="<?php echo esc_attr( $default_date_placeholder ); ?>" /><br />
                            <p class="description">
	                            <?php printf(
	                                /* translators: 1: new line break */
		                            esc_html__( 'The default placeholder for date input fields to provide a hint on how to type the date.%1$sEg. Norway: dd.mm.åååå, Sweden: åååå-mm-dd, UK: dd/mm/yyyy, US: mm/dd/yyyyetc.', "diller-loyalty" ),
		                            '<br/>'
	                            ); ?>
                            </p>
                        </td>
                    </tr>
                    <!--<tr>
                        <th><?php /*esc_html_e( "Default postal code format", "diller-loyalty" ); */?></th>
                        <td>
                            <input type="text" id="default_postal_code_format" class="regular-text" name="default_postal_code_format" value="<?php /*echo esc_attr( $default_postal_code_format ); */?>" /><br />
                            <p class="description"><?php /*esc_html_e( "If not empty, we use this as a The default postal code format, to use in date input fields. Use # for number and X for letters. Eg. Norway: ####, Portugal: ####-###, UK: ", "diller-loyalty" ); */?></p>
                        </td>
                    </tr>-->
                </table>
            </div><!-- end of inside -->
        </div>

        <div class="diller-mb-3">
            <h2><?php esc_html_e( "My Account", "diller-loyalty" ); ?></h2>
            <div id="diller-forms-description">
                <p><?php esc_html_e( "Enable and disable features for my account", "diller-loyalty"); ?></p>
            </div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="stamps_enabled"><?php esc_html_e( "Enable stamps usage", "diller-loyalty" ); ?></label>
                        </th>
                        <td>
                            <input name="stamps_enabled" type="checkbox" id="stamps_enabled" value="1" <?php checked($stamps_enabled === true); ?> >
			                <?php esc_html_e( "Enable", "diller-loyalty" ); ?><br />
                            <p class="description"><?php esc_html_e( "If checked, Stamps feature will be available for this store.", "diller-loyalty" ); ?></p>
                        </td>
                    </tr>
                </table>
            </div><!-- end of inside -->
        </div>

        <div class="diller-mb-3">
            <h2><?php esc_html_e( "Other settings", "diller-loyalty" ); ?></h2>
            <div id="diller-forms-description">
                <p><?php esc_html_e( "Other settings for customizing Loyalty Program enrollment", "diller-loyalty"); ?></p>
            </div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e( "Minimum age for enrollment", "diller-loyalty" ); ?></th>
                        <td>
                            <input type="number" id="min_enrollment_age" class="small-text" name="min_enrollment_age" value="<?php echo esc_attr( $min_enrollment_age ); ?>" /><br />
                            <p class="description"><?php esc_html_e( "The minimum age allowed, for enrolling the Loyalty Program", "diller-loyalty" ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="checkboxes_placement[]"><?php esc_html_e( "Display \"Join Loyalty Program\" checkboxes", "diller-loyalty" ); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <input name="join_checkboxes_placement" type="radio" id="checkbox_placement_billing_form" value="billing" <?php checked($join_checkboxes_placement === 'billing'); ?> >
	                            <?php esc_html_e( "After billing details", "diller-loyalty" ); ?><br />
                                <p class="description"><?php esc_html_e( "Display checkboxes for joining the Loyalty Program right after the billing fields, at checkout. This is the default setting.", "diller-loyalty" ); ?></p>
                                <br />
                            </fieldset>
                            <fieldset>
                                <input name="join_checkboxes_placement" type="radio" id="checkbox_placement_terms" value="terms" <?php checked($join_checkboxes_placement === 'terms'); ?> >
	                            <?php esc_html_e( "Before terms & conditions", "diller-loyalty" ); ?><br />
                                <p class="description"><?php esc_html_e( "Display checkboxes for joining the Loyalty Program right before the terms and conditions checkbox field, at checkout.", "diller-loyalty" ); ?></p>
                            </fieldset>
                        </td
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="test_mode_enabled"><?php esc_html_e( "Test-mode", "diller-loyalty" ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="test_mode_enabled" id="test_mode_active" value="1" <?php checked($test_mode_enabled === true); ?> >
			                <?php esc_html_e( "Enable", "diller-loyalty" ); ?><br />
                            <p class="description"><?php esc_html_e( "If checked, orders will be sent towards Diller test environment instead of the live environment.", "diller-loyalty" ); ?></p>
                        </td>
                    </tr>
                </table>
            </div><!-- end of inside -->
        </div>

        <p class="submit">
            <input type="submit" id="diller-settings-form-submit" name="diller_admin_settings_submit" class="button-primary" value="<?php esc_attr_e( "Save Settings", "diller-loyalty" ); ?>" />
		    <?php wp_nonce_field( "diller_save_admin_settings", "diller_admin_settings_nonce" ); ?>
        </p>

    </div>
</form>
