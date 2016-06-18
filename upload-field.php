<?php

/*
Plugin Name: Custom Upload Field
Plugin URI: https://github.com/leymannx/wordpress-upload-field.git
Description: This WordPress plugin some custom fields to a custom post type named "Front Page". Additionally it sets an WYSIWYG editor for some of these fields. Multi-language support enabled.
Version: 1.0
Author: Norman Kämper-Leymann
Author URI: http://berlin-coding.de
Text Domain: upload-field
Domain Path: /lang
*/

/**
 * Adds custom fields (meta boxes) to Front Page CPT.
 */
function upload_field_init() {

	//	add_meta_box( $id = '', $title = '', $callback = '', $screen = '', $context = '' );
	add_meta_box( $id = 'wp_custom_attachment', $title = 'Custom Attachment', $callback = 'field_callback', $screen = 'page', $context = 'side' );
}

add_action( 'admin_init', 'upload_field_init' );

/**
 * Meta box callback.
 */
function field_callback() {

	wp_nonce_field( plugin_basename( __FILE__ ), 'wp_custom_attachment_nonce' );

	$html = '<p class="description">';
	$html .= 'Upload your PDF here.';
	$html .= '</p>';
	$html .= '<input type="file" id="wp_custom_attachment" name="wp_custom_attachment" value="" size="25" />';

	// Grab the array of file information currently associated with the post
	$doc = get_post_meta( get_the_ID(), 'wp_custom_attachment', TRUE );

	// Create the input box and set the file's URL as the text element's value
	$html .= '<input type="hidden" id="wp_custom_attachment_url" name="wp_custom_attachment_url" value=" ' . $doc['url'] . '" size="30" />';
	if ( $doc['url'] ) {
		$html .= '<div class="wp_custom_attachment_basename">' . basename( $doc['url'] ) . '</div>';
	}

	// Display the 'Delete' option if a URL to a file exists
	if ( strlen( trim( $doc['url'] ) ) > 0 ) {
		$html .= '<a href="javascript:;" id="wp_custom_attachment_delete">' . __( 'Delete File' ) . '</a>';
	} // end if

	echo $html;

} // end wp_custom_attachment

/**
 * Little script to do the attachment deleting.
 */
function add_custom_attachment_script() {

	wp_register_script( 'custom-attachment-script', plugin_dir_url( __FILE__ ) . 'js/custom_attachment.js' );
	wp_enqueue_script( 'custom-attachment-script' );

} // end add_custom_attachment_script

add_action( 'admin_enqueue_scripts', 'add_custom_attachment_script' );

/**
 * Save custom attachment.
 *
 * @param $id
 *
 * @return mixed
 */
function save_custom_meta_data( $id ) {

	/* --- security verification --- */
	if ( ! wp_verify_nonce( $_POST['wp_custom_attachment_nonce'], plugin_basename( __FILE__ ) ) ) {
		return $id;
	} // end if

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $id;
	} // end if

	if ( ! current_user_can( 'edit_page', $id ) ) {
		return $id;
	} // end if
	/* - end security verification - */

	// Make sure the file array isn't empty
	if ( ! empty( $_FILES['wp_custom_attachment']['name'] ) ) {

		// Setup the array of supported file types. In this case, it's just PDF.
		$supported_types = array( 'application/pdf' );

		// Get the file type of the upload
		$arr_file_type = wp_check_filetype( basename( $_FILES['wp_custom_attachment']['name'] ) );
		$uploaded_type = $arr_file_type['type'];

		// Check if the type is supported. If not, throw an error.
		if ( in_array( $uploaded_type, $supported_types ) ) {

			// Use the WordPress API to upload the file
			$upload = wp_upload_bits( $_FILES['wp_custom_attachment']['name'], NULL, file_get_contents( $_FILES['wp_custom_attachment']['tmp_name'] ) );

			if ( isset( $upload['error'] ) && $upload['error'] != 0 ) {
				wp_die( 'There was an error uploading your file. The error is: ' . $upload['error'] );
			} else {
				add_post_meta( $id, 'wp_custom_attachment', $upload );
				update_post_meta( $id, 'wp_custom_attachment', $upload );
			} // end if/else

		} else {
			wp_die( "The file type that you've uploaded is not a PDF." );
		} // end if/else

	} else {

		// Grab a reference to the file associated with this post
		$doc = get_post_meta( $id, 'wp_custom_attachment', TRUE );

		// Grab the value for the URL to the file stored in the text element
		$delete_flag = get_post_meta( $id, 'wp_custom_attachment_url', TRUE );

		// Determine if a file is associated with this post and if the delete flag has been set (by clearing out the input box)
		if ( strlen( trim( $doc['url'] ) ) > 0 && strlen( trim( $delete_flag ) ) == 0 ) {

			// Attempt to remove the file. If deleting it fails, print a WordPress error.
			if ( unlink( $doc['file'] ) ) {

				// Delete succeeded so reset the WordPress meta data
				update_post_meta( $id, 'wp_custom_attachment', NULL );
				update_post_meta( $id, 'wp_custom_attachment_url', '' );

			} else {
				wp_die( 'There was an error trying to delete your file.' );
			} // end if/el;se

		} // end if

	} // end if/else

} // end save_custom_meta_data

add_action( 'save_post', 'save_custom_meta_data' );

/**
 *
 */
function update_edit_form() {
	echo ' enctype="multipart/form-data"';
} // end update_edit_form

add_action( 'post_edit_form_tag', 'update_edit_form' );
