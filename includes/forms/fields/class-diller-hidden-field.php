<?php
/**
 * Standard hidden field.
 *
 * @since    2.0.0
 *
 * @extends Diller_Base_Field
 *
 */

class Diller_Hidden_Field extends Diller_Base_Field {

	/**
	 * Print out field HTML - in this case intentionally empty.
	 */
	public function title() {}

	/**
	 * Print out field HTML.
	 */
	public function html() {
		?>

		<input <?php $this->id_attr(); ?>  type="hidden" <?php $this->name_attr(); ?>  value="<?php echo esc_attr($this->get_value()); ?>" />

        <?php
	}

	/**
	 * Print out a field.
	 */
	public function display() {
	    $this->html();
	}
}
