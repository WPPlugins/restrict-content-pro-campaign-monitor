<?php
/**
 * Plugin Name: Restrict Content Pro - Campaign Monitor
 * Plugin URL: https://restrictcontentpro.com/downloads/campaign-monitor/
 * Description: Include a Campaign Monitor signup option with your Restrict Content Pro registration form
 * Version: 1.0.1
 * Author: Pippin Williamson
 * Author URI: http://pippinsplugins.com
 * Contributors: mordauk
 */

$rcp_cm_options = get_option( 'rcp_cm_settings' );

/**
 * Add settings page
 *
 * @since 1.0
 * @return void
 */
function rcp_cm_settings_menu() {
	add_submenu_page( 'rcp-members', __( 'Restrict Content Pro Campaign Monitor Settings', 'rcp' ), __( 'Campaign Monitor', 'rcp' ), 'manage_options', 'rcp-campaign-monitor', 'rcp_cm_settings_page' );
}

add_action( 'admin_menu', 'rcp_cm_settings_menu', 100 );

/**
 * Register the plugin settings
 *
 * @since 1.0
 * @return void
 */
function rcp_cm_register_settings() {

	// register the settings section

	add_settings_section(
		'rcp_cm_settings',
		__( 'Campaign Monitor Settings', 'rcp' ),
		'rcp_cm_description_callback',
		'rcp_cm_settings'
	);

	// register the settings fields

	add_settings_field(
		'rcp_cm_settings[cm_api]',
		__( 'API Key', 'rcp' ),
		'rcp_cm_text_callback',
		'rcp_cm_settings',
		'rcp_cm_settings',
		array(
			'id'      => 'cm_api',
			'desc'    => __( 'Enter your Campaign Monitor API key to enable a newsletter signup option with the registration form.', 'rcp' ),
			'name'    => __( 'API Key', 'rcp' ),
			'options' => null,
		)
	);
	add_settings_field(
		'rcp_cm_settings[cm_client]',
		__( 'Client ID', 'rcp' ),
		'rcp_cm_text_callback',
		'rcp_cm_settings',
		'rcp_cm_settings',
		array(
			'id'      => 'cm_client',
			'desc'    => __( 'Enter the ID of the client to use. The ID can be found in the Client Settings page of the client.', 'rcp' ),
			'name'    => __( 'Client ID', 'rcp' ),
			'options' => null,
		)
	);
	add_settings_field(
		'rcp_cm_settings[cm_list]',
		__( 'List', 'rcp' ),
		'rcp_cm_select_callback',
		'rcp_cm_settings',
		'rcp_cm_settings',
		array(
			'id'      => 'cm_list',
			'desc'    => __( 'Choose the list to subscribe users to.', 'rcp' ),
			'name'    => __( 'List', 'rcp' ),
			'options' => rcp_cm_get_lists(),
		)
	);
	add_settings_field(
		'rcp_cm_settings[cm_label]',
		__( 'Form Label', 'rcp' ),
		'rcp_cm_text_callback',
		'rcp_cm_settings',
		'rcp_cm_settings',
		array(
			'id'      => 'cm_label',
			'desc'    => __( 'Enter the label to be shown on the "Signup for Newsletter" checkbox', 'rcp' ),
			'name'    => __( 'Form Label', 'rcp' ),
			'options' => null,
		)
	);


	// create whitelist of options
	register_setting( 'rcp_cm_settings', 'rcp_cm_settings' );
}

add_action( 'admin_init', 'rcp_cm_register_settings', 100 );

/**
 * Description callback
 *
 * @since 1.0
 * @return void
 */
function rcp_cm_description_callback() {
	echo __( 'Configure the settings below', 'rcp' );
}

/**
 * Text field callback
 *
 * @param array $args
 *
 * @since 1.0
 * @return void
 */
function rcp_cm_text_callback( $args ) {

	global $rcp_cm_options;

	$value = isset( $rcp_cm_options[ $args['id'] ] ) ? $rcp_cm_options[ $args['id'] ] : '';
	$html  = '<input type="text" class="regular-text" id="rcp_cm_settings[' . esc_attr( $args['id'] ) . ']" name="rcp_cm_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '"/>';
	$html  .= '<div class="description"><label for="rcp_cm_settings[' . esc_attr( $args['id'] ) . ']"> ' . $args['desc'] . '</label></div>';

	echo $html;

}

/**
 * Select field callback
 *
 * @param array $args
 *
 * @since 1.0
 * @return void
 */
function rcp_cm_select_callback( $args ) {

	global $rcp_cm_options;

	$value = isset( $rcp_cm_options[ $args['id'] ] ) ? $rcp_cm_options[ $args['id'] ] : '';
	$html  = '<select id="rcp_cm_settings[' . esc_attr( $args['id'] ) . ']" name="rcp_cm_settings[' . esc_attr( $args['id'] ) . ']"/>';
	foreach ( $args['options'] as $option => $name ) {
		$html .= '<option value="' . esc_attr( $option ) . '" ' . selected( $option, $value, false ) . '>' . esc_html( $name ) . '</option>';
	}
	$html .= '</select>';
	$html .= '<div class="description"><label for="rcp_cm_settings[' . esc_attr( $args['id'] ) . ']"> ' . $args['desc'] . '</label></div>';

	echo $html;

}

/**
 * Render settings page
 *
 * @since 1.0
 * @return void
 */
function rcp_cm_settings_page() {

	global $rcp_cm_options;

	?>
	<div class="wrap">

		<?php settings_errors( 'rcp_cm_settings' ); ?>

		<form method="post" action="options.php" class="rcp_options_form">

			<?php
			settings_fields( 'rcp_cm_settings' );
			do_settings_sections( 'rcp_cm_settings' );
			?>
			<?php submit_button( __( 'Save Options', 'rcp' ) ); ?>

		</form>
	</div><!--end .wrap-->
	<?php
}

