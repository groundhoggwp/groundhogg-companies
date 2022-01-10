<?php

namespace GroundhoggCompanies;

use Groundhogg\Contact;
use Groundhogg\Plugin;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;

/**
 * Swap out the sanitization callback
 *
 * @param $callback callable
 * @param $option string
 * @param $value mixed
 */
function filter_option_sanitize_callback( $callback, $option, $value ){

	switch ( $option ){
		case 'gh_company_custom_properties':
			// todo implement proper sanitization here
			return function ( $props ) {
				return $props;
			};
	}

	return $callback;

}

add_filter( 'groundhogg/api/v4/options_sanitize_callback', __NAMESPACE__ . '\filter_option_sanitize_callback', 10, 3 );

/**
 * Recount the contacts per tag...
 */
function recount_company_contacts_count() {
	/* Recount tag relationships */
	$companies = get_db( 'companies' )->query();

	if ( ! empty( $companies ) ) {
		foreach ( $companies as $company ) {
			$count = get_db( 'company_relationships' )->count( [ 'company_id' => absint( $company->ID ) ] );
			get_db( 'companies' )->update( absint( $company->ID ), [ 'contact_count' => $count ] );
		}
	}
}

/**
 * Get the job title of a contact
 *
 * @param $contact_id
 *
 * @return bool|mixed
 */
function get_job_title( $contact_id ) {
	$contact = $contact_id;

	if ( ! is_a( $contact, '\Groundhogg\Contact' ) ) {
		$contact = get_contactdata( $contact_id );
	}

	if ( ! $contact || ! $contact->exists() ) {
		return false;
	}

	return $contact->get_meta( 'job_title' );

}

/**
 * Add the company selector in the contact record
 */
function company_section_in_contact() {
	?>
	<table class="form-table">
		<tbody>
		<tr>
			<th>
				<label for="companies"><?php echo _x( 'Companies', 'contact_record', 'groundhogg' ) ?></label>
			</th>
			<td>
				<?php
				$company_ids = wp_parse_id_list( wp_list_pluck( get_db( 'company_relationships' )->query( [
					'contact_id' => absint( get_request_var( 'contact' ) )
				] ), 'company_id' ) );

				$options   = [];
				$companies = get_db( 'companies' )->query();
				foreach ( $companies as $company ) {
					if ( in_array( $company->ID, $company_ids ) ) {
						$options[ $company->ID ] = $company->name . '(' . $company->contact_count . ')';
					}
				}

				echo dropdown_companies( [
					'selected' => $company_ids
				] );

				if ( ! empty( $company_ids ) ) :

					$company_ddl = array(
						'name'        => 'company_id',
						'id'          => 'company-id',
						'class'       => 'post-data',
						'options'     => $options,
						'selected'    => $options[0],
						'option_none' => false,
					);


					?>

					<p>
						<?php echo html()->dropdown( $company_ddl ) ?>
						<button type="submit" name="view_company" value="view_company" class="button">
                            <span
	                            title="<?php esc_attr_e( 'View Company', 'groundhogg' ) ?>"
	                            class="dashicons dashicons-building"
	                            style="width: auto;height: auto;vertical-align: middle;font-size: 14px;margin-right: 3px;"></span> <?php _e( 'View Company', 'groundhogg-companies' ); ?>
						</button>
					</p>

				<?php endif; ?>
			</td>
		</tr>
		</tbody>
	</table>
	<?php
}

/**
 * Code to redirect user to copany page based on the selection
 */
function process_view_company_action() {
	/* USE the same email priviledges */
	if ( isset( $_POST['company_id'] ) && isset( $_POST['view_company'] ) ) {
		wp_redirect( admin_url( sprintf( 'admin.php?page=gh_companies&action=edit&company=%s', $_POST['company_id'] ) ) );
	}
}

add_filter( 'groundhogg/admin/contact/save', __NAMESPACE__ . '\process_view_company_action', 10 );

/**
 * @param $contact_id int
 * @param $contact    Contact
 */
