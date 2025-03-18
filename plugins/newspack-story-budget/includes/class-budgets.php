<?php
/**
 * Newspack Story Budget Budgets
 *
 * @package Newspack_Story_Budget
 */

namespace Newspack_Story_Budget;

/**
 * Budgets Class.
 */
class Budgets {

	/**
	 * Taxonomy name.
	 */
	const TAXONOMY = 'newspack_story_budget';

	/**
	 * Stories query object.
	 *
	 * @var \WP_Query
	 */
	public static $stories_query;

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_taxonomy' ] );
	}

	/**
	 * Register taxonomy.
	 */
	public static function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			self::get_post_types(),
			[
				'labels' => [
					'name'          => __( 'Story Budgets', 'newspack-story-budget' ),
					'singular_name' => __( 'Story Budget', 'newspack-story-budget' ),
					'edit_item'     => __( 'Edit Story Budget', 'newspack-story-budget' ),
					'add_new_item'  => __( 'Add New Story Budget', 'newspack-story-budget' ),
				],
				'public' => false,
			]
		);
	}

	/**
	 * Get post types allowed to be stories in a budget.
	 *
	 * @return string[]
	 */
	public static function get_post_types() {
		/**
		 * Filters the post types allowed to be stories in a budget.
		 */
		return apply_filters( 'newspack_story_budget_post_types', [ 'post' ] );
	}

	/**
	 * Get budgets.
	 *
	 * @param bool $include_archived Whether to include archived budgets.
	 *
	 * @return Budget[]
	 */
	public static function get_budgets( $include_archived = false ) {
		$terms = get_terms(
			[
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
			]
		);

		$budgets = array_map(
			function( $term ) {
				return new Budget( $term );
			},
			$terms
		);

		if ( ! $include_archived ) {
			$budgets = array_filter(
				$budgets,
				function( $budget ) {
					return ! $budget->archived;
				}
			);
		}

		return array_values( $budgets );
	}

	/**
	 * Get stories from all active budgets.
	 *
	 * @param array $query_args WP_Query arguments.
	 * @param int   $budget_id  Optional. Budget ID to limit stories to.
	 *
	 * @return Stories|int[] Array of Story objects or post IDs.
	 */
	public static function get_stories( $query_args = [], $budget_id = null ) {
		$budget_ids = $budget_id ? [ $budget_id ] : array_map(
			function( $budget ) {
				return $budget->id;
			},
			self::get_budgets()
		);

		$tax_param = [
			'taxonomy' => self::TAXONOMY,
			'field'    => 'term_id',
			'terms'    => $budget_ids,
			'operator' => 'IN',
		];

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		if ( isset( $query_args['tax_query'] ) ) {
			$query_args['tax_query'][] = $tax_param;

			// Enforce "AND" relation to not query stories outside the budget.
			if ( ! empty( $query_args['tax_query']['relation'] ) && 'AND' !== $query_args['tax_query']['relation'] ) {
				_doing_it_wrong( __FUNCTION__, 'Stories only support tax query with "AND" relation.', '0.0.0' );
			}
			$query_args['tax_query']['relation'] = 'AND';
		} else {
			$query_args['tax_query'] = [ $tax_param ];
		}
		// phpcs:enable

		$query_args = wp_parse_args(
			$query_args,
			[
				'post_type'      => self::get_post_types(),
				'post_status'    => [ 'publish', 'pending', 'draft', 'future', 'private' ],
				'posts_per_page' => -1,
			]
		);

		self::$stories_query = new \WP_Query( $query_args );

		if ( ! empty( $query_args['fields'] ) && $query_args['fields'] === 'ids' ) {
			return self::$stories_query->posts;
		}

		return array_map(
			function( $post ) {
				return new Story( $post );
			},
			self::$stories_query->posts
		);
	}
}
