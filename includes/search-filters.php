<?php

namespace GroundhoggCompanies;


use Groundhogg\DB\Query\Filters;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\DB\Query\Where;
use Groundhogg\Legacy_Contact_Query;
use function Groundhogg\get_db;

class Search_Filters {

	public function __construct() {
		add_action( 'groundhogg/contact_query/filters/register', [ $this, 'register_filters' ] );
		add_action( 'groundhogg/contact_query/register_filters', [ $this, 'register_legacy_filters' ] );
		add_action( 'groundhogg/company/pre_get_results', [ $this, 'modify_table_query' ] );
	}

	/**
	 * @param Table_Query $query
	 *
	 * @return void
	 */
	function modify_table_query( Table_Query &$query ) {

		if ( $query->orderby === 'contact_count' ) {
			$relQuery = new Table_Query( 'object_relationships' );
			$relQuery->setSelect( 'primary_object_id', [ 'COUNT(secondary_object_id)', 'contact_count' ] )
			         ->setGroupby( 'primary_object_id' )
			         ->where()
			         ->equals( 'primary_object_type', 'company' )
			         ->equals( 'secondary_object_type', 'contact' );

			$join = $query->addJoin( 'LEFT', [ $relQuery, 'relcount' ] );
			$join->onColumn( 'primary_object_id', 'ID' );
		}

	}

	/**
	 * Register filters for the contact query
	 *
	 * @param $query Legacy_Contact_Query
	 */
	public function register_legacy_filters( $query ) {

		// Core Stuff
		$query::register_filter( 'linked_company', [ $this, 'linked_company' ] );
		$query::register_filter( 'company_name', [ $this, 'company_name' ] );
		$query::register_filter( 'job_title', [ $this, 'job_title' ] );
		$query::register_filter( 'company_website', [ $this, 'company_website' ] );
		$query::register_filter( 'company_address', [ $this, 'company_address' ] );
		$query::register_filter( 'company_department', [ $this, 'company_department' ] );
		$query::register_filter( 'company_phone', [ $this, 'company_phone' ] );
	}

	/**
	 * Add contact query filters
	 *
	 * @param Filters $filters
	 *
	 * @return void
	 */
	public function register_filters( Filters $filters ) {
		// Primary contact filter
		$filters->register( 'company_primary_contacts', function ( $filter, Where $where ) {

			unset( $filter['query']['order'] );
			unset( $filter['query']['orderby'] );
			unset( $filter['query']['limit'] );
			unset( $filter['query']['select'] );
			unset( $filter['query']['offset'] );
			unset( $filter['query']['found_rows'] );

			$companyQuery = new Table_Query( 'companies' );
			$companyQuery->setSelect( 'primary_contact_id' )->set_query_params( $filter['query'] );

			$where->in( 'ID', $companyQuery );
		} );

		// All contacts filter
		$filters->register( 'company_all_contacts', [ $this, 'filter_all_related_contacts' ] );
		$filters->register( 'linked_company', [ $this, 'filter_all_related_contacts' ] );
		// Company name
		$filters->register( 'company_name', function ( $filter, Where $where ) {
			$this->related_or_meta( $filter, 'company_name', 'name', $where );
		} );
		// Company website
		$filters->register( 'company_website', function ( $filter, Where $where ) {
			$this->related_or_meta( $filter, 'company_website', 'domain', $where );
		} );
		$filters->register( 'company_address', function ( $filter, Where $where ) {
			$this->related_or_meta( $filter, 'company_address', 'address', $where );
		} );
		$filters->register( 'company_phone', function ( $filter, Where $where ){
			$this->related_or_meta( $filter, 'company_phone', 'phone', $where );
		} );

		$filters->register( 'company_department', function ( $filter, Where $where ){
			Filters::meta_filter( array_merge( $filter, [
				'meta' => 'company_department'
			] ), $where );
		} );

		$filters->register( 'job_title', function ( $filter, Where $where ){
			Filters::meta_filter( array_merge( $filter, [
				'meta' => 'job_title'
			] ), $where );
		} );

	}

	public function related_or_meta( $filter, $meta, $type, $where ){
		$filter = wp_parse_args( $filter, [
			'value'   => '',
			'compare' => 'equals',
		] );

		$subWhere = $where->subWhere();

		Filters::meta_filter( array_merge( $filter, [
			'meta' => $meta
		] ), $subWhere );

		$this->filter_all_related_contacts( [
			'query' => [
				'filters' => [
					[
						[
							'type'    => $type,
							'compare' => $filter['compare'],
							'value'   => $filter['value'],
						]
					]
				]
			]
		], $subWhere );
	}

