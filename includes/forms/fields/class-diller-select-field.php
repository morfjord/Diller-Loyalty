<?php
/**
 * Standard select field.
 *
 * @supports "data_delegate"
 * @args
 *     'options'     => array Array of options to show in the select, optionally use data_delegate instead
 *     'allow_none'   => bool|string Allow no option to be selected (will place a "None" at the top of the select)
 *     'multiple'     => bool whether multiple can be selected
 *
 * @since    2.0.0
 *
 * @extends Diller_Base_Field
 *
 */

class Diller_Select_Field extends Diller_Base_Field {


	public function __construct() {

		$args = func_get_args();

		call_user_func_array( array( 'parent', '__construct' ), $args );
	}

	/**
	 * Get default arguments for field including custom parameters.
	 *
	 * @return array Default arguments for field.
	 */
	public function default_args() {
		return array_merge(
			parent::default_args(),
			array(
				'options'         => array(),
				'multiple'        => false,
				'allow_none'      => true,
			)
		);
	}

	
	/**
	 * Get options for field.
	 *
	 * @return mixed
	 */
	public function get_options() {

		if ( $this->has_data_delegate() ) {
			$this->args['options'] = $this->get_delegate_data();
		}

		return $this->args['options'];
	}

	/**
	 * Print out field HTML.
	 */
	public function html() {

		if ( $this->has_data_delegate() ) {
			$this->args['options'] = $this->get_delegate_data();
		}

		$val = (array) $this->get_value();
		$name = $this->get_the_name_attr();
		$name .= ! empty( $this->args['multiple'] ) ? '[]' : null;

		$none = is_string( $this->args['allow_none'] ) ? $this->args['allow_none'] : esc_html__( 'None', 'diller-loyalty' );

		?>

		<select
			<?php $this->id_attr(); ?>
			<?php $this->boolean_attr(); ?>
			<?php printf( 'name="%s"', esc_attr( $name ) ); ?>
			<?php echo ! empty( $this->args['multiple'] ) ? 'multiple' : '' ?>
			<?php $this->class_attr( '' ); ?>
			<?php //$this->required_attr(); ?> >

			<?php if ( $this->args['allow_none'] ) : ?>
				<option value=""><?php echo esc_html( $none ); ?></option>
			<?php endif; ?>

			<?php foreach ( $this->args['options'] as $value => $name ) : ?>
				<option <?php selected( in_array( $value, $val )) ?> value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?>

		</select>

		<?php
	}
}
