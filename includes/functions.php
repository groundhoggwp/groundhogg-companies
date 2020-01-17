<?php
namespace GroundhoggCompanies;

use Groundhogg\Plugin;
use function Groundhogg\get_db;
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
