<?php
/**
 * Standard checkbox field.
 *
 * @since    2.0.0
 *
 * @extends Diller_Base_Field
 *
 */

class Diller_Checkbox_Field extends Diller_Base_Field {

	/**
	 * Print out field HTML - in this case intentionally empty.
	 */
	public function title() {}

	public function get_value() {
		return $this->value;
	}

	public function display() {
	    ?>

        <div <?php $this->outer_class_attr(''); ?>>
            <label class="diller-checkbox" <?php $this->for_attr(); ?>>
                <?php $this->html(); ?>
            </label>
        </div>

        <?php
	}

	/**
	 * Print out field HTML.
	 */
	public function html() {

        $checked = isset($_POST[$this->get_name()]) && $this->get_value() == sanitize_text_field($_POST[$this->get_name()]);
		$checked = $checked || (Diller_Loyalty_Helpers::convert_bool_to_yes_no($this->get_value()) === 'Yes');

		?>

		<input type="checkbox" value="<?php echo esc_attr( $this->get_default_value() ); ?>"
            <?php $this->id_attr(); ?>
            <?php $this->boolean_attr(); ?>
            <?php $this->class_attr(); ?>
            <?php $this->name_attr(); ?>
            <?php checked($checked); ?>
        />
        <span class="diller-label-text"><?php echo $this->get_title(); ?></span>
		<?php
	}
}
