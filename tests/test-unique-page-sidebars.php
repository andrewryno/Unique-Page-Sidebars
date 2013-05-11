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

	public function testAddingNewSidebars() {
		$expected = array('name' => 'First Sidebar', 'description' => '', 'before_title' => '', 'after_title' => '', 'before_widget' => '', 'after_widget' => '', 'children' => 'off', 'locations' => array());
		$add_sidebar = $this->plugin->add_sidebar( 'First Sidebar' );
		$this->assertTrue(isset($add_sidebar['ups-sidebar-1']), 'First sidebar should be saved in array.');
		$this->assertEquals($expected, $add_sidebar['ups-sidebar-1'], 'Sidebar should be saved with default options.');
		$this->assertEquals(1, get_option('ups_sidebars_last_id'), 'Last sidebar ID should be saved as option.');

		$expected = array('name' => 'Second Sidebar', 'description' => '', 'before_title' => '', 'after_title' => '', 'before_widget' => '', 'after_widget' => '', 'children' => 'off', 'locations' => array());
		$add_sidebar = $this->plugin->add_sidebar( 'Second Sidebar' );
		$this->assertTrue(isset($add_sidebar['ups-sidebar-2']), 'Second sidebar should be saved in array.');
		$this->assertEquals($expected, $add_sidebar['ups-sidebar-2'], 'Sidebar should be saved with default options.');
		$this->assertEquals(2, get_option('ups_sidebars_last_id'), 'Last sidebar ID should be saved as option.');
	}

}