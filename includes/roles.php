<?php
namespace GroundhoggCompanies;

class Roles extends \Groundhogg\Roles
{

    /**
     * Returns an array  of role => [
     *  'role' => '',
     *  'name' => '',
     *  'caps' => []
     * ]
     *
     * In this case caps should just be the meta cap map for other WP related stuff.
     *
     * @return array[]
     */
    public function get_roles()
    {
        return [];
    }


    public function get_administrator_caps()
    {
        return [
            'add_companies',
            'delete_companies',
            'edit_companies',
            'manage_companies',
            'view_companies'
        ];
    }

    public function get_sales_manager_caps()
    {
        return [
            'view_companies'
        ];
    }

    public function get_marketer_caps()
    {
        return [
            'add_companies',
            'delete_companies',
            'edit_companies',
            'manage_companies',
            'view_companies'
        ];
    }

    /**
     * Return a cap to check against the admin to ensure caps are also installed.
     *
     * @return mixed
     */
    protected function get_admin_cap_check()
    {
        return 'edit_companies';
    }
}