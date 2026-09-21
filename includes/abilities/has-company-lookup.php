<?php

namespace GroundhoggCompanies\Abilities;

use GroundhoggCompanies\Classes\Company;
use WP_Error;
use function Groundhogg\sanitize_object_meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared helpers for abilities that operate on a single existing company.
 */
trait Has_Company_Lookup {

	/**
	 * Find a company by ID and confirm the current user may perform $action on it. The base
	 * CAPABILITY only gates whether the ability can run at all - this is the per-object
	 * (owner/team scoped) check, the same one the Companies_Api relies on.
	 *
	 * @param int    $id
	 * @param string $action view|edit|delete
	 *
	 * @return Company|WP_Error
	 */
	protected static function get_company_for( int $id, string $action = 'view' ) {

		$company = new Company( $id );

		if ( ! $company->exists() ) {
			return new WP_Error( 'groundhogg_company_not_found', __( 'Company not found.', 'groundhogg-companies' ) );
		}

		if ( ! current_user_can( $action . '_company', $company ) ) {
			return new WP_Error(
				'groundhogg_cannot_access_company',
				__( 'You do not have permission to access this company.', 'groundhogg-companies' )
			);
		}

		return $company;
	}

	/**
	 * Validate a user ID can own companies.
	 *
	 * @param int $owner_id
	 *
	 * @return int|WP_Error
	 */
	protected static function resolve_company_owner( int $owner_id ) {

		$owner = get_userdata( $owner_id );

		if ( ! $owner || ! user_can( $owner, 'view_companies' ) ) {
			return new WP_Error(
				'groundhogg_invalid_owner',
				__( 'The given owner is not a valid WordPress user who can own companies. See groundhogg/list-owners for valid IDs.', 'groundhogg-companies' )
			);
		}

		return $owner_id;
	}

	/**
	 * Collect the meta values from an ability input - the explicit industry/phone/address
	 * fields plus the free-form meta map.
	 *
	 * @param array $input
	 *
	 * @return array
	 */
	protected static function meta_from_input( array $input ): array {

		$meta = is_array( $input['meta'] ?? null ) ? $input['meta'] : [];

		foreach ( [ 'industry', 'phone', 'address' ] as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$meta[ $key ] = $input[ $key ];
			}
		}

		return $meta;
	}

	/**
	 * Write meta from an ability input, skipping private (underscore-prefixed) keys.
	 *
	 * @param Company $company
	 * @param array   $meta
	 *
	 * @return void
	 */
	protected static function save_company_meta( Company $company, array $meta ) {

		foreach ( $meta as $key => $value ) {

			$key = sanitize_key( $key );

			if ( ! $key || strpos( $key, '_' ) === 0 ) {
				continue;
			}

			$company->update_meta( $key, sanitize_object_meta( $value, $key, 'company' ) );
		}
	}

	/**
	 * Input schema fragment for the writable meta fields shared by create and update.
	 *
	 * @return array
	 */
	protected static function meta_input_properties(): array {
		return [
			'industry' => [ 'type' => 'string' ],
			'phone'    => [ 'type' => 'string' ],
			'address'  => [
				'type'        => 'string',
				'description' => __( 'The full address. Separate lines with a newline.', 'groundhogg-companies' ),
			],
			'meta'     => [
				'type'                 => 'object',
				'additionalProperties' => true,
				'description'          => __( 'Additional company meta / custom property values as key => value. Keys starting with an underscore are ignored.', 'groundhogg-companies' ),
			],
		];
	}
}
