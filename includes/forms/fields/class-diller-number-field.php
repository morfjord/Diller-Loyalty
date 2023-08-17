<?php
/**
 * Standard input number field.
 *
 * @since    2.0.0
 *
 * @extends Diller_Base_Field
 *
 */

class Diller_Number_Field extends Diller_Base_Field {

	/**
	 * Get default arguments for field including custom parameters.
	 *
	 * @return array Default arguments for field.
	 */
	public function default_args() {
		return array_merge(
			parent::default_args(),
			array(
				'step' => '',
				'min'  => '',
				'max'  => '',
			)
		);
	}

	/**
	 * Print out field HTML.
	 */
	public function html() {
		$attrs = array();
		$attrs[] = '' !== $this->args['step'] ? sprintf( 'step="%g"', $this->args['step'] ) : '';
		$attrs[] = '' !== $this->args['min'] ? sprintf( 'min="%g"', $this->args['min'] ) : '';
		$attrs[] = '' !== $this->args['max'] ? sprintf( 'max="%g"', $this->args['max'] ) : '';
		?>

		<input type="number" <?php $this->id_attr(); ?> value="<?php echo esc_attr( $this->get_value() ); ?>"
            <?php echo esc_attr(implode( ' ', $attrs )); ?>
            <?php $this->boolean_attr(); ?>
            <?php $this->class_attr(' '); ?>
            <?php $this->name_attr(); ?>
			<?php //$this->required_attr(); ?>
        />

		<?php
	}
}