function manage_companies( $contact_id, $contact ) {
	$companies = get_request_var( 'companies', [] );

	$company_ids = wp_parse_id_list( wp_list_pluck( get_db( 'company_relationships' )->query( [
		'contact_id' => $contact_id
	] ), 'company_id' ) );

	$add = array_diff( $companies, $company_ids );
	if ( ! empty( $add ) ) {
		foreach ( $add as $company ) {
			$company = new Company( absint( $company ) );
			$company->add_contacts( $contact_id );
		}
	}

	$remove = array_diff( $company_ids, $companies );

	if ( ! empty( $remove ) ) {
		foreach ( $remove as $company ) {
			$company = new Company( absint( $company ) );
			$company->remove_contacts( $contact_id );
		}
	}

	if ( get_request_var( 'company_name' ) ) {
		$data    = get_db( 'companies' )->get_by( 'slug', sanitize_title( sanitize_title( get_request_var( 'company_name' ) ) ) );
		$company = new Company( $data->ID );
		$company->add_contacts( $contact_id );
	}

	recount_company_contacts_count();

}

add_action( 'groundhogg/admin/contact/save', __NAMESPACE__ . '\manage_companies', 10, 2 );


/**
 * Creates dropdown list for companies
 *
 * @param $args
 *
 * @return string
 */
function dropdown_companies( $args ) {
	$a = wp_parse_args( $args, array(
		'name'        => 'companies[]',
		'id'          => 'companies',
		'selected'    => 0,
		'class'       => 'gh_companies-picker gh-select2',
		'multiple'    => true,
		'placeholder' => __( 'Please Select a Company', 'groundhogg-calendar' ),
		'tags'        => false,
	) );

	$companies = get_db( 'companies' )->query();
	foreach ( $companies as $company ) {
		$a['data'][ $company->ID ] = $company->name . '(' . $company->contact_count . ')';
	}

	return html()->select2( $a );
}


/**
 * @param $contact_id
 * @param $company_name
 *
 * @return \WP_Error | bool
 */

function add_companies_based_on_contact( $contact_id, $company_name ) {

	if ( ! $company_name ) {
		return true;
	}

	if ( get_db( 'companies' )->exists( sanitize_title( $company_name ), 'slug' ) ) {

		$data    = get_db( 'companies' )->get_by( 'slug', sanitize_title( sanitize_title( $company_name ) ) );
		$company = new Company( $data->ID );

	} else {

		$company = new Company( [
			'name' => sanitize_text_field( $company_name ),
			'slug' => sanitize_title( $company_name ),

		] );

		if ( ! $company->get_id() ) {
			return new \WP_Error( 'unable_to_add_company', "Something went wrong adding the new company." );
		}
	}

	$company->add_contacts( absint( $contact_id ) );

	return true;
}

/**
 * Adds company in the contact list when contact email address domain is matched with company domain.
 * works on contact create method.
 *
 * @param $contact_id
 * @param $data
 */
function add_contact_based_on_domain( $contact_id, $data ) {

	$domain_name = get_clean_domain( substr( strrchr( $data['email'], "@" ), 1 ) );

	$companies = get_db( 'companies' )->query( [
		'domain' => $domain_name
	] );

	if ( ! empty ( $companies ) ) {
		foreach ( $companies as $com ) {
			$company = new Company( absint( $com->ID ) );
			$company->add_contacts( $contact_id );
		}
	}

}

add_action( 'groundhogg/db/post_insert/contact', __NAMESPACE__ . '\add_contact_based_on_domain', 10, 2 );

function add_companies_based_on_form_submission( $object_id, $meta_key, $meta_value, $prev_value ) {
	if ( $meta_key === 'company_name' ) {
		add_companies_based_on_contact( $object_id, $meta_value );
	}

}

add_action( 'groundhogg/meta/contact/update', __NAMESPACE__ . '\add_companies_based_on_form_submission', 10, 4 );


/**
 * Normalize domain to store in the database
 *
 * @param $domain
 *
 * @return string
 */

