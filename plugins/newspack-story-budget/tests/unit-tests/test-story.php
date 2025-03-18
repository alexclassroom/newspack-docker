<?php
/**
 * Test Story
 *
 * @package Newspack_Story_Budget
 */

//phpcs:disable Squiz.Commenting.VariableComment.Missing

namespace Newspack_Story_Budget;

/**
 * Test Story Class.
 */
class Test_Story extends \WP_UnitTestCase {

	protected static $budgets = [];
	protected static $stories = [];

	/**
	 * WP setup before class.
	 */
	public static function wpSetUpBeforeClass() {
		self::$budgets = self::factory()->term->create_many(
			2,
			[
				'taxonomy' => Budgets::TAXONOMY,
			]
		);
		self::$stories = self::factory()->post->create_many(
			100,
			[
				'post_type' => 'post',
			]
		);
		foreach ( self::$stories as $i => $post_id ) {
			wp_set_post_terms( $post_id, [ self::$budgets[ $i % 2 ] ], Budgets::TAXONOMY );
		}
	}

	/**
	 * Test Story.
	 */
	public function test_new_story() {
		$story_id = self::$stories[0];
		$story = new Story( $story_id );
		$this->assertTrue( $story->is_valid() );
		$this->assertEquals( $story_id, $story->id );
	}

	/**
	 * Test non story.
	 */
	public function test_new_non_story() {
		$story = new Story( 0 );
		$this->assertFalse( $story->is_valid() );
	}

	/**
	 * Test to array.
	 */
	public function test_to_array() {
		$story_id = self::$stories[0];
		$story = new Story( $story_id );
		$arr = $story->to_array();
		$this->assertIsArray( $arr );
		$this->assertArrayHasKey( 'id', $arr );
		$this->assertArrayHasKey( 'title', $arr );
	}
}
