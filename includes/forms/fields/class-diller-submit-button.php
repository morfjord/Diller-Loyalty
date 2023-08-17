<?php
/**
 * Standard submit button.
 *
 * @since 2.0.0
 *
 */

class Diller_Submit_Button extends Diller_Form_Element{

	/**
	 * Diller_Submit_Button constructor.
	 *
	 * @param string $name Field name/ID.
	 * @param string $text Button text to display.
	 */
	public function __construct( $name, $text, $args = array()) {
	    parent::__construct($name, $text, $args);
	}


	/**
	 * Print out a field.
	 */
	public function display() {
		?>

        <button <?php $this->id_attr(); ?> <?php $this->boolean_attr(); ?> <?php $this->class_attr(); ?> type="submit">
			<?php echo esc_attr( $this->title ); ?>
        </button>

		<?php
	}

	public function enqueue_scripts() { }

	public function enqueue_styles() {}
}
