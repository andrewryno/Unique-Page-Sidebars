<?php

class UniquePageSidebarsTest extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		$this->plugin = $GLOBALS['unique_page_sidebars'];
	}

	/**
	 * Make sure that our ups_filter is being loaded.
	 */
	public function testFilters() {
		$this->assertTrue( has_filter( 'ups_sidebar' ) );
	}

}