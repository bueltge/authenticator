<?php

/**
 * Protect Uploads
 *
 * @since  11/10/2012
 * @author fb
 */
class Authenticator_Protect_Upload {

	/**
	 * Constructor
	 *
	 * @return  void
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'check_get' ) );
	}

	/**
	 * Check the GET param file
	 *
	 * @return  void
	 */
	public function check_get() {

		if ( '' != $_GET[ 'file' ] ) {
			$this->get_file( $_GET[ 'file' ] );
		}
	}

	/**
	 * Check for access to current file
	 *
	 * @param  String $file
	 *
	 * @return Access
	 */
	public function get_file( $file ) {

		$upload   = wp_upload_dir();
		$the_file = $file;
		$file     = $upload[ 'basedir' ] . '/' . $file;

		if ( ! is_file( $file ) ) {
			status_header( 404 );
			die( '404 &#8212; File not found.' );
		} else {
			$image = get_posts( array(
				'post_type'  => 'attachment',
				'meta_query' => array(
					array( 'key' => '_wp_attached_file', 'value' => $the_file )
				)
			) );

			if ( 0 < count( $image ) && 0 < $image[ 0 ]->post_parent ) { // attachment found and parent available

				if ( post_password_required( $image[ 0 ]->post_parent ) ) // password for the post is not available
				{
					wp_die( get_the_password_form() );
				}// show the password form

				$status = get_post_meta( $image[ 0 ]->post_parent, '_inpsyde_protect_content', TRUE );

				if ( 1 == $status && ! is_user_logged_in() ) {
					wp_redirect( wp_login_url( $upload[ 'baseurl' ] . '/' . $the_file ) );
					die();
				}

			} else {
				// not a normal attachment check for thumbnail
				$filename = pathinfo( $the_file );
				$images   = get_posts( array(
					'post_type'  => 'attachment',
					'meta_query' => array(
						array(
							'key'     => '_wp_attachment_metadata',
							'compare' => 'LIKE',
							'value'   => $filename[ 'filename' ] . '.' . $filename[ 'extension' ]
						)
					)
				) );

				if ( 0 < count( $images ) ) {

					foreach ( $images as $single_image ) {
						$meta = wp_get_attachment_metadata( $single_image->ID );

						if ( 0 < count( $meta[ 'sizes' ] ) ) {
							$filepath = pathinfo( $meta[ 'file' ] );

							if ( $filepath[ 'dirname' ] == $filename[ 'dirname' ] ) {// current path of the thumbnail

								foreach ( $meta[ 'sizes' ] as $single_size ) {

									if ( $filename[ 'filename' ] . '.' . $filename[ 'extension' ] == $single_size[ 'file' ] ) {

										if ( post_password_required( $single_image->post_parent ) ) // password for the post is not available
										{
											wp_die( get_the_password_form() );
										}// show the password form

										die( 'dD' );

										$status = get_post_meta( $single_image->post_parent, '_inpsyde_protect_content', TRUE );

										if ( 1 == $status && ! is_user_logged_in() ) {
											wp_redirect( wp_login_url( $upload[ 'baseurl' ] . '/' . $the_file ) );
											die();
										}
									}

								}

							} // end if

						}

					} // end foreach

				}
			} // end if else

		} // end if else

		$mime = wp_check_filetype( $file );
		if ( FALSE === $mime[ 'type' ] && function_exists( 'mime_content_type' ) ) {
			$mime[ 'type' ] = mime_content_type( $file );
		}

		if ( $mime[ 'type' ] ) {
			$mimetype = $mime[ 'type' ];
		} else {
			$mimetype = 'image/' . substr( $file, strrpos( $file, '.' ) + 1 );
		}

		header( 'Content-type: ' . $mimetype ); // always send this
		if ( FALSE === strpos( $_SERVER[ 'SERVER_SOFTWARE' ], 'Microsoft-IIS' ) ) {
			header( 'Content-Length: ' . filesize( $file ) );
		}

		$last_modified = gmdate( 'D, d M Y H:i:s', filemtime( $file ) );
		$etag          = '"' . md5( $last_modified ) . '"';
		header( 'Last-Modified: ' . $last_modified . ' GMT' );
		header( 'ETag: ' . $etag );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );

		// Support for Conditional GET
		$client_etag = isset( $_SERVER[ 'HTTP_IF_NONE_MATCH' ] ) ? stripslashes( $_SERVER[ 'HTTP_IF_NONE_MATCH' ] ) : FALSE;

		if ( ! isset( $_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ] ) ) {
			$_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ] = FALSE;
		}

		$client_last_modified = trim( $_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ] );
		// If string is empty, return 0. If not, attempt to parse into a timestamp
		$client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;

		// Make a timestamp for our most recent modification...
		$modified_timestamp = strtotime( $last_modified );

		if ( ( $client_last_modified && $client_etag )
			? ( ( $client_modified_timestamp >= $modified_timestamp ) && ( $client_etag == $etag ) )
			: ( ( $client_modified_timestamp >= $modified_timestamp ) || ( $client_etag == $etag ) )
		) {
			status_header( 304 );
			exit;
		}

		// If we made it this far, just serve the file
		readfile( $file );
		die();
	}

} // end class

