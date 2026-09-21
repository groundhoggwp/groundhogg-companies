<?php

namespace GroundhoggCompanies\Abilities;

use Groundhogg\Abilities\Ability;
use GroundhoggCompanies\Classes\Company;
use function GroundhoggCompanies\sanitize_domain_name;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Get_Company extends Ability {

	use Has_Company_Lookup;

	protected const NAME       = 'groundhogg-companies/get-company';
	protected const CATEGORY   = 'groundhogg-companies';
	protected const CAPABILITY = 'view_companies';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Get Company', 'groundhogg-companies' ),
			'description' => __( 'Retrieve a company by ID or domain.', 'groundhogg-companies' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'id'     => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The company ID.', 'groundhogg-companies' ),
					],
					'domain' => [
						'type'        => 'string',
						'description' => __( 'The company website/domain, e.g. https://example.com.', 'groundhogg-companies' ),
					],
					'expand' => [
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
							'enum' => Company_Schema::expand_options(),
						],
						'default'     => [],
						'description' => __( 'Optional extra sections. Available: meta (raw meta and custom properties), contacts (related contacts).', 'groundhogg-companies' ),
					],
				],
				'anyOf'                => [
					[ 'required' => [ 'id' ] ],
					[ 'required' => [ 'domain' ] ],
				],
			],

			'output_schema' => Company_Schema::get_schema(),
		];
	}

	public function __invoke( $input ) {

		if ( ! empty( $input['id'] ) ) {
			$id = absint( $input['id'] );
		} else {
			$found = new Company( sanitize_domain_name( $input['domain'] ?? '' ), 'domain' );
			$id    = $found->exists() ? $found->get_id() : 0;
		}

		$company = self::get_company_for( $id, 'view' );

		if ( is_wp_error( $company ) ) {
			return $company;
		}

		return Company_Schema::transform( $company, $input['expand'] ?? [] );
	}
}
