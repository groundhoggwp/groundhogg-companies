<?php

namespace GroundhoggCompanies\Abilities;

use Groundhogg\Abilities\Ability;
use GroundhoggCompanies\Classes\Company;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Create_Company extends Ability {

	use Has_Company_Lookup;

	protected const NAME       = 'groundhogg-companies/create-company';
	protected const CATEGORY   = 'groundhogg-companies';
	protected const CAPABILITY = 'add_companies';

	protected function get_args(): array {

		return [
			'label'       => __( 'Create Company', 'groundhogg-companies' ),
			'description' => __( 'Create a new company. Errors if a company with the same name already exists - use groundhogg-companies/update-company to change an existing one, and groundhogg-companies/add-contact-to-company to link contacts.', 'groundhogg-companies' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'name' ],
				'properties'           => array_merge( [
					'name'        => [ 'type' => 'string' ],
					'domain'      => [
						'type'        => 'string',
						'description' => __( 'The company website, e.g. https://example.com.', 'groundhogg-companies' ),
					],
					'description' => [ 'type' => 'string' ],
					'owner'       => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'A WordPress user ID to own the company - see groundhogg/list-owners. Defaults to the current user.', 'groundhogg-companies' ),
					],
				], self::meta_input_properties() ),
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'company' => Company_Schema::get_schema(),
				],
			],
		];
	}

	public function __invoke( $input ) {

		$name = sanitize_text_field( $input['name'] );

		if ( $name === '' ) {
			return new WP_Error( 'groundhogg_invalid_company_name', __( 'A company name is required.', 'groundhogg-companies' ) );
		}

		if ( ( new Company( sanitize_title( $name ), 'slug' ) )->exists() ) {
			return new WP_Error( 'groundhogg_company_exists', __( 'A company with this name already exists.', 'groundhogg-companies' ) );
		}

		$data = [ 'name' => $name ];

		if ( ! empty( $input['domain'] ) ) {
			$data['domain'] = $input['domain'];
		}

		if ( isset( $input['description'] ) ) {
			$data['description'] = sanitize_textarea_field( $input['description'] );
		}

		if ( ! empty( $input['owner'] ) ) {

			$owner_id = self::resolve_company_owner( absint( $input['owner'] ) );

			if ( is_wp_error( $owner_id ) ) {
				return $owner_id;
			}

			$data['owner_id'] = $owner_id;
		}

		$company = new Company();
		$company->create( $data );

		if ( ! $company->exists() ) {
			return new WP_Error( 'groundhogg_company_not_created', __( 'Unable to create the company.', 'groundhogg-companies' ) );
		}

		self::save_company_meta( $company, self::meta_from_input( $input ) );

		return [
			'company' => Company_Schema::transform( $company ),
		];
	}
}
