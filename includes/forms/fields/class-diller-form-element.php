<?php

abstract class Diller_Form_Element {

	/**
	 * Diller_Form_Element constructor.
	 *
	 * @param string $name Element name/ID.
	 * @param string $title Title to display in element.
	 * @param array  $args Optional. Element definitions/arguments.
	 */
	public function __construct( $name, $title, $args = array() ) {
		$this->id    = sprintf("%s-%s", $name, uniqid()); //Generate a dynamic unique id. Helpful for frontend JS code and avoid collisions with other plugins
		$this->name  = $name;
		$this->title = $title;
		$this->set_arguments( $args );
	}

	/**
	 * Establish baseline default arguments
	 *
	 * @return array Default arguments.
	 */
	public function default_args() {
		return array(
			'disabled'            => false,
			'class'               => ''
		);
	}

	/**
	 * Get the default args for the abstract element.
	 * These args are available to all elements.
	 *
	 * @return array $args
	 */
	public function get_default_args() {

		/**
		 * Filter the default arguments passed by an element class.
		 *
		 * @param array $args default element arguments.
		 * @param string $class Element class being called
		 */
		return $this->default_args();
	}

	/**
	 * Enqueue all scripts required by the element.
	 *
	 * @uses wp_enqueue_script()
	 */
	public function enqueue_scripts() {
	}

	/**
	 * Enqueue all styles required by the element.
	 *
	 * @uses wp_enqueue_style()
	 */
	public function enqueue_styles() {}


	/**
	 * Output the field input ID attribute.
	 *
	 */
	public function id_attr() {
		printf( 'id="%s"', esc_attr( $this->get_id() ) );
	}

	/**
	 * Get the unique field ID
	 *
	 */
	public function get_id() {
		return  $this->id;
	}

	/**
	 * Output HTML name attribute for a element.
	 *
	 * @see get_the_name_attr
	 *
	 * @param string $append Optional. Name to place.
	 */
	public function name_attr( $append = null ) {
		$name = $this->get_the_name_attr( $append );
		printf( 'name="%s"', esc_attr( $name ) );

	}

	/**
	 * Get the name attribute contents for a element.
	 *
	 * @param null $append Optional. Name to place.
	 * @return string Name attribute contents.
	 */
	public function get_the_name_attr( $append = null ) {

		$name = $this->name;
		if ( ! is_null( $append ) ) {
			$name .= $append;
		}
		return $name;

	}

	/**
	 * Output class attribute for a element.
	 *
	 * @param string $classes Optional. Classes to assign to the element.
	 */
	public function class_attr( $classes = '' ) {

		// Combine any passed-in classes and the ones defined in the arguments and sanitize them.
		$all_classes = array_unique( explode( ' ', $classes . ' ' . $this->args['class'] ) );
		$classes     = array_map( 'sanitize_html_class', array_filter( $all_classes ) );

		if( $classes = implode( ' ', $classes ) ) {
			echo 'class="'.esc_attr( $classes ).'"';
        }
	}

	/**
	 * Print one or more HTML5 attributes for a element.
	 *
	 * @param array $attrs Optional. Attributes to define in the element.
	 */
	public function boolean_attr( $attrs = array() ) {

		if ( $this->args['disabled'] ) {
			$attrs[] = 'disabled';
		}

		$attrs = array_filter( array_unique( $attrs ) );
		foreach ( $attrs as $attr ) {
			echo esc_html( $attr ) . '="' . esc_attr( $attr ) . '"';
		}

	}


	/**
	 * Print out the Element.
	 */
	public function display() {
	}


	/**
	 * Setup arguments for the class.
	 *
	 * @param $arguments
	 */
	public function set_arguments( $arguments ) {
		// Initially set arguments up.
		$this->args = wp_parse_args( $arguments, $this->get_default_args() );
	}
}
