<?php

namespace GroundhoggCompanies\Abilities;

use Groundhogg\Abilities\Ability;
use WP_Error;
use function Groundhogg\get_contactdata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Update_Company extends Ability {

	use Has_Company_Lookup;

	protected const NAME       = 'groundhogg-companies/update-company';
	protected const CATEGORY   = 'groundhogg-companies';
	protected const CAPABILITY = 'edit_companies';

	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Update Company', 'groundhogg-companies' ),
			'description' => __( 'Update an existing company by ID. Only the fields you pass are changed.', 'groundhogg-companies' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'id' ],
				'properties'           => array_merge( [
					'id'                 => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The company ID - see groundhogg-companies/list-companies.', 'groundhogg-companies' ),
					],
					'name'               => [ 'type' => 'string' ],
					'domain'             => [ 'type' => 'string' ],
					'description'        => [ 'type' => 'string' ],
					'owner'              => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'A WordPress user ID to reassign as the company owner - see groundhogg/list-owners.', 'groundhogg-companies' ),
					],
					'primary_contact_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The ID of a contact already linked to this company.', 'groundhogg-companies' ),
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

		$company = self::get_company_for( absint( $input['id'] ), 'edit' );

		if ( is_wp_error( $company ) ) {
			return $company;
		}

		$data = [];

		if ( ! empty( $input['name'] ) ) {
			$data['name'] = sanitize_text_field( $input['name'] );
		}

		if ( isset( $input['domain'] ) ) {
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

		if ( ! empty( $input['primary_contact_id'] ) ) {

			$contact = get_contactdata( absint( $input['primary_contact_id'] ) );

			if ( ! $contact || ! $company->is_related( $contact ) ) {
				return new WP_Error( 'groundhogg_contact_not_in_company', __( 'The primary contact must be an existing contact linked to this company.', 'groundhogg-companies' ) );
			}

			$data['primary_contact_id'] = $contact->get_id();
		}

		$meta = self::meta_from_input( $input );

		if ( empty( $data ) && empty( $meta ) ) {
			return new WP_Error( 'groundhogg_no_changes', __( 'No fields to update were given.', 'groundhogg-companies' ) );
		}

		if ( ! empty( $data ) && ! $company->update( $data ) ) {
			return new WP_Error( 'groundhogg_company_not_updated', __( 'Unable to update the company. Another company may already use that name.', 'groundhogg-companies' ) );
		}

		self::save_company_meta( $company, $meta );

		return [
			'company' => Company_Schema::transform( $company ),
		];
	}
}