/**
 * Get an array of all Campaign Monitor subscription lists
 *
 * @since 1.0
 * @return array
 */
function rcp_cm_get_lists() {

	global $rcp_cm_options;

	if ( strlen( trim( $rcp_cm_options['cm_api'] ) ) > 0 && strlen( trim( $rcp_cm_options['cm_client'] ) ) > 0 ) {

		$lists = array();

		if ( ! class_exists( 'CS_REST_Clients' ) ) {
			require_once( dirname( __FILE__ ) . '/vendor/csrest_clients.php' );
		}

		$wrap = new CS_REST_Clients( $rcp_cm_options['cm_client'], $rcp_cm_options['cm_api'] );

		$result = $wrap->get_lists();

		if ( $result->was_successful() ) {
			if ( empty( $result->response ) ) {
				// No lists in Campaign Monitor.
				return array( __( 'No Campaign Monitor lists found', 'rcp' ) );
			} else {
				foreach ( $result->response as $list ) {
					$lists[ $list->ListID ] = $list->Name;
				}
			}

			return $lists;
		}
	}

	return array( __( 'Enter your API key and Client ID above', 'rcp' ) );
}

/**
 * Adds an email to the Campaign Monitor subscription list
 *
 * @param string $email Email address to add.
 * @param string $name  Name of the subscriber.
 *
 * @since 1.0
 * @return bool Whether or not it was added successfully.
 */
function rcp_cm_subscribe_email( $email, $name ) {
	global $rcp_cm_options;

	if ( strlen( trim( $rcp_cm_options['cm_api'] ) ) > 0 ) {

		if ( ! class_exists( 'CS_REST_Subscribers' ) ) {
			require_once( dirname( __FILE__ ) . '/vendor/csrest_subscribers.php' );
		}

		$wrap = new CS_REST_Subscribers( $rcp_cm_options['cm_list'], $rcp_cm_options['cm_api'] );

		$subscribe = $wrap->add( array(
			'EmailAddress' => $email,
			'Name'         => $name,
			'Resubscribe'  => true
		) );

		if ( $subscribe->was_successful() ) {
			return true;
		}
	}

	return false;
}

/**
 * Displays the Campaign Monitor checkbox on the registration form
 *
 * @since 1.0
 * @return void
 */
function rcp_cm_fields() {
	global $rcp_cm_options;
	ob_start();
	if ( isset( $rcp_cm_options['cm_api'] ) && strlen( trim( $rcp_cm_options['cm_api'] ) ) > 0 ) { ?>
		<p>
			<input name="rcp_cm_signup" id="rcp_cm_signup" type="checkbox" checked="checked"/>
			<label for="rcp_cm_signup"><?php echo $rcp_cm_options['cm_label']; ?></label>
		</p>
		<?php
	}
	echo ob_get_clean();
}

add_action( 'rcp_before_registration_submit_field', 'rcp_cm_fields', 100 );

/**
 * Checks whether a user should be signed up for the Campaign Monitor list
 *
 * @param array $posted  Posted data.
 * @param int   $user_id ID of the user registering.
 *
 * @since 1.0
 * @return void
 */
function rcp_cm_check_for_email_signup( $posted, $user_id ) {
	if ( $posted['rcp_cm_signup'] ) {
		// Set a flag so we know to add them to the list later.
		update_user_meta( $user_id, 'rcp_pending_cm_signup', true );
	} else {
		delete_user_meta( $user_id, 'rcp_pending_cm_signup' );
	}
}

add_action( 'rcp_form_processing', 'rcp_cm_check_for_email_signup', 10, 2 );

/**
 * Add user to the Campaign Monitor list when their account is activated
 *
 * @param string     $status     New status being set.
 * @param int        $user_id    ID of the user.
 * @param string     $old_status Previous status.
 * @param RCP_Member $member     Member object.
 *
 * @since 1.0.1
 * @return void
 */
function rcp_cm_add_to_list( $status, $user_id, $old_status, $member ) {

	if ( ! in_array( $status, array( 'active', 'free' ) ) ) {
		return;
	}

	if ( ! get_user_meta( $user_id, 'rcp_pending_cm_signup', true ) ) {
		return;
	}

	rcp_cm_subscribe_email( $member->user_email, $member->display_name );
	update_user_meta( $user_id, 'rcp_subscribed_to_cm', 'yes' );
	delete_user_meta( $user_id, 'rcp_pending_cm_signup' );

}

add_action( 'rcp_set_status', 'rcp_cm_add_to_list', 10, 4 );

/**
 * Add new column header to the "Members" table.
 *
 * @since 1.0
 * @return void
 */
function rcp_add_cm_table_column_header_and_footer() {
	echo '<th style="width: 140px;">' . __( 'Newsletter Signup', 'rcp' ) . '</th>';
}

add_action( 'rcp_members_page_table_header', 'rcp_add_cm_table_column_header_and_footer' );
add_action( 'rcp_members_page_table_footer', 'rcp_add_cm_table_column_header_and_footer' );

/**
 * Display table content saying whether or not the user signed up for the mailing list.
 *
 * @param int $user_id ID of the current member.
 *
 * @since 1.0
 * @return void
 */
function rcp_add_cm_table_column_content( $user_id ) {

	if ( get_user_meta( $user_id, 'rcp_subscribed_to_cm', true ) ) {
		$signed_up = __( 'yes', 'rcp' );
	} else {
		$signed_up = __( 'no', 'rcp' );
	}

	echo '<td>' . $signed_up . '</td>';
}

add_action( 'rcp_members_page_table_column', 'rcp_add_cm_table_column_content' );
