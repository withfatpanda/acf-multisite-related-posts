<?php
/**
 * Class SampleTest
 *
 * @package Acf_Multisite_Related_Posts
 */

/**
 * Sample test case.
 */
class SampleTest extends WP_UnitTestCase {

	/**
	 * A single example test.
	 */
	function test_sample() {
		include_once(__DIR__.'/../fields/base-acf-multisite-related-posts.php');
    include_once(__DIR__.'/../fields/acf-multisite-related-posts-v5.php');
	}
}
