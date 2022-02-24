<?php

namespace GroundhoggCompanies;

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
				'name'        => __( 'Job Title', 'groundhogg' ),
				'description' => _x( 'The contact\'s job title.', 'replacement', 'groundhogg' ),
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
	 * Get the company name of a contact
	 *
	 * @param $contact_id
	 *
	 * @return mixed
	 */
	function replacement_company_name( $contact_id ) {
		return get_contactdata( $contact_id )->get_meta( 'company_name' );
	}

	/**
	 * Get the company address of a contact
	 *
	 * @param $contact_id
	 *
	 * @return mixed
	 */
	function replacement_company_address( $contact_id ) {
		return get_contactdata( $contact_id )->get_meta( 'company_address' );
	}

	/**
	 * Get the company address of a contact
	 *
	 * @param $contact_id
	 *
	 * @return mixed
	 */
	function replacement_company_phone( $contact_id ) {
		return get_contactdata( $contact_id )->get_meta( 'company_phone' );
	}

	/**
	 * Get the company address of a contact
	 *
	 * @param $contact_id
	 *
	 * @return mixed
	 */
	function replacement_company_department( $contact_id ) {
		return get_contactdata( $contact_id )->get_meta( 'company_department' );
	}


	/**
	 * Get the company address of a contact
	 *
	 * @param $contact_id
	 *
	 * @return mixed
	 */
	function replacement_company_website( $contact_id ) {
		return get_contactdata( $contact_id )->get_meta( 'company_website' );
	}

	/**
	 * Get the job title of a contact
	 *
	 * @param $contact_id
	 *
	 * @return mixed
	 */
	function replacement_job_title( $contact_id ) {
		return get_contactdata( $contact_id )->get_meta( 'job_title' );
	}

}