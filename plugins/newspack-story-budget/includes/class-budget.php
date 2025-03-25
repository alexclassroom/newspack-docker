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

		$this->archived = (bool) get_term_meta( $this->id, 'archived', true );
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
		return \update_term_meta( $this->id, 'archived', true );
	}

	/**
	 * Unarchive a budget.
	 *
	 * @return bool
	 */
	public function unarchive() {
		$this->archived = false;
		return \delete_term_meta( $this->id, 'archived' );
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
}
