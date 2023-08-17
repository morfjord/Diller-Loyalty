<?php
/**
 * class Diller_Checkbox_Multi
 *
 * @since 1.1.0
 *
 * @extends Diller_Base_Fields
 *
 */

class Diller_Checkbox_Multi extends Diller_Base_Field {

	/**
	 * Print out a field.
	 */
	public function display() {
		?>
        <div <?php $this->outer_class_attr(); ?>>
            <label><?php echo esc_html( $this->get_title() ); ?></label>
			<?php $this->html(); ?>
        </div>
		<?php
	}

	/**
	 * Print out field HTML.
	 */
	public function html() {

		if ( $this->has_data_delegate() ) {
			$this->args['options'] = $this->get_delegate_data();
		}

		// No options, no can do
		if ( empty( $this->args['options'] ) ) {
			return;
		}

		$values = $this->get_values();

		foreach ( $this->args['options'] as $key => $label ) :
			$this->set_id(md5(sprintf("%s-%s", $this->get_id(), $key )));
			?>

            <label class="diller-checkbox" <?php $this->for_attr(); ?>>
				<input type="checkbox"
					<?php $this->id_attr(); ?>
					<?php $this->boolean_attr(); ?>
					<?php $this->class_attr(); ?>
					<?php $this->name_attr('[]'); ?>
					<?php $this->value_attr($key); ?>
					<?php checked( in_array($key, $values) ); ?>
				    <?php //$this->required_attr(); ?>
                />
                <span class="diller-label-text"><?php echo esc_html( $label ); ?></span>
            </label>

		<?php
		endforeach;
	}

	/**
	 * Get multiple values for the checkbox-multi field.
	 *
	 * @return array
	 */
	public function get_values() {
        return $this->values;
	}
}
