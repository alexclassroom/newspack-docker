<?php
/**
 * Newspack Story Budget API
 *
 * @package Newspack_Story_Budget
 */

namespace Newspack_Story_Budget;

/**
 * API Class.
 */
class API {

	/**
	 * API Namespace
	 *
	 * @var string
	 */
	const NAMESPACE = 'newspack-story-budget/v1';

	/**
	 * Default limit of items to return.
	 */
	const DEFAULT_LIMIT = 1000;

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * Register REST API routes.
	 */
	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/stories',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'get_stories' ],
				'permission_callback' => [ __CLASS__, 'permission_callback' ],
				'args'                => [
					'limit'  => [
						'description' => __( 'Number of stories to return.', 'newspack-story-budget' ),
						'type'        => 'integer',
					],
					'offset' => [
						'description' => __( 'Offset.', 'newspack-story-budget' ),
						'type'        => 'integer',
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/stories/search',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'get_stories_search' ],
				'permission_callback' => [ __CLASS__, 'permission_callback' ],
				'args'                => [
					's' => [
						'description' => __( 'Search query.', 'newspack-story-budget' ),
						'type'        => 'string',
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/stories/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'get_story' ],
				'permission_callback' => [ __CLASS__, 'permission_callback' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/budgets',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'get_budgets' ],
				'permission_callback' => [ __CLASS__, 'permission_callback' ],
				'args'                => [
					'limit'  => [
						'description' => __( 'Number of budgets to return.', 'newspack-story-budget' ),
						'type'        => 'integer',
					],
					'offset' => [
						'description' => __( 'Offset.', 'newspack-story-budget' ),
						'type'        => 'integer',
					],
				],
			]
		);
		// @TODO Add more routes for budget CRUD.

		register_rest_route(
			self::NAMESPACE,
			'/budgets/(?P<id>\d+)/stories',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'get_budget_stories' ],
				'permission_callback' => [ __CLASS__, 'permission_callback' ],
			]
		);
		register_rest_route(
			self::NAMESPACE,
			'/budgets/(?P<id>\d+)/stories/search',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'get_budget_stories_search' ],
				'permission_callback' => [ __CLASS__, 'permission_callback' ],
				'args'                => [
					's' => [
						'description' => __( 'Search query.', 'newspack-story-budget' ),
						'type'        => 'string',
					],
				],
			]
		);
	}

	/**
	 * Permission callback.
	 *
	 * @return bool
	 */
	public static function permission_callback() {
		return current_user_can( 'edit_others_posts' );
	}

	/**
	 * Get stories.
	 *
	 * @param \WP_Rest_Request $request Request object.
	 *
	 * @return \WP_Rest_Response
	 */
	public static function get_stories( $request ) {
		$query_args = [
			'posts_per_page' => $request->get_param( 'limit' ) ?? self::DEFAULT_LIMIT,
			'offset'         => $request->get_param( 'offset' ) ?? 0,
		];

		$stories = Budgets::get_stories( $query_args );

		return rest_ensure_response(
			[
				'stories' => array_map(
					function( $story ) {
						return $story->to_array();
					},
					$stories
				),
				'total'   => Budgets::$stories_query->found_posts,
			]
		);
	}

	/**
	 * Get stories search.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_stories_search( $request ) {
		$query_args = [
			'fields'         => 'ids',
			'posts_per_page' => -1,
			's'              => $request->get_param( 's' ) ?? '',
		];
		return rest_ensure_response(
			[
				'story_ids' => Budgets::get_stories( $query_args ),
				'total'     => Budgets::$stories_query->found_posts,
			]
		);
	}

	/**
	 * Get story.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_story( $request ) {
		$story = new Story( $request->get_param( 'id' ) );
		if ( ! $story->is_valid() ) {
			return new \WP_Error( 'story_not_found', __( 'Story not found.', 'newspack-story-budget' ), [ 'status' => 404 ] );
		}

		return rest_ensure_response( $story->to_array() );
	}

	/**
	 * Get budgets.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public static function get_budgets( $request ) {
		$limit  = $request->get_param( 'limit' ) ?? self::DEFAULT_LIMIT;
		$offset = $request->get_param( 'offset' ) ?? 0;

		$budgets = array_map(
			function( $budget ) {
				return $budget->to_array();
			},
			Budgets::get_budgets()
		);
		$total   = count( $budgets );

		// Limit and offset.
		if ( $limit < count( $budgets ) ) {
			$budgets = array_slice( $budgets, $offset, $limit );
		}

		return rest_ensure_response(
			[
				'budgets' => $budgets,
				'total'   => $total,
			]
		);
	}

	/**
	 * Get budget stories.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public static function get_budget_stories( $request ) {
		$budget = new Budget( $request->get_param( 'id' ) );
		if ( ! $budget->is_valid() ) {
			return new \WP_Error( 'budget_not_found', __( 'Budget not found.', 'newspack-story-budget' ), [ 'status' => 404 ] );
		}

		$stories = $budget->get_stories();

		return rest_ensure_response(
			[
				'stories' => array_map(
					function( $story ) {
						return $story->to_array();
					},
					$stories
				),
				'total'   => Budgets::$stories_query->found_posts,
			]
		);
	}

	/**
	 * Get budget stories search.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_budget_stories_search( $request ) {
		$budget = new Budget( $request->get_param( 'id' ) );
		if ( ! $budget->is_valid() ) {
			return new \WP_Error( 'budget_not_found', __( 'Budget not found.', 'newspack-story-budget' ), [ 'status' => 404 ] );
		}

		$query_args = [
			'fields'         => 'ids',
			'posts_per_page' => -1,
			's'              => $request->get_param( 's' ) ?? '',
		];

		return rest_ensure_response(
			[
				'story_ids' => $budget->get_stories( $query_args ),
				'total'     => Budgets::$stories_query->found_posts,
			]
		);
	}
}
