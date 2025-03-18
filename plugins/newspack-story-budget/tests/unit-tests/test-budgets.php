<?php
/**
 * Test Budgets
 *
 * @package Newspack_Story_Budget
 */

//phpcs:disable Squiz.Commenting.VariableComment.Missing

namespace Newspack_Story_Budget;

/**
 * Test Budgets Class.
 */
class Test_Budgets extends \WP_UnitTestCase {

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
	 * Test get budgets.
	 */
	public function test_get_budgets() {
		$budgets = Budgets::get_budgets();
		$this->assertCount( 2, $budgets );
		$this->assertInstanceOf( Budget::class, $budgets[0] );
		$this->assertInstanceOf( Budget::class, $budgets[1] );
	}

	/**
	 * Test get budgets excludes archived.
	 */
	public function test_get_budgets_excludes_archived() {
		$budget = new Budget( self::$budgets[0] );
		$budget->archive();

		$budgets = Budgets::get_budgets();
		$this->assertCount( 1, $budgets );

		$budget->unarchive();
	}

	/**
	 * Test get budgets include archived.
	 */
	public function test_get_budgets_include_archived() {
		$budget = new Budget( self::$budgets[0] );
		$budget->archive();

		$budgets = Budgets::get_budgets( true );
		$this->assertCount( 2, $budgets );

		$budget->unarchive();
	}

	/**
	 * Test get stories from one budget.
	 */
	public function test_get_stories() {
		$stories = Budgets::get_stories();
		$this->assertCount( 100, $stories );
		$this->assertInstanceOf( 'WP_Query', Budgets::$stories_query );
		$this->assertContainsOnlyInstancesOf( 'Newspack_Story_Budget\Story', $stories );
	}

	/**
	 * Test get stories args.
	 */
	public function test_get_stories_args() {
		// Limit.
		$result = Budgets::get_stories( [ 'posts_per_page' => 10 ] );
		$this->assertCount( 10, $result );

		// Fields.
		$result = Budgets::get_stories( [ 'fields' => 'ids' ] );
		$this->assertContainsOnly( 'int', $result );
	}

	/**
	 * Test get stories tax query.
	 */
	public function test_get_stories_tax_query() {
		$stories = Budgets::get_stories();

		$post = $this->factory->post->create();
		$story_1 = $stories[0]->id;
		$story_2 = $stories[1]->id;
		$tag_1 = $this->factory->tag->create();
		$tag_2 = $this->factory->tag->create();
		wp_set_post_terms( $post, [ $tag_1, $tag_2 ], 'post_tag' );
		wp_set_post_terms( $story_1, [ $tag_1, $tag_2 ], 'post_tag' );
		wp_set_post_terms( $story_2, [ $tag_1 ], 'post_tag' );


		$result = Budgets::get_stories(
			[
				'tax_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => 'post_tag',
						'terms'    => $tag_1,
					],
				],
			]
		);
		$this->assertCount( 2, $result );

		$this->setExpectedIncorrectUsage( 'get_stories' );
		$result = Budgets::get_stories(
			[
				'tax_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => 'post_tag',
						'terms'    => $tag_1,
					],
					[
						'taxonomy' => 'post_tag',
						'terms'    => $tag_2,
					],
					'relation' => 'OR',
				],
			]
		);
		$this->assertCount( 1, $result );
		$this->assertEquals( $story_1, $result[0]->id );
	}
}
