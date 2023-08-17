<?php
/**
 * Standard radio field.
 *
 * @since    2.0.0
 *
 * @extends Diller_Base_Field
 *
 */

class Diller_Radio_Field extends Diller_Base_Field {

	/**
	 * Get default arguments for field including custom parameters.
	 *
	 * @return array Default arguments for field.
	 */
	public function default_args() {
		return array_merge(
			parent::default_args(),
			array(
				'options' => array(),
			)
		);
	}


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

		foreach ( $this->args['options'] as $key => $value ) :
			$this->set_id(md5(sprintf("%s-%s", $this->get_id(), $key )));

        ?>

            <label class="diller-radio" <?php $this->for_attr(); ?>>
			    <input type="radio"
                    <?php $this->name_attr(); ?>
				    <?php $this->value_attr($key); ?>
                    <?php $this->id_attr(); ?>
                    <?php $this->boolean_attr(); ?>
                    <?php $this->class_attr(); ?>
                    <?php checked( $key, $this->get_value() ); ?>
				    <?php //$this->required_attr(); ?>
                />
                <span class="diller-label-text"><?php echo esc_html( $value ); ?></span>
			</label>

		<?php
        endforeach;
	}
}
