<?php
/**
 * Newspack Story Budget Budget
 *
 * @package Newspack_Story_Budget
 */

namespace Newspack_Story_Budget;

/**
 * Budget Class.
 */
class Budget {
	/**
	 * Budget ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Budget term object.
	 *
	 * @var \WP_Term
	 */
	public $term;

	/**
	 * Whether this budget is archived.
	 *
	 * @var bool
	 */
	public $archived;

	/**
	 * Stories query object.
	 *
	 * @var \WP_Query
	 */
	public $stories_query;

	/**
	 * Stories count.
	 *
	 * @var int
	 */
	public $story_count;

	/**
	 * Order of the budget.
	 *
	 * @var int
	 */
	public $order;

	/**
	 * Meta key for archived status.
	 *
	 * @var string
	 */
	const ARCHIVE_META_KEY = 'archived';

	/**
	 * Meta Key for active budgets order.
	 */
	const ORDER_META_KEY = 'order';

	/**
	 * Constructor.
	 *
	 * @param int|\WP_Term $term Budget ID or term object.
	 */
	public function __construct( $term ) {
		if ( $term instanceof \WP_Term ) {
			$this->id   = $term->term_id;
			$this->term = $term;
		} else {
			$this->id   = $term;
			$this->term = get_term( $term, Budgets::TAXONOMY );
		}

		$this->archived    = (bool) get_term_meta( $this->id, self::ARCHIVE_META_KEY, true );
		$this->order       = (int) get_term_meta( $this->id, self::ORDER_META_KEY, true );
		$this->story_count = $this->get_stories_count();
	}

	/**
	 * Whether it's a valid budget.
	 *
	 * @return bool
	 */
	public function is_valid() {
		return ! $this->get_budget_errors()->has_errors();
	}

	/**
	 * Get budget errors.
	 *
	 * @return \WP_Error
	 */
	public function get_budget_errors() {
		$errors = new \WP_Error();

		if ( empty( $this->id ) || empty( $this->term ) || is_wp_error( $this->term ) ) {
			$errors->add( 'not_found', __( 'Budget not found.', 'newspack-story-budget' ) );
			return $errors;
		}

		if ( $this->term->taxonomy !== Budgets::TAXONOMY ) {
			$errors->add( 'invalid_taxonomy', __( 'This is not a budget.', 'newspack-story-budget' ) );
		}

		return $errors;
	}

	/**
	 * Archive a budget.
	 *
	 * @return bool
	 */
	public function archive() {
		$this->archived = true;
		$this->order    = 0;
		delete_term_meta( $this->id, self::ORDER_META_KEY );
		return \update_term_meta( $this->id, self::ARCHIVE_META_KEY, true );
	}

	/**
	 * Unarchive a budget.
	 *
	 * @return bool
	 */
	public function unarchive() {
		$this->archived = false;
		$this->order    = 0;
		update_term_meta( $this->id, self::ORDER_META_KEY, 0 );
		return \delete_term_meta( $this->id, self::ARCHIVE_META_KEY );
	}

	/**
	 * Add one or more stories to this budget.
	 *
	 * @param int[] $post_ids Post IDs to add to this budget.
	 *
	 * @return int The number of stories successfully added.
	 */
	public function add_stories( $post_ids = [] ) {
		$results = 0;
		if ( empty( $post_ids ) ) {
			return $results;
		}
		foreach ( $post_ids as $post_id ) {
			$story = new Story( $post_id );
			if ( ! $story->is_valid() ) {
				Logger::error(
					sprintf(
						// Translators: post ID.
						__( 'Invalid story ID "%d".', 'newspack-story-budget' ),
						$post_id
					)
				);
				continue;
			}

			$result = $story->update_budgets( [ $this->term->term_id ], true );
			if ( \is_wp_error( $result ) ) {
				Logger::error( $result );
			} else {
				$results++;
			}
		}
		return $results;
	}

	/**
	 * Remove one or more stories from this budget.
	 *
	 * @param int[] $post_ids Post IDs to remove from this budget.
	 *
	 * @return int The number of stories successfully removed.
	 */
	public function remove_stories( $post_ids = [] ) {
		$results = 0;
		if ( empty( $post_ids ) ) {
			return $results;
		}
		foreach ( $post_ids as $post_id ) {
			$story = new Story( $post_id );
			if ( ! $story->is_valid() ) {
				Logger::error(
					sprintf(
						// Translators: post ID.
						__( 'Invalid story ID "%d".', 'newspack-story-budget' ),
						$post_id
					)
				);
				continue;
			}

			$result = $story->remove_budgets( [ $this->term->term_id ] );
			if ( \is_wp_error( $result ) ) {
				Logger::error( $result );
			} else {
				$results++;
			}
		}
		return $results;
	}

	/**
	 * Get budget in array format.
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'id'          => $this->id,
			'name'        => $this->term->name,
			'description' => $this->term->description,
			'slug'        => $this->term->slug,
			'archived'    => $this->archived,
			'story_count' => $this->story_count,
			'order'       => $this->order,
		];
	}

	/**
	 * Get budget stories.
	 *
	 * @param array $query_args WP_Query arguments.
	 *
	 * @return Story[]
	 */
	public function get_stories( $query_args = [] ) {
		return Budgets::get_stories( $query_args, $this->id );
	}

	/**
	 * Get budget stories count.
	 * TODO: Implement a performance-optimized way to get the count.
	 *
	 * @return int
	 */
	public function get_stories_count() {
		return 0;
	}
}
