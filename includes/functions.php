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


function company_section_in_contact()
{

    ?>
    <!--    <h2>--><?php //_ex('Location', 'contact_record', 'groundhogg');
    ?><!--</h2>-->
    <table class="form-table">
        <tbody>
        <tr>
            <th>
                <label for="companies"><?php echo _x( 'Companies Link', 'contact_record', 'groundhogg' ) ?></label>
            </th>
            <td>
                <?php
                $company_ids = wp_parse_id_list( wp_list_pluck( get_db( 'company_relationships' )->query( [
                    'contact_id' => absint( get_request_var( 'contact' ) )
                ] ), 'company_id' ) );

                echo dropdown_companies( [
                    'selected' => $company_ids
                ] );
                ?>
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
    $companies = get_request_var( 'companies' );
    if ( $companies ) {

        $company_ids = wp_parse_id_list( wp_list_pluck( get_db( 'company_relationships' )->query( [
            'contact_id' => $contact_id
        ] ), 'company_id' ) );

        $remove = array_diff( $company_ids, $companies );
        if ( !empty( $remove ) ) {
            foreach ( $remove as $company ) {
                $company = new Company( absint( $company ) );
                $company->remove_contacts( $contact_id );
            }
        }

        $add = array_diff( $companies, $company_ids );
        if ( !empty( $add ) ) {
            foreach ( $add as $company ) {
                $company = new Company( absint( $company ) );
                $company->add_contacts( $contact_id );
            }
        }
        recount_company_contacts_count();
    }

}


add_action( 'groundhogg/admin/contact/save', __NAMESPACE__ . '\manage_companies', 10, 2 );


/**
 * Creates dropdown list for companies
 *
 * @param $args
 * @return string
 */

function dropdown_companies( $args )
{
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
 * Adds company in the contact list when contact email address domain is matched with company domain.
 * works on contact create method.
 *
 * @param $contact_id
 * @param $data
 */
function add_contact_based_on_domain( $contact_id, $data )
{

    $domain_name = get_clean_domain( substr( strrchr(  $data['email'] , "@" ), 1 )  );

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