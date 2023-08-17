<?php

function get_partial_view( $slug, $name, $params = array() ) {
	$partial = '';
	$name = (string)$name;

	if ( '' !== $name ) {
		$partial = "{$slug}-{$name}.php";
	}

	// Save params to globals
	$GLOBALS['diller_admin_param'] = $params;

	include_once( DILLER_LOYALTY_PATH . 'admin/partials/' . $partial );

	// Empty params to prevent some possible bugs
	$GLOBALS['diller_admin_param'] = array();
}

function get_partial_view_param( $param, $default = false) {
	return $GLOBALS['diller_admin_param'][ $param ] ?? $default;
}

function get_partial_view_data() {
	return $GLOBALS['diller_admin_param'] ?? array();
}