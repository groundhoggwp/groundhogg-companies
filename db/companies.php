<?php

namespace GroundhoggCompanies\DB;

use Groundhogg\DB\DB;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\get_primary_owner;
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
			'owner_id'      => '%d',
			'name'          => '%s',
			'slug'          => '%s',
			'description'   => '%s',
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
			'ID'           => 0,
			'owner_id'     => current_user_can( 'view_companies' ) ? get_current_user_id() : ( get_primary_owner() ? get_primary_owner()->ID : 0 ),
			'name'         => '',
			'slug'         => '',
			'description'  => '',
			'domain'       => '',
			'date_created' => current_time( 'mysql' ),
		);
	}

	/**
	 * Add support for owners
	 *
	 * @param array  $data
	 * @param string $ORDER_BY
	 * @param bool   $from_cache
	 *
	 * @return array|array[]|bool|int|object|object[]|null
	 */
	public function query( $data = [], $ORDER_BY = '', $from_cache = true ) {

		// Reps can't see others companies
		if ( current_user_can( 'view_companies' ) && ! current_user_can( 'view_others_companies' ) ) {
			$data['owner_id'] = get_current_user_id();
		}

		return parent::query( $data, $ORDER_BY, $from_cache );
	}

	public function add( $data = array() ) {

		if ( ! isset_not_empty( $data, 'name' ) ){
			return false;
		}

		if ( ! isset_not_empty( $data, 'slug' ) ) {
			$data['slug'] = sanitize_title( $data['name'] );
		}

		return parent::add( $data );
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
        slug varchar({$this->get_max_index_length()}) NOT NULL,
        description text NOT NULL,     
        domain VARCHAR(80) NOT NULL,
        owner_id bigint(20) unsigned NOT NULL,        
        date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,     
        PRIMARY KEY (ID),
        KEY slug (slug)        
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}