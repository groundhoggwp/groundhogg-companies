<?php

namespace GroundhoggCompanies\DB;

use Groundhogg\DB\DB;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Companies DB
 *
 * Store Companies
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2020, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0
 */
class Companies extends DB {
	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_companies';
	}

	/**
	 * Get the DB primary key
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return 'ID';
	}

	/**
	 * Get the DB version
	 *
	 * @return mixed
	 */
	public function get_db_version() {
		return '2.0';
	}


	/**
	 * Get the object type we're inserting/updateing/deleting.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return 'company';
	}

	protected function add_additional_actions() {

		//done using recount method where operation are supported
		add_action( 'groundhogg/db/post_insert/company_relationship', [ $this, 'increase_contact_count' ], 10, 2 );
		add_action( 'groundhogg/db/post_delete/company_relationship', [ $this, 'decrease_contact_count' ], 10 );
		add_action( 'groundhogg/db/pre_bulk_delete/company_relationships', [ $this, 'bulk_decrease_count' ], 10 );
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return array(
			'ID'            => '%d',
			'name'          => '%s',
			'slug'          => '%s',
			'description'   => '%s',
			'contact_count' => '%d',
			'domain'        => '%s',
			'date_created'  => '%s',
		);
	}

	/**
	 * @param \Groundhogg\Base_Object $object
	 *
	 * @return Company
	 */
	public function create_object( $object ) {
		return new Company( $object );
	}

	/**
	 * Get default column values
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function get_column_defaults() {
		return array(
			'ID'            => 0,
			'name'          => '',
			'slug'          => '',
			'description'   => '',
			'contact_count' => 0,
			'domain'        => '',
			'date_created'  => current_time( 'mysql' ),
		);
	}

	/**
	 * Increase the contact company count
	 *
	 * @param $insert_id
	 * @param $args
	 */
	public function increase_contact_count( $insert_id = 0, $args = [] ) {
		$company_id = absint( $args['company_id'] );

		if ( ! $this->exists( $company_id ) ) {
			return;
		}

		$tag                = $this->get( $company_id );
		$tag->contact_count = intval( $tag->contact_count ) + 1;
		$this->update( $company_id, array( 'contact_count' => $tag->contact_count ), $this->primary_key );
	}

	/**
	 * TODO
	 * Decrease the contact tag count
	 *
	 * @param $insert_id
	 * @param $args
	 */
	public function decrease_contact_count( $args = [] ) {
		if ( ! isset_not_empty( $args, 'company_id' ) ) {
			return;
		}

		$company_id = absint( $args['company_id'] );

		if ( ! $this->exists( $company_id ) ) {
			return;
		}

		$tag                = $this->get( $company_id );
		$tag->contact_count = intval( $tag->contact_count ) - 1;
		$this->update( $company_id, array( 'contact_count' => $tag->contact_count ), $this->primary_key );
	}

	/**
	 * Bulk decrease the contact count.
	 *
	 * @param $company_ids
	 */
	public function bulk_decrease_count( $company_ids ) {

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
	public function create_table() {
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
        ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,        
        name mediumtext NOT NULL,
        slug mediumtext NOT NULL,
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