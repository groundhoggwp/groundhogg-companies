<?php

use GroundhoggCompanies\Classes\Company;
use function Groundhogg\get_contactdata;

/**
 * Exercises the groundhogg-companies/* abilities through the WordPress Abilities API, so the
 * input schema validation, permission callback and execute callback all run like they do for MCP.
 */
class Company_Abilities_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * @param string $name  ability name without the namespace
	 * @param array  $input
	 *
	 * @return mixed|WP_Error
	 */
	protected function run_ability( string $name, array $input = [] ) {
		$ability = wp_get_ability( 'groundhogg-companies/' . $name );
		$this->assertNotNull( $ability, "Ability $name should be registered" );

		return $ability->execute( $input ?: null );
	}

	protected function make_company( array $args = [] ): Company {
		$company = new Company();
		$company->create( wp_parse_args( $args, [ 'name' => 'Acme ' . wp_generate_password( 6, false ) ] ) );

		return $company;
	}

	protected function make_contact() {
		$contact = self::factory()->contacts->create_and_get();

		return get_contactdata( $contact->ID );
	}

	public function test_all_abilities_are_registered() {
		foreach ( [
			'list-companies',
			'get-company',
			'create-company',
			'update-company',
			'add-contact-to-company',
			'remove-contact-from-company',
			'add-company-note',
			'list-company-notes',
		] as $name ) {
			$this->assertNotNull( wp_get_ability( 'groundhogg-companies/' . $name ), $name );
		}

		$this->assertNotNull( wp_get_ability_category( 'groundhogg-companies' ) );
	}

	public function test_contact_schema_work_details() {
		$contact = $this->make_contact();
		$contact->update_meta( 'job_title', 'CTO' );
		$contact->update_meta( 'company_phone', '555-0100' );
		$contact->update_meta( 'company_department', 'Engineering' );

		$ability = wp_get_ability( 'groundhogg/get-contact' );

		$this->assertArrayHasKey( 'work_details', $ability->get_output_schema()['properties'] );
		$this->assertContains( 'work_details', $ability->get_input_schema()['properties']['expand']['items']['enum'] );

		$plain = $ability->execute( [ 'id' => $contact->get_id() ] );
		$this->assertArrayNotHasKey( 'work_details', $plain, 'Only present when requested' );

		$result = $ability->execute( [ 'id' => $contact->get_id(), 'expand' => [ 'work_details' ] ] );

		$this->assertNotWPError( $result );
		$this->assertEquals( 'CTO', $result['work_details']->job_title );
		$this->assertEquals( '555-0100', $result['work_details']->company_phone );
		$this->assertEquals( 'Engineering', $result['work_details']->company_department );
		$this->assertEquals( '', $result['work_details']->company_name );
	}

	public function test_contact_work_details_fall_back_to_linked_company() {
		$contact = $this->make_contact();
		$company = $this->make_company( [ 'name' => 'Fallback Co', 'domain' => 'https://fallback.test' ] );
		$company->create_relationship( $contact );
		$company->update_meta( 'phone', '555-9999' );
		$contact->update_meta( 'company_phone', '555-0100' ); // the contact's own value wins

		$result = wp_get_ability( 'groundhogg/get-contact' )->execute( [ 'id' => $contact->get_id(), 'expand' => [ 'work_details' ] ] );

		$this->assertNotWPError( $result );
		$this->assertEquals( 'Fallback Co', $result['work_details']->company_name );
		$this->assertEquals( 'https://fallback.test', $result['work_details']->company_website );
		$this->assertEquals( '555-0100', $result['work_details']->company_phone );
	}

	public function test_create_company_with_meta() {

		$result = $this->run_ability( 'create-company', [
			'name'     => 'Widgets Inc',
			'domain'   => 'https://widgets.test',
			'industry' => 'Manufacturing',
			'phone'    => '555-1234',
			'meta'     => [ '_private' => 'nope' ],
		] );

		$this->assertNotWPError( $result );

		$company = new Company( $result['company']['id'] );

		$this->assertTrue( $company->exists() );
		$this->assertEquals( 'Widgets Inc', $company->get_name() );
		$this->assertEquals( 'Manufacturing', $company->get_meta( 'industry' ) );
		$this->assertEquals( 'Manufacturing', $result['company']['industry'] );
		$this->assertEmpty( $company->get_meta( '_private' ), 'Private meta keys must be ignored' );
	}

	public function test_create_company_duplicate_name_errors() {
		$this->make_company( [ 'name' => 'Dupe Co' ] );

		$result = $this->run_ability( 'create-company', [ 'name' => 'Dupe Co' ] );

		$this->assertWPError( $result );
		$this->assertEquals( 'groundhogg_company_exists', $result->get_error_code() );
	}

	public function test_get_company_by_id_and_expand() {
		$company = $this->make_company();
		$contact = $this->make_contact();
		$company->create_relationship( $contact );
		$company->update_meta( 'industry', 'Retail' );

		$result = $this->run_ability( 'get-company', [ 'id' => $company->get_id(), 'expand' => [ 'contacts', 'meta' ] ] );

		$this->assertNotWPError( $result );
		$this->assertEquals( $company->get_id(), $result['id'] );
		$this->assertEquals( $contact->get_id(), $result['contacts'][0]['id'] );
		$this->assertEquals( 'Retail', $result['meta']->industry );
	}

	public function test_get_company_not_found() {
		$result = $this->run_ability( 'get-company', [ 'id' => 999999 ] );

		$this->assertWPError( $result );
		$this->assertEquals( 'groundhogg_company_not_found', $result->get_error_code() );
	}

	public function test_list_companies_search_and_total() {
		$this->make_company( [ 'name' => 'Zebra Findable Ltd' ] );
		$this->make_company( [ 'name' => 'Other Corp' ] );

		$result = $this->run_ability( 'list-companies', [ 'search' => 'Findable', 'limit' => 1 ] );

		$this->assertNotWPError( $result );
		$this->assertEquals( 1, $result['total_items'] );
		$this->assertCount( 1, $result['companies'] );
		$this->assertEquals( 'Zebra Findable Ltd', $result['companies'][0]['name'] );
	}

	public function test_update_company() {
		$company = $this->make_company( [ 'name' => 'Before' ] );

		$result = $this->run_ability( 'update-company', [
			'id'      => $company->get_id(),
			'name'    => 'After',
			'address' => '1 Main St' . PHP_EOL . 'Town',
		] );

		$this->assertNotWPError( $result );

		$fresh = new Company( $company->get_id() );
		$this->assertEquals( 'After', $fresh->get_name() );
		$this->assertEquals( '1 Main St' . PHP_EOL . 'Town', $fresh->get_meta( 'address' ) );
	}

	public function test_update_company_requires_changes() {
		$company = $this->make_company();

		$result = $this->run_ability( 'update-company', [ 'id' => $company->get_id() ] );

		$this->assertWPError( $result );
		$this->assertEquals( 'groundhogg_no_changes', $result->get_error_code() );
	}

	public function test_update_company_name_conflict_errors() {
		$this->make_company( [ 'name' => 'Taken Name' ] );
		$company = $this->make_company( [ 'name' => 'Mine' ] );

		$result = $this->run_ability( 'update-company', [ 'id' => $company->get_id(), 'name' => 'Taken Name' ] );

		$this->assertWPError( $result );
		$this->assertEquals( 'Mine', ( new Company( $company->get_id() ) )->get_name() );
	}

	public function test_add_and_remove_contact() {
		$company = $this->make_company();
		$contact = $this->make_contact();

		$added = $this->run_ability( 'add-contact-to-company', [ 'company_id' => $company->get_id(), 'contact_id' => $contact->get_id() ] );

		$this->assertNotWPError( $added );
		$this->assertTrue( (bool) ( new Company( $company->get_id() ) )->is_related( $contact ) );
		$this->assertEquals( $contact->get_id(), ( new Company( $company->get_id() ) )->primary_contact_id, 'First contact becomes primary' );

		// idempotent
		$again = $this->run_ability( 'add-contact-to-company', [ 'company_id' => $company->get_id(), 'contact_id' => $contact->get_id() ] );
		$this->assertNotWPError( $again );
		$this->assertCount( 1, $again['company']['contacts'] );

		$removed = $this->run_ability( 'remove-contact-from-company', [ 'company_id' => $company->get_id(), 'contact_id' => $contact->get_id() ] );

		$this->assertNotWPError( $removed );
		$this->assertFalse( (bool) ( new Company( $company->get_id() ) )->is_related( $contact ) );

		$missing = $this->run_ability( 'remove-contact-from-company', [ 'company_id' => $company->get_id(), 'contact_id' => $contact->get_id() ] );
		$this->assertWPError( $missing );
	}

	public function test_update_primary_contact_must_be_linked() {
		$company = $this->make_company();
		$contact = $this->make_contact();

		$result = $this->run_ability( 'update-company', [ 'id' => $company->get_id(), 'primary_contact_id' => $contact->get_id() ] );

		$this->assertWPError( $result );
		$this->assertEquals( 'groundhogg_contact_not_in_company', $result->get_error_code() );
	}

	public function test_notes() {
		$company = $this->make_company();

		$added = $this->run_ability( 'add-company-note', [ 'company_id' => $company->get_id(), 'content' => 'Called them today' ] );

		$this->assertNotWPError( $added );
		$this->assertEquals( $company->get_id(), $added['note']['company_id'] );

		$list = $this->run_ability( 'list-company-notes', [ 'company_id' => $company->get_id() ] );

		$this->assertNotWPError( $list );
		$this->assertEquals( 1, $list['total_items'] );
		$this->assertStringContainsString( 'Called them today', $list['notes'][0]['content'] );

		// a note on another company is not listed
		$other = $this->make_company();
		$empty = $this->run_ability( 'list-company-notes', [ 'company_id' => $other->get_id() ] );
		$this->assertEquals( 0, $empty['total_items'] );
	}

	public function test_user_without_caps_is_denied() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$result = $this->run_ability( 'create-company', [ 'name' => 'Nope Inc' ] );

		$this->assertWPError( $result );
		$this->assertFalse( ( new Company( 'nope-inc', 'slug' ) )->exists() );
	}

	public function test_sales_rep_cannot_edit_others_company() {
		$admin_company = $this->make_company();

		// Holds the base capabilities the abilities gate on, but not the *_others_companies ones,
		// so only the per-company (owner scoped) check can stop them.
		$rep_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$rep    = new WP_User( $rep_id );
		foreach ( [ 'view_companies', 'edit_companies' ] as $cap ) {
			$rep->add_cap( $cap );
		}
		wp_set_current_user( $rep_id );

		$this->assertTrue( current_user_can( 'edit_companies' ) );

		$update = $this->run_ability( 'update-company', [ 'id' => $admin_company->get_id(), 'name' => 'Hijacked' ] );
		$this->assertWPError( $update );
		$this->assertEquals( 'groundhogg_cannot_access_company', $update->get_error_code() );
		$this->assertNotEquals( 'Hijacked', ( new Company( $admin_company->get_id() ) )->get_name() );

		$get = $this->run_ability( 'get-company', [ 'id' => $admin_company->get_id() ] );
		$this->assertWPError( $get );
		$this->assertEquals( 'groundhogg_cannot_access_company', $get->get_error_code() );

		// ...but their own company is fine
		$own = $this->make_company();
		$own->update( [ 'owner_id' => $rep_id ] );
		$this->assertNotWPError( $this->run_ability( 'update-company', [ 'id' => $own->get_id(), 'name' => 'Renamed Own' ] ) );
	}

	public function test_invalid_owner_rejected() {
		$subscriber = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		$result = $this->run_ability( 'create-company', [ 'name' => 'Owned Co', 'owner' => $subscriber ] );

		$this->assertWPError( $result );
		$this->assertEquals( 'groundhogg_invalid_owner', $result->get_error_code() );
	}
}
