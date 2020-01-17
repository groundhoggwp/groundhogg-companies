<?php

namespace GroundhoggCompanies\DB;

use Groundhogg\DB\DB;
use function Groundhogg\isset_not_empty;

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Tags DB
 *
 * Store tags
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2020, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0
 */
class Companies extends DB
{
//
//    /**
//     * Runtime associative array of ID => tag_object
//     *
//     * @var array
//     */
//    public $tag_cache = [];

    /**
     * Get the DB suffix
     *
     * @return string
     */
    public function get_db_suffix()
    {
        return 'gh_companies';
    }

    /**
     * Get the DB primary key
     *
     * @return string
     */
    public function get_primary_key()
    {
        return 'ID';
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
        return 'companies';
    }

    protected function add_additional_actions()
    {
        add_action( 'groundhogg/db/post_insert/companies_relationship', [ $this, 'increase_contact_count' ], 10, 2 );
        add_action( 'groundhogg/db/post_delete/companies_relationship', [ $this, 'decrease_contact_count' ], 10 );
        add_action( 'groundhogg/db/pre_bulk_delete/companies_relationships', [ $this, 'bulk_decrease_count' ], 10 );
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
            'ID'            => '%d',
            'name'          => '%s',
            'description'   => '%s',
            'contact_count' => '%d',
            'domain'        => '%s',
            'date_created'  => '%s',
        );
    }

    /**
     * Get default column values
     *
     * @access  public
     * @since   1.0
     */
    public function get_column_defaults()
    {
        return array(
            'ID'            => 0,
            'name'          => '',
            'description'   => '',
            'contact_count' => 0,
            'domain'        => '',
            'date_created'  => current_time( 'mysql' ),
        );
    }

    /**
     * TODO
     * Given a list of tags, make sure that the tags exist, if they don't add/or remove them
     *
     * @param array $maybe_tags
     * @return array $tags
     */
    public function validate( $maybe_tags = array() )
    {

        $tags = array();

        if ( !is_array( $maybe_tags ) ) {
            $maybe_tags = array( $maybe_tags );
        }

        foreach ( $maybe_tags as $i => $tag_id_or_string ) {

            if ( is_numeric( $tag_id_or_string ) ) {

                $tag_id = intval( $tag_id_or_string );

                if ( $this->exists( $tag_id ) ) {
                    $tags[] = $tag_id;
                }

            } else if ( is_string( $tag_id_or_string ) ) {

                $slug = sanitize_title( $tag_id_or_string );

                if ( $this->exists( $slug, 'tag_slug' ) ) {
                    $tag = $this->get_tag_by( 'tag_slug', $slug );
                    $tags[] = $tag->tag_id;

                } else {
                    $tags[] = $this->add( array( 'tag_name' => sanitize_text_field( $tag_id_or_string ) ) );
                }
            }
        }

        return $tags;
    }


    /**
     * TODO
     * Get tags in an array format that is select friendly.
     *
     * @return array
     */
    public function get_companies_select()
    {
        global $wpdb;
        $results = $wpdb->get_results( "SELECT * FROM $this->table_name ORDER BY $this->primary_key DESC" );

        $companies = [];

        foreach ( $results as $company ) {
            $companies[ $company->ID ] = sprintf( "%s (%s)", $company->name, $company->contact_count );
        }

        return $companies;
    }

    /**
     * Increase the contact tag count
     *
     * @param $insert_id
     * @param $args
     */
    public function increase_contact_count( $insert_id = 0, $args = [] )
    {
        $tag_id = absint( $args[ 'tag_id' ] );

        if ( !$this->exists( $tag_id ) ) {
            return;
        }

        $tag = $this->get_tag( $tag_id );
        $tag->contact_count = intval( $tag->contact_count ) + 1;
        $this->update( $tag_id, array( 'contact_count' => $tag->contact_count ), $this->primary_key );
    }

    /**
     * TODO
     * Decrease the contact tag count
     *
     * @param $insert_id
     * @param $args
     */
    public function decrease_contact_count( $args = [] )
    {
        if ( !isset_not_empty( $args, 'tag_id' ) ) {
            return;
        }

        $tag_id = absint( $args[ 'tag_id' ] );

        if ( !$this->exists( $tag_id ) ) {
            return;
        }

        $tag = $this->get_tag( $tag_id );
        $tag->contact_count = intval( $tag->contact_count ) - 1;
        $this->update( $tag_id, array( 'contact_count' => $tag->contact_count ), $this->primary_key );
    }

    /**
     * TODO
     * Bulk decrease the contact count.
     *
     * @param $company_ids
     */
    public function bulk_decrease_count( $company_ids )
    {

        if ( empty( $company_ids ) ) {
            return;
        }

        foreach ( $company_ids as $id ) {
            $this->decrease_contact_count( [ 'ID' => $id ] );
        }
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
        ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,        
        name mediumtext NOT NULL,
        description text NOT NULL,
        contact_count bigint(20) unsigned NOT NULL,     
        domain VARCHAR(80) NOT NULL,
        date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,     
        PRIMARY KEY (ID)        
		) {$this->get_charset_collate()};";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }
}