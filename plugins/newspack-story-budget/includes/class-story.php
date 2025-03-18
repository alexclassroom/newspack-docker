<?php
/**
 * Newspack Story Budget Story
 *
 * @package Newspack_Story_Budget
 */

namespace Newspack_Story_Budget;

/**
 * Story Class.
 */
class Story {
	/**
	 * Story ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Story post object.
	 *
	 * @var \WP_Post
	 */
	public $post;

	/**
	 * Constructor.
	 *
	 * @param int|\WP_Post $post Story ID or post object.
	 */
	public function __construct( $post ) {
		if ( $post instanceof \WP_Post ) {
			$this->id   = $post->ID;
			$this->post = $post;
		} else {
			$this->id   = $post;
			$this->post = get_post( $post );
		}
	}

	/**
	 * Whether it's a valid story.
	 *
	 * @return bool
	 */
	public function is_valid() {
		return ! empty( $this->id ) && ! empty( $this->post ) && ! is_wp_error( $this->post );
	}

	/**
	 * Get story in array format.
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'id'      => $this->id,
			'title'   => get_the_title( $this->post ),
			'slug'    => get_post_field( 'post_name', $this->post ),
			'budgets' => wp_get_post_terms( $this->id, Budgets::TAXONOMY, [ 'fields' => 'ids' ] ),
		];
	}
}
