<?php

namespace GroundhoggCompanies;


use Groundhogg\Contact_Query;

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
		$query::register_filter( 'company_name', [ $this, 'company_name' ] );
		$query::register_filter( 'job_title', [ $this, 'job_title' ] );
		$query::register_filter( 'company_website', [ $this, 'company_website' ] );
		$query::register_filter( 'company_address', [ $this, 'company_address' ] );
		$query::register_filter( 'company_department', [ $this, 'company_department' ] );
		$query::register_filter( 'company_phone', [ $this, 'company_phone' ] );
	}

	/**
	 * Filter by company name
	 *
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @return string
	 */
	public static function company_name( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'equals',
		] );

		return $query::filter_meta( array_merge( $filter_vars, [
			'meta' => 'company_name'
		] ), $query );
	}

	/**
	 * Filter Job TITLE
	 *
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @return string
	 */
	public static function job_title( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'equals',
		] );

		return $query::filter_meta( array_merge( $filter_vars, [
			'meta' => 'job_title'
		] ), $query );
	}

	/**
	 * Filter website
	 *
	 * @param $filter_vars
	 * @param $query Contact_Query
	 *
	 * @return string
	 */
	public static function company_website( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'equals',
		] );

		return $query::filter_meta( array_merge( $filter_vars, [
			'meta' => 'company_website'
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
	public static function company_address( $filter_vars, $query ) {

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
	public static function company_department( $filter_vars, $query ) {

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
	public static function company_phone( $filter_vars, $query ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'value'   => '',
			'compare' => 'equals',
		] );

		return $query::filter_meta( array_merge( $filter_vars, [
			'meta' => 'company_phone'
		] ), $query );
	}
}