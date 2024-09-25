<?php

namespace GroundhoggCompanies;

use Groundhogg\Contact;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;

class Replacements {

	public function __construct() {
		add_action( 'groundhogg/replacements/init', [ $this, 'register' ] );
	}

	/**
	 * @param $replacments \Groundhogg\Replacements
	 */
	public function register( $replacments ) {

		$replacments->add_group( 'company', __( 'Company', 'groundhogg-company' ) );

		$_replacements = [
			[
				'code'        => 'company_name',
				'group'       => 'company',
				'callback'    => [ $this, 'replacement_company_name' ],
				'name'        => __( 'Company Name', 'groundhogg' ),
				'description' => _x( 'The contact\'s company name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'company_website',
				'group'       => 'company',
				'callback'    => [ $this, 'replacement_company_website' ],
				'name'        => __( 'Company Website', 'groundhogg' ),
				'description' => _x( 'The contact\'s company website.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'job_title',
				'group'       => 'company',
				'callback'    => [ $this, 'replacement_job_title' ],
				'name'        => __( 'Position', 'groundhogg' ),
				'description' => _x( 'The contact\'s position.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'company_department',
				'group'       => 'company',
				'callback'    => [ $this, 'replacement_company_department' ],
				'name'        => __( 'Department', 'groundhogg' ),
				'description' => _x( 'The contact\'s department.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'company_address',
				'group'       => 'company',
				'callback'    => [ $this, 'replacement_company_address' ],
				'name'        => __( 'Address', 'groundhogg' ),
				'description' => _x( 'The contact\'s company address.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'company_phone',
				'group'       => 'company',
				'callback'    => [ $this, 'replacement_company_phone' ],
				'name'        => __( 'Phone', 'groundhogg' ),
				'description' => _x( 'The contact\'s company phone number.', 'replacement', 'groundhogg' ),
			],
		];

		foreach ( $_replacements as $replacement ) {
			$replacments->add(
				$replacement['code'],
				$replacement['callback'],
				$replacement['description'],
				get_array_var( $replacement, 'name' ),
				get_array_var( $replacement, 'group' ),
				get_array_var( $replacement, 'default_args' )
			);
		}
	}

	/**
	 * Get a related company record if available
	 *
	 * @param $contact Contact
	 *
	 * @return Company|false
	 */
	public function get_related_company( $contact ) {
		$companies = $contact->get_related_objects( 'company', false );

		return count( $companies ) > 0 ? $companies[0] : false;
	}

	/**
	 * Get the company name of a contact
	 *
	 * @param $contact_id
	 *
	 * @return mixed
	 */
	function replacement_company_name( $contact_id ) {

		$contact = get_contactdata( $contact_id );

		if ( ! $contact ){
			return '';
		}

		$company = $this->get_related_company( $contact );

		return $company ? $company->get_name() : $contact->get_meta( 'company_name' );
	}

	/**
	 * Get the company address of a contact
	 *
	 * @param $contact_id
	 *
	 * @return mixed
	 */
	function replacement_company_address( $contact_id ) {
		$contact = get_contactdata( $contact_id );

		if ( ! $contact ){
			return '';
		}

		$company = $this->get_related_company( $contact );

		return $company ? $company->get_address() : $contact->get_meta( 'company_address' );
	}

	/**
	 * Get the company address of a contact
	 *
	 * @param $contact_id
	 *
	 * @return mixed
	 */
	function replacement_company_phone( $contact_id ) {
		$contact = get_contactdata( $contact_id );

		if ( ! $contact ){
			return '';
		}

		$company = $this->get_related_company( $contact );

		return $company ? $company->get_meta( 'phone' ) : $contact->get_meta( 'company_phone' );
	}

	/**
	 * Get the company address of a contact
	 *
	 * @param $contact_id
	 *
	 * @return mixed
	 */
	function replacement_company_website( $contact_id ) {
		$contact = get_contactdata( $contact_id );

		if ( ! $contact ){
			return '';
		}

		$company = $this->get_related_company( $contact );

		return $company ? $company->get_domain() : $contact->get_meta( 'company_website' );
	}


	/**
	 * Get the company address of a contact
	 *
	 * @param $contact_id
	 *
	 * @return mixed
	 */
	function replacement_company_department( $contact_id ) {

		$contact = get_contactdata( $contact_id );

		if ( ! $contact ){
			return '';
		}

		return $contact->get_meta( 'company_department' );
	}

	/**
	 * Get the job title of a contact
	 *
	 * @param $contact_id
	 *
	 * @return mixed
	 */
	function replacement_job_title( $contact_id ) {

		$contact = get_contactdata( $contact_id );

		if ( ! $contact ){
			return '';
		}

		return $contact->get_meta( 'job_title' );
	}

}
