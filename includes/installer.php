<?php

namespace GroundhoggCompanies;

use function Groundhogg\words_to_key;

class Installer extends \Groundhogg\Installer {

	protected function activate() {
		\Groundhogg\Plugin::$instance->dbs->install_dbs();
		Plugin::$instance->roles->install_roles_and_caps();

	}

	protected function deactivate() {
		// TODO: Implement deactivate() method.
	}

	/**
	 * The path to the main plugin file
	 *
	 * @return string
	 */
	function get_plugin_file() {
		return GROUNDHOGG_COMPANIES__FILE__;
	}

	/**
	 * Get the plugin version
	 *
	 * @return string
	 */
	function get_plugin_version() {
		return GROUNDHOGG_COMPANIES_VERSION;
	}

	/**
	 * A unique name for the updater to avoid conflicts
	 *
	 * @return string
	 */
	protected function get_installer_name() {
		return words_to_key( GROUNDHOGG_COMPANIES_NAME );
	}
}