<?php

add_action( 'buddyforms_front_js_css_after_enqueue', 'buddyforms_pods_form_css' );

/**
 *
 */
function buddyforms_pods_form_css(){

	?>
	<style>
		ul.pods-form-fields.pods-dependency {
		list-style-type: none;
		margin-right: 10px;
		font-weight: bold;
		font-style: normal;
	</style>

<?php
}
