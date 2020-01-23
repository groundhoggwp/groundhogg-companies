<?php

namespace GroundhoggCompanies\DB;

use Groundhogg\DB\DB;

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * companies relationships DB
 *
 * Store the relationships between company and contacts
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2020, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Company_Relationships extends DB
{
    /**
     * The name of the cache group.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $cache_group = 'company_contact_relationships';

    /**
     * Get the DB suffix
     *
     * @return string
     */
    public function get_db_suffix()
    {
        return 'gh_company_relationships';
    }

    /**
     * Get the DB primary key
     *
     * @return string
     */
    public function get_primary_key()
    {
        return 'company_id';
    }

    /**
     * Get the DB version
     *
     * @return mixed
     */
    public function get_db_version()
    {
        return '2.0';
    }

    /**
     * Get the object type we're inserting/updateing/deleting.
     *
     * @return string
     */
    public function get_object_type()
    {
        return 'company_relationship';
    }

    /**
     * Clean up after company/contact is deleted.
     */
    protected function add_additional_actions()
    {
        add_action( 'groundhogg/db/post_delete/contact', [ $this, 'contact_deleted' ] );
        add_action( 'groundhogg/db/post_delete/company', [ $this, 'company_deleted' ] );
        parent::add_additional_actions();
    }

    /**
     * Get columns and formats
     *
     * @access  public
     * @since   2.1
     */
    public function get_columns()
    {
        return array(
            'company_id' => '%d',
            'contact_id' => '%d',
        );
    }

    /**
     * Get default column values
     *
     * @access  public
     * @since   2.1
     */
    public function get_column_defaults()
    {
        return array(
            'company_id' => 0,
            'contact_id' => 0,
        );
    }

    /**
     * Add a company relationship
     *
     * @access  public
     * @since   2.1
     */
    public function add( $company_id = 0, $contact_id = 0 )
    {

        if ( !$company_id || !$contact_id ) {
            return false;
        }

        $data = array(
            'company_id' => absint( $company_id ),
            'contact_id' => absint( $contact_id )
        );

        $result = $this->insert( $data );

        return $result;
    }

    /**
     * A company was delete, delete all company relationships
     *
     * @param $company_id
     *
     * @return bool
     */
    public function company_deleted( $company_id )
    {
        return $this->delete( [ 'company_id' => $company_id ] );
    }

    /**
     * A contact was deleted, delete all company relationships
     *
     * @param $contact_id
     *
     * @return bool
     */
    public function contact_deleted( $contact_id )
    {

        $companies = $this->get_companies_by_contact( $contact_id );

        if ( empty( $companies ) ) {
            return false;
        }

        do_action( 'groundhogg/db/pre_bulk_delete/companies_relationships', wp_parse_id_list( $companies ) );

        return $this->delete( [ 'contact_id' => $contact_id ] );

    }

    /**
     * Delete a companies relationship
     *
     * @access  public
     * @since   1.0
     */
    public function delete( $args = array() )
    {

        global $wpdb;

        // Initialise column format array
        $column_formats = $this->get_columns();

        // Force fields to lower case
        $data = array_change_key_case( $args );

        // White list columns
        $data = array_intersect_key( $data, $column_formats );

        // Reorder $column_formats to match the order of columns given in $data
        $data_keys = array_keys( $data );
        $column_formats = array_merge( array_flip( $data_keys ), $column_formats );

        if ( false === $wpdb->delete( $this->table_name, $data, $column_formats ) ) {
            return false;
        }

        do_action( 'groundhogg/db/post_delete/compnanies_relationship', $args );

        return true;

    }

    /**
     * Retrieve companies from the database
     *
     * @access  public
     * @since   1.0
     */
    public function get_relationships( $value, $column = 'contact_id', $return = "company_id" )
    {

        if ( empty( $value ) || !is_numeric( $value ) )
            return false;

        global $wpdb;

        $results = $wpdb->get_col( "SELECT $return FROM $this->table_name WHERE $column = $value ORDER BY $this->primary_key DESC" );

        if ( empty( $results ) ) {
            return false;
        }

        return $results;
    }

    /**
     * Get a list of company associated with a particular contact
     *
     * @param int $contact_id
     * @return array|bool|null|object
     */
    public function get_companies_by_contact( $contact_id = 0 )
    {
        return $this->get_relationships( $contact_id, 'contact_id', 'company_id' );
    }


    /**
     * Get a list of contacts associated with a particular company
     *
     * @param int $company_id
     * @return array|bool|null|object
     */
    public function get_contacts_by_companies( $company_id = 0 )
    {
        return $this->get_relationships( $company_id, 'company_id', 'contact_id' );

    }

    /**
     * Create the table
     *
     * @access  public
     * @since   2.1
     */
    public function create_table()
    {

        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE " . $this->table_name . " (
		company_id bigint(20) unsigned NOT NULL,
		contact_id bigint(20) unsigned NOT NULL,
		PRIMARY KEY (company_id,contact_id),
		KEY company_id (company_id),
		KEY contact_id (contact_id)
		) {$this->get_charset_collate()};";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }
}