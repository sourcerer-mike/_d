<?php

namespace _s {

	use _s\Tests\Unit_Tests\Abstract_Test;
	use Mockery;

	function _s_mock( $name, $args = array() ) {
		$name = preg_replace( '/^_s\\\\/', '', $name );

		if ( false == Abstract_Test::$functions instanceof \Mockery\MockInterface ) {
			Abstract_Test::$functions = Mockery::mock();
		}

		try {
			return call_user_func_array( [ Abstract_Test::$functions, $name ], $args );
		} catch ( \BadMethodCallException $e ) {
			return current( $args );
		}
	}

	// Mock all called function
	function __() {
		return _s_mock( __FUNCTION__, func_get_args() );
	}

	function add_action( $action, $method ) {
		return _s_mock( __FUNCTION__, func_get_args() );
	}

	function add_filter() {
		return _s_mock( __FUNCTION__, func_get_args() );
	}

	function get_page_template_slug() {
		return _s_mock( __FUNCTION__, func_get_args() );
	}

	function get_posts() {
		return _s_mock( __FUNCTION__, func_get_args() );
	}

	function get_site_transient() {
		return _s_mock( __FUNCTION__, func_get_args() );
	}

	function locate_template() {
		return _s_mock( __FUNCTION__, func_get_args() );
	}

	function register_post_type() {
		return _s_mock( __FUNCTION__, func_get_args() );
	}

	function set_site_transient() {
		return _s_mock( __FUNCTION__, func_get_args() );
	}
}

namespace _s\Tests\Unit_Tests {

	use Mockery;

	class Abstract_Test extends \PHPUnit_Framework_TestCase {
		/**
		 * @var \Mockery\MockInterface
		 */
		public static $functions;

		protected function setUp() {
			parent::setUp();
			self::$functions = Mockery::mock();
		}

		protected function tearDown() {
			Mockery::close();
			parent::tearDown();
		}
	}
}