<?php

namespace GroundhoggCompanies\Admin\companies;

use Groundhogg\Contact;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use function Groundhogg\html;
use function GroundhoggCompanies\get_clean_domain;

if ( !defined( 'ABSPATH' ) ) exit;

$id = absint( get_request_var( 'company' ) );

if ( !$id ) {
    return;
}
$company = new Company( absint( $id ) );


?>

<script>
    (function ($) {
        $('form').on('submit', function () {
            $('.spinner').css('visibility', 'visible')
        });
    })(jQuery);
</script>
<div id="col-container" class="wp-clearfix">
    <?php
    Plugin::$instance->notices->print_notices();
    ?>

    <div id="col-left">
        <div class="col-wrap">
            <div class="form-wrap">
                <div id="company">
                    <form method="post" enctype="multipart/form-data" name="company_details">
                        <div class="save">
                            <span class="spinner" style="float: left"></span>
                            <?php
                            submit_button( null, 'primary', 'submit', false, [ 'style' => 'float:right' ] );
                            ?>
                            <div class="wp-clearfix"></div>
                        </div>
                        <div class="company-details">

                            <?php wp_nonce_field();
                            echo html()->input( [
                                'type' => 'hidden',
                                'name' => 'operation',
                                'value' => 'update_company'
                            ] );
                            ?>
                            <h3><?php _e( 'Company Details' ); ?></h3>

                            <!-- Photo -->
                            <?php
                            if ( !empty( $company->get_picture() ) ) {
                                ?>
                                <div class="company-picture-wrap">
                                    <?php

                                    $file = array_pop( $company->get_picture() );

                                    echo html()->e( 'img', [
                                        'class' => 'company-picture ',
                                        'title' => __( 'Company Picture' ),
                                        'src' => $file[ 'file_url' ],
                                    ] );

                                    ?>
                                </div>
                                <?php
                            }
                            ?>

                            <div style="width: 100%">
                                <input class="gh-file-uploader" type="file" name="pictures[]"
                                       accept="image/x-png,image/gif,image/jpeg" multiple>
                            </div>


                            <h3><?php _e( 'Company' ); ?></h3>

                            <?php

                            echo html()->e( 'div', [ 'class' => 'details' ], html()->input( [
                                'name' => 'company_name',
                                'title' => __( 'Company Name' ),
                                'value' => $company->get_name(),
                                'class' => 'auto-copy regular-text'
                            ] ) );

                            ?>

                            <h3>
                                <?php _e( 'Website' ); ?>

                                <a title="<?php printf( esc_attr__( 'Visit %s', 'groundhogg' ), $company->get_domain() ) ?>"
                                   style="text-decoration: none" target="_blank"
                                   href="<?php echo esc_url( $company->get_domain() ); ?>">
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </h3>
                            <?php

                            echo html()->e( 'div', [ 'class' => 'details' ], html()->input( [
                                'type' => 'url',
                                'name' => 'company_domain',
                                'title' => __( 'Website' ),
                                'value' => $company->get_domain(),
                                'class' => 'auto-copy regular-text '
                            ] ) );

                            ?>


                            <h3><?php _e( 'Description' );
                                ?></h3>
                            <?php

                            echo html()->e( 'div', [ 'class' => 'details' ], html()->textarea( [
                                'name' => 'company_description',
                                'value' => $company->get_description(),
                                'class' => 'auto-copy regular-text',
                                'rows' => 6,
                            ] ) );

                            ?>

                            <h3>
                                <?php _e( 'Address' ); ?>

                                <a title="<?php printf( esc_attr__( 'View in map', 'groundhogg' )) ?>"
                                   style="text-decoration: none" target="_blank"
                                   href="<?php echo sprintf(  'http://maps.google.com/?q=%s', urlencode( $company->get_searchable_address() ) ); ?>">
                                    <span class="dashicons dashicons-location-alt"></span>
                                </a>
                            </h3>
                            <?php

                            echo html()->e( 'div', [ 'class' => 'details' ], html()->textarea( [
                                'name' => 'address',
                                'value' => $company->get_address(),
                                'class' => 'auto-copy regular-text ',
                                'rows' => 6,
                            ] ) );

                            ?>
                            <h3><?php _e( 'Notes' );
                                ?></h3>
                            <?php
                            echo html()->e( 'div', [ 'class' => 'details' ], html()->textarea( [
                                'name' => 'add_note',
                                'class' => 'auto-copy regular-text ',
                                'rows' => 3,
                            ] ) );

                            submit_button( _x( 'Add Note', 'action', 'groundhogg' ), 'secondary', 'add_new_note' );

                            $args = array(
                                'id' => 'notes',
                                'name' => 'notes',
                                'value' => $company->get_notes(),
                                'readonly' => true,
                                'class' => 'auto-copy regular-text ',
                                'rows' => 10,
                            );
                            echo Plugin::$instance->utils->html->textarea( $args );
                            ?>

                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id="col-right">
        <div class="col-wrap">
            <div id="company">

                <h3><?php _e( 'Contacts', 'groundhogg-companies' ); ?></h3>
                <div class="contact-details">
                    <form method="post">
                        <?php
                        if ( !class_exists( 'Contacts_Table' ) ) {
                            include dirname( __FILE__ ) . '/contacts-table.php';
                        }

                        $contacts_table = new Contacts_Table();

                        $contacts_table->prepare_items();
                        $contacts_table->display();
                        ?>
                    </form>
                </div>
                <h3><?php _e( 'Files', 'groundhogg-companies' ); ?></h3>

                <div class="company-files">

                    <form method="post" enctype="multipart/form-data">
                        <div style="">
                            <style>
                                .wp-admin .gh-file-uploader {
                                    width: 100%;
                                    margin: auto;
                                    padding: 30px !important;
                                    box-sizing: border-box;
                                    background: #F9F9F9;
                                    border: 2px dashed #e5e5e5;
                                    text-align: center;
                                    margin-top: 10px;
                                }
                            </style>
                            <?php
                            wp_nonce_field();
                            echo html()->input( [
                                'type' => 'hidden',
                                'name' => 'operation',
                                'value' => 'add_files'
                            ] );

                            $files = $company->get_files();

                            $rows = [];

                            foreach ( $files as $key => $item ) {

                                $info = pathinfo( $item[ 'file_path' ] );

                                $rows[] = [
                                    sprintf( "<a href='%s' target='_blank'>%s</a>", esc_url( $item[ 'file_url' ] ), esc_html( $info[ 'basename' ] ) ),
                                    esc_html( size_format( filesize( $item[ 'file_path' ] ) ) ),
                                    esc_html( $info[ 'extension' ] ),
                                    html()->e( 'span', [ 'class' => 'row-actions' ], [
                                        html()->e( 'span', [ 'class' => 'delete' ],
                                            html()->e( 'a', [
                                                'class' => 'delete',
                                                'href' => admin_page_url( 'gh_companies', [
                                                    'action' => 'remove_file',
                                                    'file' => $info[ 'basename' ],
                                                    'company' => $company->get_id(),
                                                    '_wpnonce' => wp_create_nonce( 'remove_file' )
                                                ] ) ], __( 'Delete' ) ) ),
                                    ] )
                                ];

                            }

                            html()->list_table( [ 'class' => 'files', 'id' => 'files' ], [
                                _x( 'Name', 'contact_record', 'groundhogg' ),
                                _x( 'Size', 'contact_record', 'groundhogg' ),
                                _x( 'Type', 'contact_record', 'groundhogg' ),
                                _x( 'Actions', 'contact_record', 'groundhogg' ),
                            ], $rows );

                            ?>
                            <div>
                                <input class="gh-file-uploader" type="file" name="files[]" multiple>
                            </div>
                            <?php
                            submit_button( 'Upload File', 'secondary', 'submit' );
                            ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

