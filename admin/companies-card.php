<?php

use Groundhogg\Contact;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\filter_object_exists;
use function Groundhogg\html;

/**
 * @var $contact   Contact
 * @var $companies Company[]
 */

$companies = $contact->get_related_objects( 'company', false );

$companies = filter_object_exists( $companies );

foreach ( $companies as $company ) {

	if ( ! $company->exists() ) {
		continue;
	}

	$phone = $company->get_meta( 'phone' );

	?>
    <div class="gh-panel company outlined closed">
        <div class="gh-panel-header">
            <h2><b><a href="<?php echo $company->admin_link() ?>"
                      target="_blank"><?php esc_html_e( $company->get_name() ); ?></a></b></h2>
            <button type="button" class="toggle-indicator"></button>
        </div>
        <div class="inside">
            <ul class="info-list">
                <li class="<?php echo $company->get_meta( 'industry' ) ? '' : 'hidden' ?>"><span
                            class="dashicons dashicons-building"></span><?php esc_html_e( $company->get_meta( 'industry' ) ?: '-' ) ?>
                </li>
                <li class="<?php echo $company->get_domain() ? '' : 'hidden' ?>"><span
                            class="dashicons dashicons-welcome-view-site"></span><a
                            href="<?php echo esc_url( $company->get_domain() ?: '' ) ?>"
                            target="_blank"><?php esc_html_e( parse_url( $company->get_domain(), PHP_URL_HOST ) ); ?></a>
                </li>
                <li class="<?php echo $company->get_address() ? '' : 'hidden' ?>"><span
                            class="dashicons dashicons-location"></span><?php echo html()->e( 'a', [
						'href'   => 'http://maps.google.com/?q=' . $company->get_searchable_address(),
						'target' => '_blank'
					], $company->get_address(), false ); ?></li>
                <li class="<?php echo $phone ? '' : 'hidden' ?>"><span
                            class="dashicons dashicons-phone"></span><?php echo $phone ? html()->e( 'a', [
						'href' => 'tel:' . $phone
					], $phone ) : '-'; ?></li>
            </ul>
        </div>
    </div>
	<?php

}
?>
    <script>
      ( ($) => {
        $('.gh-panel.company .toggle-indicator').on('click', (e) => {
          $(e.target).closest('.company').toggleClass('closed')
        })
      } )(jQuery)
    </script>
<?php

if ( empty( $companies ) ) {
	?>
    <p><?php _e( 'Not associated to a company.', 'groundhogg-companies' ) ?></p>
	<?php
}
