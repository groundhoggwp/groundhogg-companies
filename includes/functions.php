<?php

namespace GroundhoggCompanies;

use Groundhogg\Contact;
use Groundhogg\Plugin;
use Groundhogg\Properties;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\array_map_with_keys;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\get_email_address_hostname;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\is_a_contact;

add_filter( 'groundhogg/api/v4/options_sanitize_callback', __NAMESPACE__ . '\filter_option_sanitize_callback', 10, 3 );

/**
 * Swap out the sanitization callback
 *
 * @param $callback callable
 * @param $option   string
 * @param $value    mixed
 */
function filter_option_sanitize_callback( $callback, $option, $value ) {

	switch ( $option ) {
		case 'gh_company_custom_properties':
			// todo implement proper sanitization here
			return function ( $props ) {
				return $props;
			};
	}

	return $callback;

}

/**
 * Normalize domain to store in the database
 *
 * @param $domain string
 *
 * @return string|false
 */
function sanitize_domain_name( $domain ) {
	// convert to lower case
	$domain = strtolower( $domain );
	// Replace WWW in HOST
	$domain = str_replace( 'www.', '', parse_url( $domain, PHP_URL_HOST ) );
	$domain = sanitize_text_field( $domain );

	if ( empty( $domain ) ) {
		return false;
	}

	return sprintf( "https://%s", $domain );
}

/**
 * @return string Get the CSV import URL.
 */
function get_company_exports_dir( $file_path = '', $create_folders = false ) {
	return Plugin::$instance->utils->files->get_uploads_dir( 'company-imports', $file_path, $create_folders );
}

/**
 * @return string Get the CSV import URL.
 */
function get_company_exports_url( $file_path = '' ) {
	return Plugin::$instance->utils->files->get_uploads_url( 'company-imports', $file_path );
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
 * Company meta fields for the contact record
 *
 * @return array
 */
function get_contact_company_fields() {
	return [
		'company_name'            => __( 'Company Name', 'groundhogg' ),
		'company_website'         => __( 'Company Website', 'groundhogg' ),
		'company_address'         => __( 'Company Address', 'groundhogg' ),
		'company_phone'           => __( 'Company Phone Number', 'groundhogg' ),
		'company_phone_extension' => __( 'Company Phone Number Extension', 'groundhogg' ),
		'job_title'               => __( 'Job Title', 'groundhogg' ),
		'company_department'      => __( 'Department', 'groundhogg' ),
	];
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
		'name'        => __( 'Name' ),
		'industry'    => __( 'Industry' ),
		'phone'       => __( 'Phone' ),
		'address'     => __( 'Address' ),
		'description' => __( 'Description' ),
		'domain'      => __( 'Website' ),
		'notes'       => __( 'Add To Notes' ),
		'contacts'    => __( 'Add To Contacts' ),
	];

	$fields = array_merge( $defaults, $extra );

	return apply_filters( 'groundhogg/companies/mappable_fields', $fields );
}

add_filter( 'groundhogg/mappable_fields', __NAMESPACE__ . '\register_mappable_fields' );

/**
 * Add company fields to mappable fields
 *
 * @param $fields
 *
 * @return mixed
 */
function register_mappable_fields( $fields ) {

	$fields[ __( 'Company' ) ] = get_contact_company_fields();

	return $fields;
}

add_action( 'groundhogg/admin/tools/export', __NAMESPACE__ . '\show_export_headers' );

/**
 * Display the Company Fields in the export form
 */
function show_export_headers() {

	$fields = get_contact_company_fields();

	?><h3><?php _e( 'Company', 'groundhogg' ) ?></h3>
	<?php

	html()->list_table( [
		'class' => 'export-table'
	], [
		[
			'class' => 'check-column',
			'name'  => "<input type='checkbox' value='1' checked class='select-all'>",
			'tag'   => 'td'
		],
		__( 'Pretty Name', 'groundhogg' ),
		__( 'Field ID', 'groundhogg' ),
	], map_deep( array_keys( $fields ), function ( $header ) use ( $fields ) {
		return [
			html()->checkbox( [
				'label'   => '',
				'type'    => 'checkbox',
				'name'    => 'headers[' . $header . ']',
				'id'      => 'header_' . $header,
				'class'   => 'basic header',
				'value'   => '1',
				'checked' => true,
			] ),
			$fields[ $header ],
			'<code>' . esc_html( $header ) . '</code>'
		];
	} ) );

}

add_filter( 'groundhogg/export_header_name', __NAMESPACE__ . '\export_header_name', 10, 3 );

/**
 * Export the header name for company fields
 *
 * @param $header
 * @param $key
 * @param $type
 *
 * @return mixed
 */
