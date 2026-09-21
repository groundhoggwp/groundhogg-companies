<?php
/**
 * PHPUnit bootstrap file for Groundhogg - Companies
 *
 * Loads Groundhogg core first, then this add-on, and reuses core's test framework
 * (GH_UnitTestCase and the factories) instead of shipping a copy.
 *
 * Environment variables:
 *  - WP_TESTS_DIR:        the WordPress test library (defaults to the system temp dir)
 *  - GROUNDHOGG_CORE_DIR: the Groundhogg core plugin (defaults to a sibling `groundhogg` folder)
 *
 * @package GroundhoggCompanies
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you set WP_TESTS_DIR?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

$_core_dir = getenv( 'GROUNDHOGG_CORE_DIR' );

if ( ! $_core_dir ) {
	$_core_dir = dirname( __DIR__, 3 ) . '/groundhogg';
}

$_core_dir = rtrim( $_core_dir, '/\\' );

if ( ! file_exists( $_core_dir . '/groundhogg.php' ) || ! is_dir( $_core_dir . '/tests/phpunit/framework' ) ) {
	echo "Could not find Groundhogg core at $_core_dir, set GROUNDHOGG_CORE_DIR." . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load Groundhogg core, then the add-on.
 * Core has to be first so that `groundhogg/loaded` has already fired when the add-on checks for it.
 */
$_manually_load_groundhogg_and_addon = function () use ( $_core_dir ) {
	require $_core_dir . '/groundhogg.php';
	require dirname( __DIR__, 2 ) . '/groundhogg-companies.php';
};

/**
 * Manually install core's DBs and roles, then the add-on's tables and capabilities.
 */
$_manually_install_groundhogg = function () {
	\Groundhogg\Plugin::instance()->installer->activation_hook( false );
	\GroundhoggCompanies\Plugin::instance()->installer->activation_hook( false );
};

tests_add_filter( 'muplugins_loaded', $_manually_load_groundhogg_and_addon );
tests_add_filter( 'init', $_manually_install_groundhogg );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

/**
 * Load core's framework additions, same order as core's own bootstrap
 */
$_load_groundhogg_framework = function () use ( $_core_dir ) {

	$files = [
		'class-gh-unittest-factory-for-thing.php',
		'class-gh-unittest-factory-for-contact.php',
		'class-gh-unittest-factory-for-funnel.php',
		'class-gh-unittest-factory-for-step.php',
		'class-gh-unittest-factory-for-event.php',
		'class-gh-unittest-factory-for-event-queue.php',
		'class-gh-unittest-factory-for-activity.php',
		'class-gh-unittest-factory.php',
		'class-gh-unittest-id-generator.php',
		'class-gh-unittest-time-generator.php',
		'class-gh-unittestcase.php',
	];

	foreach ( $files as $file ) {
		require $_core_dir . '/tests/phpunit/framework/' . $file;
	}
};

$_load_groundhogg_framework();

define( 'DOING_GROUNDHOGG_TESTS', true );
