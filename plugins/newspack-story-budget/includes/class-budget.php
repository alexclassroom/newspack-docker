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
	 * @return void
	 */
	public function archive() {
		$this->archived = true;
		update_term_meta( $this->id, 'archived', true );
	}

	/**
	 * Unarchive a budget.
	 *
	 * @return void
	 */
	public function unarchive() {
		$this->archived = false;
		delete_term_meta( $this->id, 'archived' );
	}

	/**
	 * Get budget in array format.
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'id'   => $this->id,
			'name' => $this->term->name,
			'slug' => $this->term->slug,
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
