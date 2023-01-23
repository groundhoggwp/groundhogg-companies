<?php

namespace GroundhoggCompanies;


use Groundhogg\Contact_Query;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;

class Search_Filters {

	public function __construct() {
		add_action( 'groundhogg/contact_query/register_filters', [ $this, 'register_filters' ] );
	}

	/**
	 * Register filters for the contact query
	 *
	 * @param $query Contact_Query
	 */
	public function register_filters( $query ) {

		// Core Stuff
		$query::register_filter( 'linked_company', [ $this, 'linked_company' ] );
		$query::register_filter( 'company_name', [ $this, 'company_name' ] );
		$query::register_filter( 'job_title', [ $this, 'job_title' ] );
		$query::register_filter( 'company_website', [ $this, 'company_website' ] );
		$query::register_filter( 'company_address', [ $this, 'company_address' ] );
		$query::register_filter( 'company_department', [ $this, 'company_department' ] );
		$query::register_filter( 'company_phone', [ $this, 'company_phone' ] );
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
		$obj_rel_tbl     = get_db( 'object_relationships' );
		$obj_rel_query   = "SELECT secondary_object_id FROM {$obj_rel_tbl->table_name} WHERE primary_object_type = 'company' AND secondary_object_type = 'contact'";
		return "{$query->table_name}.ID IN ( $obj_rel_query  )";
	}

	/**
	 * Filter by company name
	 *
	 * @param $filter_vars
	 * @param $query Contact_Query
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
	 * @param $query Contact_Query
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
	 * @param $query Contact_Query
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
	 * @param $query Contact_Query
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
	 * @param $query Contact_Query
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
	 * @param $query Contact_Query
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