function export_header_name( $header, $key, $type ) {

	if ( $type === 'pretty' ) {

		$fields = get_contact_company_fields();

		if ( in_array( $key, $fields ) ) {
			$header = $fields['key'];
		}
	}

	return $header;
}

add_action( 'groundhogg/update_contact_with_map/default', __NAMESPACE__ . '\handle_map_company_details', 10, 4 );
add_action( 'groundhogg/generate_contact_with_map/default', __NAMESPACE__ . '\handle_map_company_details', 10, 4 );

/**
 * During field mapping, maybe create a company record and link it to the contact
 *
 * @param $field string the current field
 * @param $value mixed
 * @param $args  array contact details
 * @param $meta  array meta stuff
 */
function handle_map_company_details( $field, $value, &$args, &$meta ) {

	switch ( $field ) {

		case 'company_name':
		case 'company_phone':
		case 'company_website':
		case 'company_phone_extension':
		case 'job_title':
		case 'company_department':
			$meta[ $field ] = sanitize_text_field( $value );
			break;
		case 'company_address':
			$meta[ $field ] = sanitize_textarea_field( $value );
			break;
	}

}

add_action( 'groundhogg/contact/post_create', __NAMESPACE__ . '\contact_created', 10, 3 );

/**
 * At this point the contact will not have any meta or company information because it runs
 * before all that.
 *
 * So the only piece of info we have is the email address
 *
 * @param $ID      int
 * @param $data    array
 * @param $contact Contact
 */
function contact_created( $ID, $data, $contact ) {

	// Ignore free email providers
	if ( is_free_email_provider( $contact->get_email() ) ) {
		return;
	}

	$company_website = 'https://' . get_email_address_hostname( $contact->get_email() );

	$company = new Company( $company_website, 'domain' );

	if ( $company->exists() ) {
		$company->create_relationship( $contact );
	}
}

add_action( 'groundhogg/contact/post_update', __NAMESPACE__ . '\contact_updated', 10, 4 );

/**
 * When a contact is updated check the email domain and maybe associate to a company
 * But only if the email was changed!
 *
 * @param $ID       int
 * @param $data     array
 * @param $contact  Contact
 * @param $old_data array
 */
function contact_updated( $ID, $data, $contact, $old_data ) {

	if ( ! key_exists( 'email', $data ) ) {
		return;
	}

	contact_created( $ID, $data, $contact );
}

add_action( 'groundhogg/update_contact_with_map/after', __NAMESPACE__ . '\were_company_changes_made_during_mapping', 10, 3 );
add_action( 'groundhogg/generate_contact_with_map/after', __NAMESPACE__ . '\were_company_changes_made_during_mapping', 10, 3 );

/**
 * Detect if changes where made during field mapping
 *
 * @param $contact
 * @param $map
 * @param $fields
 */
function were_company_changes_made_during_mapping( $contact, $map, $fields ) {

	$has_changes = array_intersect( get_contact_company_fields(), array_values( $map ) );

	if ( ! $has_changes ) {
		return;
	}

	maybe_create_company_from_contact( $contact );
}

add_action( 'groundhogg/after_form_submit', __NAMESPACE__ . '\maybe_create_company_from_contact', 10, 1 );
add_action( 'groundhogg/companies/save_work_details', __NAMESPACE__ . '\maybe_create_company_from_contact', 10, 1 );

/**
 * If Company details are present, create a new company
 *
 * @param $contact Contact
 */
function maybe_create_company_from_contact( $contact ) {

	if ( ! is_a_contact( $contact ) ) {
		return;
	}

	$company_name    = $contact->get_meta( 'company_name' );
	$company_website = $contact->get_meta( 'company_website' );
	$company_phone   = $contact->get_meta( 'company_phone' );

	$_slug = sanitize_title( $company_name );

	if ( ! $company_website && ! is_free_email_provider( $contact->get_email() ) ) {
		$company_website = 'https://' . get_email_address_hostname( $contact->get_email() );
	}

	// No slug here
	if ( $_slug ) {
		// By Company Name

		$company = new Company( $_slug, 'slug' );
	} else if ( $company_website ) {
		// By Email address or Website

		$company = new Company( $company_website, 'domain' );
	} else if ( $company_phone ) {
		// By phone number

		$_phone        = preg_replace( '/[^0-9]/', '', $company_phone );
		$_company_meta = get_db( 'company_meta' )->query( [
			'meta_key'   => '_phone',
			'meta_value' => $_phone
		] );

		if ( ! $_company_meta ) {
			// No companies found, not enough data to create a new one
			return;
		}

		$company = new Company( $_company_meta[0]->company_id );
	} else {

		// We can't continue of the company does not exist and there is no slug...
		return;
	}

	// The exact slug doesn't exist
	if ( ! $company->exists() ) {

		if ( ! $_slug ) {
			return;
		}

		$related_companies = $contact->get_related_objects( 'company', false );

		// Contact is related to other companies
		if ( ! empty( $related_companies ) ) {

			// Loop through other companies to see if we should update them
			foreach ( $related_companies as $company ) {

				similar_text( $company->slug, $_slug, $percent );

				// If a small modification was made to the contact record
				if ( $percent < 75 ) {
					continue;
				}

				$company->update_from_contact( $contact );

				return;
			}
		}

		// no previous companies or like companies, so link to a new one
		$company->create( [
			'name' => $company_name,
			'slug' => $_slug,
		] );

		$company->update_from_contact( $contact );
	}

	// Relate to the company
	if ( ! $company->is_related( $contact ) ) {
		$company->create_relationship( $contact );
	}

	$company->update_from_contact( $contact );

}

