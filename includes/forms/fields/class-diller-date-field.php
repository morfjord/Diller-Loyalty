<?php
/**
 * Date picker meta box field.
 *
 * @since 2.0.0
 *
 * @extends Diller_Base_Field
 *
 */

class Diller_Date_Field extends Diller_Base_Field {

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
				'pattern'  => '',
				'data_date_format'  => '',
                'data_date'  => '',
			)
		);
	}

	/**
     * Override parent function to output value in the right format
	 * @return false|mixed|string
	 */
	public function get_value() {
		$value = parent::get_value();
        return !empty($value)? date('Y-m-d', strtotime($value)) : '';
	}


	/**
	 * Print out field HTML.
	 */
	public function html() {
		$attrs = array();
		$attrs[] = '' !== $this->args['step'] ? sprintf( 'step="%g"', $this->args['step'] ) : '';
		$attrs[] = '' !== $this->args['min'] ? sprintf( 'min="%g"', $this->args['min'] ) : '';
		$attrs[] = '' !== $this->args['max'] ? sprintf( 'max="%s"', $this->args['max'] ) : '';
		$attrs[] = '' !== $this->args['pattern'] ? sprintf( 'pattern="%s"', $this->args['pattern'] ) : '';
		$attrs[] = '' !== $this->args['data_date_format'] ? sprintf( 'data-date-format="%s"', $this->args['data_date_format'] ) : '';
		$attrs[] = '' !== $this->args['data_date'] ? sprintf( 'data-date="%s"', $this->args['data_date'] ) : '';
		?>

        <input type="date" <?php $this->id_attr(); ?>
            value="<?php echo esc_attr( $this->get_value() ); ?>"
            <?php echo esc_attr(implode( ' ', $attrs )); ?>
            <?php $this->boolean_attr(); ?>
            <?php $this->class_attr( '' ); ?>
            <?php $this->name_attr(); ?>
        />
		<?php
	}
}
