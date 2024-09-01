<?php

namespace GroundhoggCompanies\DB;

use Groundhogg\Contact;
use Groundhogg\DB\DB;
use Groundhogg\DB\Query\Filters;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\DB\Query\Where;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\get_primary_owner;
use function Groundhogg\isset_not_empty;
use function GroundhoggCompanies\properties;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Companies DB
 *
 * Store Companies
 *
 * @since       File available since Release 1.0
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2020, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
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
	 * Get the object type we're inserting/updating/deleting.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return 'company';
	}

	/**
	 * Swap primary contact IDs
	 *
	 * @param $contact Contact
	 * @param $other   Contact
	 */
	public function contact_merged( $contact, $other ) {

		$this->update( [
			'primary_contact_id' => $other->get_id(),
		],
		[
			'primary_contact_id' => $contact->get_id()
		] );
	}

	protected function maybe_register_filters() {

		if ( $this->query_filters ) {
			return;
		}

		parent::maybe_register_filters();

		$this->query_filters->register_from_properties( properties()->get_fields() );

		foreach ( [ 'industry', 'address', 'phone' ] as $meta_key ) {
			$this->query_filters->register( $meta_key, function ( $filter, Where $where ) use ( $meta_key ) {
				$alias = $where->query->joinMeta( $meta_key );
				Filters::string( "$alias.meta_value", $filter, $where );
			} );
		}

		$this->query_filters->register( 'num_contacts', function ( $filter, Where $where ) {

			$relQuery = new Table_Query( 'object_relationships' );
			$relQuery->setSelect( [ 'COUNT(secondary_object_id)', 'total_contacts' ], 'primary_object_id' )->setGroupby( 'primary_object_id' )
			         ->where( 'primary_object_type', 'company' )
			         ->equals( 'secondary_object_type', 'contact' );

			$join = $where->query->addJoin( 'LEFT', $relQuery );
			$join->onColumn( 'primary_object_id', 'ID' );
			Filters::number( "$join->alias.total_contacts", $filter, $where );
		} );

		$this->query_filters->register( 'contacts', function ( $filter, Where $where ) {

			$filter = wp_parse_args( $filter, [
				'contacts' => []
			] );

			$contact_ids = wp_parse_id_list( $filter['contacts'] );

			if ( $where->hasCondition( 'contact_relationships' ) ) {

				$relQuery = new Table_Query( 'object_relationships' );
				$relQuery->setSelect( 'primary_object_id' )
				         ->where( 'primary_object_type', 'company' )
				         ->equals( 'secondary_object_type', 'contact' )
				         ->in( 'secondary_object_id', $contact_ids );

				$where->in( 'ID', $relQuery );

				return;
			}

			$join = $where->query->addJoin( 'LEFT', [ 'object_relationships', 'contact_relationships' ] );

			$join->onColumn( 'primary_object_id' )
			     ->equals( 'contact_relationships.primary_object_type', 'company' )
			     ->equals( 'contact_relationships.secondary_object_type', 'contact' );

			$where->in( 'contact_relationships.secondary_object_id', $contact_ids );

			$where->query->setGroupby( 'ID' );
		} );
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return [
			'ID'                 => '%d',
			'owner_id'           => '%d',
			'primary_contact_id' => '%d',
			'name'               => '%s',
			'slug'               => '%s',
			'description'        => '%s',
			'domain'             => '%s',
			'date_created'       => '%s',
		];
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
		return [
			'ID'                 => 0,
			'owner_id'           => current_user_can( 'view_companies' ) ? get_current_user_id() : ( get_primary_owner() ? get_primary_owner()->ID : 0 ),
			'primary_contact_id' => 0,
			'name'               => '',
			'slug'               => '',
			'description'        => '',
			'domain'             => '',
			'date_created'       => current_time( 'mysql' ),
		];
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

		if ( ! isset_not_empty( $data, 'name' ) ) {
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
        primary_contact_id bigint(20) unsigned NOT NULL,        
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
