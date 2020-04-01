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
 * Recount the contacts per tag...
 */
function recount_company_contacts_count()
{
    /* Recount tag relationships */
    $companies = get_db( 'companies' )->query();

    if ( !empty( $companies ) ) {
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
 * @return bool|mixed
 */
function get_job_title( $contact_id )
{
    $contact = $contact_id;

    if ( !is_a( $contact, '\Groundhogg\Contact' ) ) {
        $contact = get_contactdata( $contact_id );
    }

    if ( !$contact || !$contact->exists() ) {
        return false;
    }

    return $contact->get_meta( 'job_title' );

}

/**
 * Add the company selector in the contact record
 */
function company_section_in_contact()
{
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

                echo dropdown_companies( [
                    'selected' => $company_ids
                ] );

                if (!empty($company_ids)) :
                ?>
                <p>
                    <a href="<?php echo admin_url( sprintf( 'admin.php?page=gh_companies&action=edit&company=%s', $company_ids[0] ) ); ?> "
                       class="button"><span
                                title="<?php esc_attr_e( 'Copy share link', 'groundhogg' ) ?>"
                                class="dashicons dashicons-building" style="width: auto;height: auto;vertical-align: middle;font-size: 14px;margin-right: 3px;"></span> <?php _e( 'View Company', 'groundhogg-companies' ); ?></a>
                </p>
                <?php endif; ?>
            </td>
        </tr>
        </tbody>
    </table>
    <?php
}

add_action( 'groundhogg/contact/record/company_info/after', __NAMESPACE__ . '\company_section_in_contact' );

/**
 * @param $contact_id int
 * @param $contact Contact
 */
function manage_companies( $contact_id, $contact )
{
    $companies = get_request_var( 'companies', [] );

    $company_ids = wp_parse_id_list( wp_list_pluck( get_db( 'company_relationships' )->query( [
        'contact_id' => $contact_id
    ] ), 'company_id' ) );

    $add = array_diff( $companies, $company_ids );
    if ( !empty( $add ) ) {
        foreach ( $add as $company ) {
            $company = new Company( absint( $company ) );
            $company->add_contacts( $contact_id );
        }
    }

    $remove = array_diff( $company_ids, $companies );

    if ( !empty( $remove ) ) {
        foreach ( $remove as $company ) {
            $company = new Company( absint( $company ) );
            $company->remove_contacts( $contact_id );
        }
    }

    if(get_request_var('company_name')) {
        $data = get_db( 'companies' )->get_by( 'slug', sanitize_title( sanitize_title( get_request_var('company_name') ) ) );
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
 * @return string
 */
function dropdown_companies( $args ) {
    $a = wp_parse_args( $args, array(
        'name' => 'companies[]',
        'id' => 'companies',
        'selected' => 0,
        'class' => 'gh_companies-picker gh-select2',
        'multiple' => true,
        'placeholder' => __( 'Please Select a Company', 'groundhogg-calendar' ),
        'tags' => false,
    ) );

    $companies = get_db( 'companies' )->query();
    foreach ( $companies as $company ) {
        $a[ 'data' ][ $company->ID ] = $company->name . '(' . $company->contact_count . ')';
    }

    return html()->select2( $a );
}


/**
 * @param $contact_id
 * @param $company_name
 * @return \WP_Error | bool
 */

function add_companies_based_on_contact( $contact_id, $company_name ) {

    if ( get_db( 'companies' )->exists( sanitize_title( $company_name ), 'slug' ) ) {

        $data = get_db( 'companies' )->get_by( 'slug', sanitize_title( sanitize_title( $company_name ) ) );
        $company = new Company( $data->ID );

    } else {

        $company = new Company( [
            'name' => sanitize_text_field( $company_name ),
            'slug' => sanitize_title( $company_name ),

        ] );

        if ( !$company->get_id() ) {
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
function add_contact_based_on_domain( $contact_id, $data )
{

    $domain_name = get_clean_domain( substr( strrchr( $data[ 'email' ], "@" ), 1 ) );

    $companies = get_db( 'companies' )->query( [
        'domain' => $domain_name
    ] );

    if ( !empty ( $companies ) ) {
        foreach ( $companies as $com ) {
            $company = new Company( absint( $com->ID ) );
            $company->add_contacts( $contact_id );
        }
    }

}

add_action( 'groundhogg/db/post_insert/contact', __NAMESPACE__ . '\add_contact_based_on_domain', 10, 2 );

function add_companies_based_on_form_submission( $object_id, $meta_key, $meta_value, $prev_value )
{
	if ( $meta_key === 'company_name' ) {

		add_companies_based_on_contact( $object_id, $meta_value );

	}

}
add_action( 'groundhogg/meta/contact/update', __NAMESPACE__ . '\add_companies_based_on_form_submission', 10, 4 );


/**
 * Normalize domain to store in the database
 * @param $domain
 * @return string
 */

function get_clean_domain( $domain )
{
    $url = $domain;
    $url = strtolower( $url ); // convert to lower case
    $url = str_replace( 'http://', '', $url ); //remove http
    $url = str_replace( 'https://', '', $url ); // remove https
    $url = 'http://' . $url;//add value for domain
    $sanitized = str_replace( 'www.', '', parse_url( $url, PHP_URL_HOST ) ); // get domain and replace www

    return sprintf( "https://%s", $sanitized );

}