/**
 * The company properties instance
 *
 * @return Properties
 */
function properties() {
	static $props;

	if ( ! $props ) {
		$props = new Properties( 'gh_company_custom_properties' );
	}

	return $props;
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

	$notes    = [];
	$args     = [];
	$meta     = [];
	$contacts = [];

	foreach ( $fields as $column => $value ) {

		// ignore if we are not mapping it.
		if ( ! key_exists( $column, $map ) ) {
			continue;
		}

		$value = wp_unslash( $value );
		$field = $map[ $column ];
		switch ( $field ) {
			case 'name':
				$args[ $field ] = sanitize_text_field( $value );
				break;
			case 'domain'   :
				$args[ $field ] = sanitize_domain_name( $value );
				break;
			case 'address':
				// build address from multiple fields
				if ( key_exists( $field, $meta ) ) {
					$meta[ $field ] .= ', ' . sanitize_textarea_field( $value );
				} else {
					$meta[ $field ] = sanitize_textarea_field( $value );
				}
				break;
			case 'phone':
			case 'industry':
				$meta[ $field ] = sanitize_text_field( $value );
				break;
			case 'notes':
				$notes[] = sanitize_textarea_field( $value );
				break;
			case 'contacts':
				$contacts = array_merge( $contacts, wp_parse_list( $value ) );
				break;
			default:

				// check if custom field for company exists
				$_field = properties()->get_field( $field );

				if ( $_field ) {
					$meta[ $_field ] = $value;
				}

				break;

		}

	}

	// Can't import without name
	if ( ! key_exists( 'name', $args ) ) {
		return false;
	}

	$_slug = sanitize_title( $args['name'] );

	$company = new Company( $_slug, 'slug' );

	if ( ! $company->exists() ) {
		$company->create( $args );
	} else {
		$company->update( $args );
	}

	if ( $notes ) {
		foreach ( $notes as $note ) {
			$company->add_note( $note );
		}
	}

	// Add relationships from contact records
	if ( $contacts ) {
		$contacts = array_unique( $contacts );
		foreach ( $contacts as $_id_or_email ) {

			$contact = get_contactdata( $_id_or_email );

			if ( $contact ) {
				$company->create_relationship( $contact );
				continue;
			}

			if ( is_email( $_id_or_email ) ) {
				$contact = new Contact( [ 'email' => $_id_or_email ] );
				$company->create_relationship( $contact );
			}
		}
	}

	if ( $meta ) {
		$company->update_meta( $meta );
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

add_action( 'groundhogg/contact/record/company_info/after', __NAMESPACE__ . '\company_info_fields' );

/**
 * Show the clompany fields in the contact record
 *
 * @param $contact Contact
 */
function company_info_fields( $contact ) {

	wp_enqueue_script( 'groundhogg-companies-data-admin' );

	?>
    <h2><?php _e( 'Work Details', 'groundhogg' ) ?></h2>
    <div class="gh-rows-and-columns">
        <div class="gh-row">
            <div class="gh-col">
                <label for="job_title"><?php _e( 'Company Name', 'groundhogg' ) ?></label>
				<?php echo html()->input( [
					'class' => 'input',
					'id'    => 'company_name',
					'name'  => 'company_name',
					'value' => $contact->get_meta( 'company_name' ),
				] ); ?>
            </div>

            <div class="gh-col">
                <label
                        for="company_phone"><?php _e( 'Work Phone & Ext.', 'groundhogg' ) ?></label>
                <div class="gh-input-group">
					<?php echo html()->input( [
						'type'        => 'tel',
						'class'       => 'input',
						'id'          => 'company_phone',
						'name'        => 'company_phone',
						'value'       => $contact->get_meta( 'company_phone' ),
						'placeholder' => __( '+1 (555) 555-5555', 'groundhogg' )
					] ); ?>
					<?php echo html()->input( [
						'type'        => 'number',
						'class'       => 'input',
						'id'          => 'company_phone_extension',
						'name'        => 'company_phone_extension',
						'value'       => $contact->get_meta( 'company_phone_extension' ),
						'style'       => [
							'width' => '60px'
						],
						'placeholder' => __( '1234', 'groundhogg' )
					] ); ?>
                </div>
            </div>
        </div>
        <div class="gh-row">
            <div class="gh-col">
                <label
                        for="job_title"><?php _e( 'Position', 'groundhogg' ) ?></label>
				<?php echo html()->input( [
					'class' => 'input',
					'id'    => 'job_title',
					'name'  => 'job_title',
					'value' => $contact->get_job_title(),
				] ); ?>
            </div>
            <div class="gh-col">
                <label
                        for="company_department"><?php _e( 'Department', 'groundhogg' ) ?></label>
				<?php echo html()->input( [
					'class' => 'input',
					'id'    => 'company_department',
					'name'  => 'company_department',
					'value' => $contact->get_meta( 'company_department' ),
				] ); ?>
            </div>
        </div>
        <div class="gh-row">
            <div class="gh-col">
                <label
                        for="company_website"><?php _e( 'Website', 'groundhogg' ) ?></label>
				<?php echo html()->input( [
					'type'  => 'url',
					'class' => 'full-width',
					'id'    => 'company_website',
					'name'  => 'company_website',
					'value' => $contact->get_meta( 'company_website' ),
				] ); ?>
            </div>
        </div>
        <div class="gh-row">
            <div class="gh-col">
                <label
                        for="company_address"><?php _e( 'Address', 'groundhogg' ) ?></label>
				<?php echo html()->textarea( [
					'class' => 'full-width',
					'id'    => 'company_address',
					'name'  => 'company_address',
					'value' => $contact->get_meta( 'company_address' ),
					'rows'  => 2,
				] ); ?>
            </div>
        </div>
    </div>
    <script>
      ( ($) => {
        $(() => {

          console.log('here')

          $('#company_department').autocomplete({
            source: Groundhogg.companyDepartments,
          })

          $('#job_title').autocomplete({
            source: Groundhogg.companyPositions,
          })

        })
      } )(jQuery)
    </script>
	<?php

}

add_action( 'groundhogg/admin/contact/save', __NAMESPACE__ . '\save_work_details', 9, 2 );

/**
 * Save the work details
 *
 * @param $contact_id
 * @param $contact Contact
 */
function save_work_details( $contact_id, $contact ) {

	$fields = [
		'company_name',
		'company_phone',
		'company_phone_extension',
		'company_department',
		'company_website',
		'company_address',
		'job_title'
	];

	$orig = array_map_with_keys( $fields, [ $contact, 'get_meta' ] );
	$new  = array_map_with_keys( $fields, 'Groundhogg\get_post_var' );

	// there was no change in values
	if ( $orig == $new ) {
		return;
	}

	foreach ( $fields as $field ) {
		$value = sanitize_text_field( get_post_var( $field ) );

		if ( $value ) {
			$contact->update_meta( $field, sanitize_text_field( get_post_var( $field ) ) );
		} else {
			$contact->delete_meta( $field );
		}

	}

	$contact->update_meta( 'company_address', sanitize_textarea_field( get_post_var( 'company_address' ) ) );

	/**
	 * When the work details are saved
	 *
	 * @param $contact Contact
	 */
	do_action( 'groundhogg/companies/save_work_details', $contact );

}

add_filter( 'groundhogg/admin/contacts/exclude_meta_list', __NAMESPACE__ . '\exclude_company_fields_from_meta' );

/**
 * Don't show the company meta keys in the general meta list
 *
 * @param $keys
 *
 * @return array
 */
function exclude_company_fields_from_meta( $keys ) {
	return array_merge( $keys, array_keys( get_contact_company_fields() ) );
}

/**
 * @todo remove this in future versions once the core version is fixed
 *
 * If the given email is from a free inbox provider
 *
 * @param $email string
 * @deprecated 3.0.1
 */
function is_free_email_provider( $email ) {

	if ( ! is_email( $email ) ) {
		return false;
	}

	static $providers = [];

	// initialize providers
	if ( empty( $providers ) ) {
		$providers = json_decode( file_get_contents( GROUNDHOGG_ASSETS_PATH . 'lib/free-email-providers.json' ), true );
	}

	return apply_filters( 'groundhogg/is_free_email_provider', in_array( get_email_address_hostname( $email ), $providers ) );
}
