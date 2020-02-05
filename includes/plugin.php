<?php

namespace GroundhoggCompanies;

use Groundhogg\Admin\Admin_Menu;
use Groundhogg\DB\Manager;
use Groundhogg\Extension;
use GroundhoggCompanies\Admin\Companies\Companies_Page;
use GroundhoggCompanies\Bulk_Jobs\Sync_Companies;
use GroundhoggCompanies\DB\Companies;
use GroundhoggCompanies\DB\Company_Meta;
use GroundhoggCompanies\DB\Company_Relationships;

class Plugin extends Extension{


    /**
     * Override the parent instance.
     *
     * @var Plugin
     */
    public static $instance;

    /**
     * Include any files.
     *
     * @return void
     */
    public function includes()
    {
        require  GROUNDHOGG_COMPANIES_PATH . '/includes/functions.php';
    }

    /**
     * Init any components that need to be added.
     *
     * @return void
     */
    public function init_components()
    {
        $this->installer = new Installer();
        $this->updater = new Updater();
        $this->roles = new Roles();

    }

    /**
     * @param Manager $db_manager
     */
    public function register_dbs( $db_manager )
    {
        $db_manager->companies = new Companies();
        $db_manager->company_meta = new Company_Meta();
        $db_manager->company_relationships = new Company_Relationships();
    }

    /**
     * Code to register admin menu in the list.
     * @param Admin_Menu $admin_menu
     */
    public function register_admin_pages( $admin_menu )
    {
        $admin_menu->companies = new Companies_Page();
    }


    public function register_bulk_jobs( $manager )
    {
        $manager->sync_companies  = new Sync_Companies();
    }

    /**
     * Get the ID number for the download in EDD Store
     *
     * @return int
     */
    public function get_download_id()
    {
        return  37360;
    }

    /**
     * Get the version #
     *
     * @return mixed
     */
    public function get_version()
    {
        return GROUNDHOGG_COMPANIES_VERSION;
    }

    public function register_admin_styles()
    {
        wp_register_style( 'groundhogg-companies-admin', GROUNDHOGG_COMPANIES_ASSETS_URL . '/css/companies.css', [], GROUNDHOGG_COMPANIES_VERSION );
    }


    public function register_admin_scripts( $is_minified, $dot_min )
    {
        wp_register_script( 'groundhogg-edit-companies-admin', GROUNDHOGG_COMPANIES_ASSETS_URL . '/css/companies.css', [], GROUNDHOGG_COMPANIES_VERSION );

    }

    /**
     * @return string
     */
    public function get_plugin_file()
    {
        return GROUNDHOGG_COMPANIES__FILE__;
    }

    /**
     * Register autoloader.
     *
     * Groundhogg autoloader loads all the classes needed to run the plugin.
     *
     * @since 1.6.0
     * @access private
     */
    protected function register_autoloader()
    {
        require GROUNDHOGG_COMPANIES_PATH . 'includes/autoloader.php';
        Autoloader::run();
    }
}

Plugin::instance();