<?php
namespace GroundhoggCompanies\Admin\companies;

use Groundhogg\Contact;
use GroundhoggCompanies\Classes\Company;
use GroundhoggRSP\Classes\Subscription;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_date_time_format;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use function Groundhogg\html;

if ( !defined( 'ABSPATH' ) ) exit;


$id = absint( get_request_var( 'companies' ) );

if ( !$id ) {
    return;
}

$company = new Company( absint( $id ) );
?>
<div class="wrap">
    <?php
    Plugin::$instance->notices->print_notices();
    ?>
    <form method="post">
        <?php wp_nonce_field(); ?>
        <div class="company">
            <div class="company-inside">
                <div id="col-container">
                    <div id="col-left-company">
                        <div class="contact-details">
                            <h2><?php _e( 'Add Contact', 'groundhogg-companies' ); ?></h2>
                            <?php
                            echo html()->dropdown_contacts([
                                    'name' => 'contact_id'
                            ] );
                            ?>

                            <h2><?php _e( 'Contacts', 'groundhogg-companies' ); ?></h2>
                            <?php
                            $contacts = get_db( 'company_relationships' )->query( [ 'company_id' => absint( $company->get_id()  )] );
                            //                            $contacts = get_db( 'companies_relationships' )->query( [ 'company_id' => absint( get_request_var( 'companies' ) ) ] );
                            $contacts_rows = [];

                            if ( !empty( $contacts ) ) {
                                foreach ( $contacts as $id ) {

                                    $contact = new Contact( absint( $id->contact_id ) );

                                    $contacts_rows[] = [

                                        html()->e( 'a', [ 'href' => admin_page_url( 'gh_contacts', [
                                            'action' => 'edit',
                                            'contact' => $contact->get_id()
                                        ] ) ], $contact->get_full_name() ),
                                        $contact->get_full_name(),
                                        $contact->get_email(),
                                        $contact->get_phone_number(), // Date

                                    ];
                                }
                            }

                            html()->list_table( [ 'style' => [ 'max-width' => '700px' ] ], [
                                __( 'Name', 'groundhogg-companies' ),
                                __( 'Role', 'groundhogg-companies' ),
                                __( 'Email', 'groundhogg-companies' ),
                                __( 'phone', 'groundhogg-companies' ),

                            ], $contacts_rows );
                            ?>


                        </div>

                    </div>
                    <div id="col-right-company">
                        <div class="save">
                            <span class="spinner" style="float: left"></span>
                            <?php

                            submit_button( null, 'primary', 'submit', false, [ 'style' => 'float:right' ] );

                            ?>
                        </div>
                        <div class="company-details">

<!--                            <h3>--><?php //_e( 'Company Details' ); ?><!--</h3>-->

                            <!-- Photo -->
<!--                            <div class="company-picture">-->
<!--                                --><?php //echo html()->e( 'img', [
//                                    'class' => 'company-picture',
//                                    'title' => __( 'Profile Picture' ),
//                                    'width' => 150,
//                                    'height' => 150,
////                                            'src' => $company->get_profile_picture()
//                                ] ); ?>
<!--                            </div>-->
                            <h3><?php _e( 'Company' ); ?></h3>
                                <!-- FIRST -->
                                <?php

                                echo html()->e( 'div', [ 'class' => 'details' ], html()->input( [
                                    'name' => 'company_name',
                                    'title' => __( 'Company Name' ),
                                    'value' => $company->get_name(),
                                    'class' => 'auto-copy regular-text'
                                ] ) );
                                ?>
                            <h3><?php _e( 'Website' ); ?></h3>
                            <?php

                                echo html()->e( 'div', [ 'class' => 'details' ], html()->input( [
                                    'name' => 'company_domain',
                                    'title' => __( 'Website' ),
                                    'value' => $company->get_domain(),
                                    'class' => 'auto-copy regular-text'
                                ] ) );

                                ?>


                            <h3><?php _e( 'Description' ); ?></h3>
                            <?php

                            echo html()->e( 'div', [ 'class' => 'details' ], html()->textarea( [
                                'name' => 'company_description',
                                'value' => $company->get_description(),

                                'class' => 'auto-copy regular-text',
                                'rows' => 6,
                            ] ) );
                            ?>

                            <h3><?php _e( 'Address' ); ?></h3>
                            <?php

                            echo html()->e( 'div', [ 'class' => 'details' ], html()->textarea( [
                                'name' => 'address',
                                'value' => $company->get_address(),

                                'class' => 'auto-copy regular-text',
                                'rows' => 6,
                            ] ) );

                            ?>
                            <h3><?php _e( 'Notes' ); ?></h3>
                            <?php

                            echo html()->e( 'div', [ 'class' => 'details' ], html()->textarea( [
                                'name' => 'notes',
                                'value' => $company->get_notes(),

                                'class' => 'auto-copy regular-text'
                            ] ) );

                            ?>
                            <!-- Edit -->

                            <?php do_action( 'groundhogg/companies/admin/edit/after_details', $company ); ?>

                        </div>
                    </div>
                </div>
                <div class="wp-clearfix"></div>
            </div>
        </div>
    </form>
</div>
<script>
    (function ($) {
        $('form').on('submit', function () {
            $('.spinner').css('visibility', 'visible')
        });
    })(jQuery);
</script>


<!---->
<!---->
<!---->
<!--<form name="edittag" id="edittag" method="post" action="" class="validate">-->
<!--    --><?php //wp_nonce_field();
?>
<!--    <table class="form-table">-->
<!--        <tbody>-->
<!--        <tr class="form-field form-required term-name-wrap">-->
<!--            <th scope="row"><label for="name">--><?php //_e( 'Name' )
?><!--</label></th>-->
<!--            <td>-->
<!--                --><?php
//                echo html()->input([
//                    'name' => 'company_name',
//                    'value' => $company->get_name()
//                ]);
//
?>
<!--                <p>--><?php //_e( 'Enter a Name of company.', 'groundhogg' );
?><!--</p>-->
<!--            </td>-->
<!--        </tr>-->
<!--        <tr class="form-field term-description-wrap">-->
<!--            <th scope="row"><label for="description">--><?php //_e( 'Description' );
?><!--</label></th>-->
<!--            <td>-->
<!--                --><?php
//
//                echo html()->textarea( [
//                    'name' => 'company_description',
//                    'value' => $company->get_description()
//                ] );
//
//
?>
<!--                <p>--><?php //_e( 'Describe something about a company in details which makes it unique.', 'groundhogg' );
?><!--</p>-->
<!--            </td>-->
<!--        </tr>-->
<!--        </tbody>-->
<!--        --><?php //do_action( 'groundhogg/admin/companies/edit/form', $id );
?>
<!--    </table>-->
<!--    <div class="edit-tag-actions">-->
<!--        --><?php //submit_button( __( 'Update' ), 'primary', 'update', false );
?>
<!--        <span id="delete-link"><a class="delete" href="--><?php //echo wp_nonce_url( admin_url( 'admin.php?page=gh_companies&action=delete&companies='. $id ), 'delete'  )
?><!--">--><?php //_e( 'Delete' );
?><!--</a></span>-->
<!--    </div>-->
<!--</form>-->
