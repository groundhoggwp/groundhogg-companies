# Groundhogg Companies tests

WP-integration PHPUnit tests. They load Groundhogg core first, then this add-on, and reuse core's
test framework (`GH_UnitTestCase` and the factories) straight from the core repo.

## Requirements

- The WordPress test library (`WP_TESTS_DIR`), with a `wp-tests-config.php` pointing at a **throwaway** database
  (the run drops its tables - never use a real site's DB).
- PHPUnit 9.6 and `yoast/phpunit-polyfills` (`WP_TESTS_PHPUNIT_POLYFILLS_PATH`).
- Groundhogg core at `../groundhogg`, or set `GROUNDHOGG_CORE_DIR`.

## Run

```bash
export WP_TESTS_DIR=/path/to/wordpress-tests-lib
phpunit            # uses phpunit.xml.dist
phpunit --filter Company_Abilities_Tests
```

`unit-tests/class-company-abilities-tests.php` runs each `groundhogg-companies/*` ability through
`wp_get_ability()->execute()`, so input-schema validation, the permission callback and the callback all run.

Groundhogg's updaters print `WordPress database error` lines on a fresh DB; that is expected noise.
