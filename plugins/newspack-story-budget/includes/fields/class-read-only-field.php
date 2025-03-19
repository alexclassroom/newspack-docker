<?php
/**
 * Newspack Story Budget - abstract class for a story budget field.
 *
 * @package Newspack_Story_Budget
 */

namespace Newspack_Story_Budget\Fields;

use Newspack_Story_Budget\Budgets;

/**
 * Class for editable fields.
 */
class Read_Only_Field extends Abstract_Field {
	/**
	 * Optional callback used to dynamically calculate the value of the field.
	 *
	 * @var callable|null
	 */
	protected $get_value_callback = null;

	/**
	 * Optional hook name to trigger the $save_value_callback. Defaults to save_post_post.
	 * TODO: This isn't currently implemented. Needs more discussion about how to handle.
	 *
	 * @var string
	 */
	protected $save_value_hook = 'save_post';

	/**
	 * Optional callback used to calculate the value of the field on post_save.
	 * If provided, the value will be calculated stored as post meta on post update.
	 *
	 * @var callable|null
	 */
	protected $save_value_callback = null;

	/**
	 * Register the field.
	 *
	 * @param array $args {
	 *    Configuration for registering a read-only field. See abstract class constructor for additional params.
	 *    @type callable $get_value_callback?   Optional callback used to dynamically calculate the value of a read-only field.
	 *    @type callable $save_value_hook?      Optional hook name to trigger the $save_value_callback. Defaults to save_post_post.
	 *    @type callable $save_value_callback?  Optional callback used to calculate the value of a read-only field on $save_value_hook.
	 * }
	 */
	public function __construct( $args ) {

		parent::__construct( $args );

		if ( ! empty( $args['get_value_callback'] ) && is_callable( $args['get_value_callback'] ) ) {
			$this->get_value_callback = $args['get_value_callback'];
		}

		if ( ! empty( $args['save_value_callback'] ) && is_callable( $args['save_value_callback'] ) ) {
			$this->save_value_callback = $args['save_value_callback'];
		}

		if ( ! empty( $args['save_value_hook'] ) ) {
			$this->save_value_hook = $args['save_value_hook'];
		}

		if ( is_null( $this->get_value_callback ) && is_null( $this->save_value_callback ) ) {
			$this->errors->add(
				'newspack_story_budget_invalid_field_configuration',
				__( 'Read-only fields must receive a callback function to calculate their value.', 'newspack-story-budget' )
			);
		}
	}

	/**
	 * Get the field's save_value_hook name.
	 *
	 * @return string The field's save_value_hook.
	 */
	public function get_save_value_hook() {
		return $this->save_value_hook;
	}

	/**
	 * Get the field's save_value_callback.
	 *
	 * @return callable? The field's callback.
	 */
	public function get_save_value_callback() {
		return $this->save_value_callback;
	}

	/**
	 * Get the field's value.
	 *
	 * @param int $post_id The post ID to get the value for. If not passed, return the default value, if any.
	 *
	 * @return mixed The field's value or WP_Error.
	 */
	public function get_value( $post_id ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return null;
		}

		if ( ! in_array( \get_post_type( $post_id ), Budgets::get_post_types(), true ) ) {
			return new \WP_Error(
				'newspack_story_budget_invalid_post_type',
				sprintf(
					// Translators: %d is the post ID.
					__( 'Post ID %d is not a valid post type for story budgets.', 'newspack-story-budget' ),
					$post_id
				)
			);
		}

		// Dynamically calculate the value.
		if ( $this->get_value_callback && is_callable( $this->get_value_callback ) ) {
			return call_user_func( $this->get_value_callback, $post_id );
		}

		// Get the stored value.
		if ( $this->save_value_callback && is_callable( $this->save_value_callback ) ) {
			return \get_post_meta( $post_id, $this->get_post_meta_name(), true );
		}

		return null;
	}

	/**
	 * Return an error message if attempting to update a read-only field value.
	 *
	 * @return WP_Error
	 */
	public function update_value() {
		return new \WP_Error(
			'newspack_story_budget_read_only_field',
			__( "Cannot update a read-only field's value.", 'newspack-story-budget' )
		);
	}

	/**
	 * Return an error message if attempting to add a read-only field value.
	 *
	 * @return WP_Error
	 */
	public function add_value() {
		return $this->update_value();
	}

	/**
	 * Return an error message if attempting to delete a read-only field value.
	 *
	 * @return WP_Error
	 */
	public function delete_value() {
		return $this->update_value();
	}
}
