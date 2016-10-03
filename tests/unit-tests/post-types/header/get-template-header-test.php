<?php

namespace _s\Tests\Unit_Tests\Post_Types\Header {

	use _s\Tests\Unit_Tests\Abstract_Test;

	class Get_Template_Header_Test extends Abstract_Test {
		public function testItLooksUpTheHeaderForTheCurrentTemplate() {
			$self     = $this;
			$return   = uniqid();
			$template = uniqid();

			static::$functions->shouldReceive( 'get_posts' )->andReturnUsing(
				function ( $query ) use ( $return, $self, $template ) {
					$self->assertEquals( '_page_template', $query['meta_key'] );
					$self->assertEquals( $template, $query['meta_value'] );

					return [ $return ];
				}
			);

			$this->assertEquals( $return, \_s\_s_get_template_header( $template ) );
		}

		public function testItRegisteresThePostType() {
			$self = $this;

			static::$functions->shouldReceive( 'register_post_type' )->andReturnUsing(
				function ( $post_type ) use ( $self ) {
					$self->assertEquals( 'theme_header', $post_type );
				}
			);

			\_s\_s_theme_header();
		}

		public function testItReturnsNullWhenNoHeaderIsFound() {
			static::$functions->shouldReceive( 'get_posts' )->andReturn( null );

			$this->assertNull( \_s\_s_get_template_header() );
		}

		public function testItShouldCacheResults() {

		}

		/**
		 * When no slug is given then it looks up the current one.
		 */
		public function testItUsesTheCurrentTemplateSlugIfNoneGiven() {
			$result = uniqid();

			static::$functions->shouldReceive( 'get_page_template_slug' )
			                  ->andReturn( $result );

			// not everything of the tested function is needed => early exit with cache
			static::$functions->byDefault()
			                  ->shouldReceive( 'get_site_transient' )
			                  ->with( 'theme_header_' . md5( $result ) )
			                  ->andReturn( $result );

			$this->assertEquals( $result, \_s\_s_get_template_header() );
		}

		protected function setUp() {
			parent::setUp();

			// no cache for all tests
			static::$functions->shouldReceive( 'get_site_transient' )->andReturn( null );
		}
	}
}