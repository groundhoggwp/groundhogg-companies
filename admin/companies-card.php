<?php

use Groundhogg\Contact;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\contact_filters_link;
use function Groundhogg\filter_object_exists;
use function Groundhogg\html;

/**
 * @var $contact   Contact
 * @var $companies Company[]
 */

$companies = $contact->get_related_objects( 'company', false );

$companies = filter_object_exists( $companies );


function company_detail( $icon, $content ) {

	if ( empty( $content ) ) {
		return;
	}

	echo html()->e( 'div', [
		'class' => 'display-flex gap-10'
	], [
		\Groundhogg\dashicon( $icon ),
		html()->e( 'span', [], $content )
	] );
}

foreach ( $companies as $company ) {

	if ( ! $company->exists() ) {
		continue;
	}

	$phone = $company->get_meta( 'phone' );

	?>
    <div class="gh-panel company outlined closed">
        <div class="gh-panel-header">
            <h2>
				<?php echo html()->e( 'img', [
					'src'    => "https://www.google.com/s2/favicons?domain={$company->get_hostname()}&sz=40",
					'width'  => 20,
					'height' => 20,
					'style'  => [ 'vertical-align' => 'bottom' ]
				] ); ?>
                &nbsp;
                <b><a href="<?php echo $company->admin_link() ?>"
                      target="_blank"><?php esc_html_e( $company->get_name() ); ?></a></b></h2>
            <button type="button" class="toggle-indicator"></button>
        </div>
        <div class="inside display-flex column gap-5">
			<?php

			$contacts = $company->countRelatedContacts();

			company_detail( 'building', esc_html( $company->get_meta( 'industry' ) ) );

			$company->get_domain() && company_detail( 'admin-site', html()->e( 'a', [
				'href'   => $company->get_domain(),
				'target' => '_blank'
			], $company->get_hostname() ) );

			$company->get_address() && company_detail( 'location', html()->e( 'a', [
				'href'   => 'http://maps.google.com/?q=' . $company->get_searchable_address(),
				'target' => '_blank'
			], $company->get_address() ) );

			$phone && company_detail( 'phone', html()->e( 'a', [
				'href' => 'tel:' . $phone,
			], $phone ) );

			company_detail( 'groups', contact_filters_link( sprintf( _n( '%s contact', '%s contacts', $contacts, 'groundhogg' ), number_format_i18n( $contacts ) ), [
				[
					[
						'type'        => 'secondary_related',
						'object_type' => 'company',
						'object_id'   => $company->ID
					]
				]
			], $contacts ) );

			echo html()->e( 'a', [
                'class' => 'danger-confirm gh-text danger',
                'data-alert' => __( 'Are you sure you want to remove this association?' ),
				'href' => \Groundhogg\action_url( 'remove_relationship', [
					'primary_object_type'   => 'company',
					'primary_object_id'     => $company->ID,
					'secondary_object_type' => 'contact',
					'secondary_object_id'   => $contact->ID
				] ),
			], __( 'Remove' ) )
			?>
        </div>
    </div>
	<?php
}

if ( empty( $companies ) ) {
	?>
    <p><?php _e( 'Not associated to a company.', 'groundhogg-companies' ) ?></p>
	<?php
}