	/**
	 * @throws \Groundhogg\DB\Query\FilterException
	 *
	 * @param Where $where
	 * @param       $filter
	 *
	 * @return void
	 */
	public function filter_all_related_contacts( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'query' => []
		] );

		unset( $filter['query']['order'] );
		unset( $filter['query']['orderby'] );
		unset( $filter['query']['limit'] );
		unset( $filter['query']['select'] );
		unset( $filter['query']['offset'] );
		unset( $filter['query']['found_rows'] );

		if ( empty( $filter['query'] ) ) {
			$relationships = new Table_Query( 'object_relationships' );
			$relationships->setSelect( 'secondary_object_id' )
			              ->where()
			              ->equals( 'primary_object_type', 'company' )
			              ->equals( 'secondary_object_type', 'contact' );
			$where->in( 'ID', $relationships );

			return;
		}

		$companyQuery = new Table_Query( 'companies' );
		$join         = $companyQuery->addJoin( 'RIGHT', 'object_relationships' );
		$join->onColumn( 'primary_object_id', 'ID' )->equals( 'primary_object_type', 'company' );
		$companyQuery->where->equals( "$join->alias.secondary_object_type", 'contact' );
		$companyQuery->set_query_params( $filter['query'] );

		$companyQuery->setSelect( "$join->alias.secondary_object_id" );

		$where->in( 'ID', $companyQuery );
	}

	public function companies_query( $clause, $query ) {
		$obj_rel_tbl     = get_db( 'object_relationships' );
		$companies_tbl   = get_db( 'companies' );
		$companies_query = "SELECT ID FROM {$companies_tbl->table_name} as companies WHERE " . $clause;
		$obj_rel_query   = "SELECT secondary_object_id FROM {$obj_rel_tbl->table_name} WHERE primary_object_type = 'company' AND secondary_object_type = 'contact' AND primary_object_id IN ( $companies_query )";

		return "{$query->table_name}.ID IN ( $obj_rel_query  )";
	}

	/**
	 * Contact is linked to a company record
	 *
	 * @param $filter_vars
	 * @param $query
	 *
	 * @return string
	 */
	public function linked_company( $filter_vars, $query ) {
		$obj_rel_tbl   = get_db( 'object_relationships' );
		$obj_rel_query = "SELECT secondary_object_id FROM {$obj_rel_tbl->table_name} WHERE primary_object_type = 'company' AND secondary_object_type = 'contact'";

		return "{$query->table_name}.ID IN ( $obj_rel_query  )";
	}

	/**
	 * Filter by company name
	 *
	 * @param $filter_vars
	 * @param $query Legacy_Contact_Query
	 *
	 * @return string
	 */
	public function company_name( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'equals',
		] );

		$clause = $query::generic_text_compare( 'companies.name', $filter_vars['compare'], $filter_vars['value'] );

		return $this->companies_query( $clause, $query );
	}

	/**
	 * Filter website
	 *
	 * @param $filter_vars
	 * @param $query Legacy_Contact_Query
	 *
	 * @return string
	 */
	public function company_website( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'contains',
		] );

		$clause = $query::generic_text_compare( 'companies.domain', $filter_vars['compare'], $filter_vars['value'] );

		return $this->companies_query( $clause, $query );
	}

	/**
	 * Filter Job TITLE
	 *
	 * @param $filter_vars
	 * @param $query Legacy_Contact_Query
	 *
	 * @return string
	 */
	public function job_title( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'equals',
		] );

		return $query::filter_meta( array_merge( $filter_vars, [
			'meta' => 'job_title'
		] ), $query );
	}

	/**
	 * Filter address
	 *
	 * @param $filter_vars
	 * @param $query Legacy_Contact_Query
	 *
	 * @return string
	 */
	public function company_address( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'equals',
		] );

		return $query::filter_meta( array_merge( $filter_vars, [
			'meta' => 'company_address'
		] ), $query );
	}

	/**
	 * Filter department
	 *
	 * @param $filter_vars
	 * @param $query Legacy_Contact_Query
	 *
	 * @return string
	 */
	public function company_department( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'equals',
		] );

		return $query::filter_meta( array_merge( $filter_vars, [
			'meta' => 'company_department'
		] ), $query );
	}

	/**
	 * Filter department
	 *
	 * @param $filter_vars
	 * @param $query Legacy_Contact_Query
	 *
	 * @return string
	 */
	public function company_phone( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'equals',
		] );

		return $query::filter_meta( array_merge( $filter_vars, [
			'meta' => 'company_phone'
		] ), $query );
	}
}
