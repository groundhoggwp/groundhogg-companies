<?php

namespace GroundhoggCompanies\Abilities;

use Groundhogg\Abilities\Ability;
use function Groundhogg\get_db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class List_Companies extends Ability {

	protected const NAME       = 'groundhogg-companies/list-companies';
	protected const CATEGORY   = 'groundhogg-companies';
	protected const CAPABILITY = 'view_companies';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Companies', 'groundhogg-companies' ),
			'description' => __( 'Search and list companies. Results are scoped to the companies the current user is allowed to see.', 'groundhogg-companies' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'search' => [
						'type'        => 'string',
						'description' => __( 'Free-text search against the company name, domain and description.', 'groundhogg-companies' ),
					],
					'owner'  => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'Only companies owned by this WordPress user ID.', 'groundhogg-companies' ),
					],
					'expand' => [
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
							'enum' => Company_Schema::expand_options(),
						],
						'default'     => [],
						'description' => __( 'Optional extra sections to expand on each company. Available: meta, contacts (each costs extra queries per company).', 'groundhogg-companies' ),
					],
					'limit'  => [
						'type'    => 'integer',
						'minimum' => 1,
						'maximum' => 200,
						'default' => 50,
					],
					'offset' => [
						'type'    => 'integer',
						'minimum' => 0,
						'default' => 0,
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'total_items' => [
						'type'        => 'integer',
						'description' => __( 'Total companies matching, ignoring limit/offset.', 'groundhogg-companies' ),
					],
					'companies'   => [
						'type'  => 'array',
						'items' => Company_Schema::get_schema(),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$query_vars = [
			'limit'      => ! empty( $input['limit'] ) ? min( absint( $input['limit'] ), 200 ) : 50,
			'offset'     => ! empty( $input['offset'] ) ? absint( $input['offset'] ) : 0,
			'found_rows' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];

		if ( ! empty( $input['search'] ) ) {
			$query_vars['search'] = sanitize_text_field( $input['search'] );
		}

		if ( ! empty( $input['owner'] ) ) {
			$query_vars['owner_id'] = absint( $input['owner'] );
		}

		$db      = get_db( 'companies' );
		$results = $db->query( $query_vars );

		// Capture immediately - FOUND_ROWS() is clobbered by any query run afterwards, like the expand loop below.
		$total_items = $db->found_rows();

		$expand = $input['expand'] ?? [];

		return [
			'total_items' => $total_items,
			'companies'   => array_map( function ( $company ) use ( $expand ) {
				return Company_Schema::transform( $company, $expand );
			}, $results ),
		];
	}
}