function get_clean_domain( $domain ) {
	$url       = $domain;
	$url       = strtolower( $url ); // convert to lower case
	$url       = str_replace( 'http://', '', $url ); //remove http
	$url       = str_replace( 'https://', '', $url ); // remove https
	$url       = 'http://' . $url;//add value for domain
	$sanitized = str_replace( 'www.', '', parse_url( $url, PHP_URL_HOST ) ); // get domain and replace www

	return sprintf( "https://%s", $sanitized );

}


/**
 * @return string Get the CSV import URL.
 */

function get_company_imports_dir( $file_path = '', $create_folders = false ) {
	return Plugin::$instance->utils->files->get_uploads_dir( 'company-imports', $file_path, $create_folders );
}

/**
 * @return string Get the CSV import URL.
 */

function get_company_imports_url( $file_path = '' ) {
	return Plugin::$instance->utils->files->get_uploads_url( 'company-imports', $file_path );
}


/**
 * Get a list of mappable fields as well as extra fields
 *
 * @param array $extra
 *
 * @return array
 */
function get_company_mappable_fields( $extra = [] ) {

	$defaults = [
		'company_name' => __( 'Company Name' ),
		'address'      => __( 'Company Address' ),
		'notes'        => __( 'Add To Notes' ),
		'description'  => __( 'Description' ),
		'website'      => __( 'Website' )
	];

	$fields = array_merge( $defaults, $extra );

	return apply_filters( 'groundhogg/companies/mappable_fields', $fields );

}

/**
 * Create or update company based on mapped data
 *
 * @param       $fields
 * @param array $map
 *
 * @return Company|\WP_Error
 */
function generate_company_with_map( $fields, $map = [] ) {

	if ( empty( $map ) ) {
		$keys = array_keys( $fields );
		$map  = array_combine( $keys, $keys );
	}

	$notes        = [];
	$company_name = '';
	$args         = [];
	$meta         = [];

	foreach ( $fields as $column => $value ) {


		// ignore if we are not mapping it.
		if ( ! key_exists( $column, $map ) ) {
			continue;
		}

		$value = wp_unslash( $value );
		$field = $map[ $column ];
		switch ( $field ) {
			case 'company_name' :
				var_dump( "incompant" );
				$company_name = sanitize_text_field( $value );
				break;
			case 'address' :
				$meta ['address'] = sanitize_text_field( $value );
				break;

			case 'notes' :
				$notes [] = sanitize_textarea_field( $value );
				break;
			case 'description'  :
				$args['description'] = sanitize_textarea_field( $value );
				break;
			case 'website'   :
				$args['domain'] = get_clean_domain( sanitize_text_field( $value ) );
				break;
		}

	}

	$company = false;

	if ( get_db( 'companies' )->exists( sanitize_title( $company_name ), 'slug' ) ) {
		$data    = get_db( 'companies' )->get_by( 'slug', sanitize_title( $company_name ) );
		$company = new Company( $data->ID );
	} else {

		$company = new Company( [
			'name' => $company_name,
			'slug' => sanitize_title( $company_name ),

		] );

		if ( ! $company->get_id() ) {
			return new \WP_Error( 'unable_to_add_company', "Something went wrong adding the new company." );
		}
	}

	if ( $args ) {
		$company->update( $args );
	}


	if ( $notes ) {
		foreach ( $notes as $note ) {
			$company->add_note( $note );
		}
	}

	if ( $meta ) {
		foreach ( $meta as $key => $value ) {
			$company->update_meta( $key, sanitize_textarea_field( $value ) );
		}
	}

	return $company;

}

/**
 * Migrate a companies notes
 *
 * @param $company Company
 */
function migrate_notes( $company ) {

	// Migrate notes
	$notes_migrated = $company->get_meta( 'notes_migrated' );
	$old_notes      = $company->get_meta( 'notes' );

	if ( ! $notes_migrated && $old_notes ) {
		$company->add_note( wpautop( $old_notes ) );
		$company->delete_meta( 'notes' );
	}

	$company->update_meta( 'notes_migrated', true );
}