/**
 * WordPress dependencies.
 */
import { useSelect } from '@wordpress/data';
import { Spinner, Tooltip, Icon } from '@wordpress/components';
import { error } from '@wordpress/icons';

/**
 * Internal dependencies.
 */
import { NAMESPACE as storeNamespace } from '../store/constants';
import StoryField from './story-field';

export default function TableRowField( { story, field, allowEdit = false } ) {
	const { isLoadingStory, storyError, view } = useSelect( select => ( {
		isLoadingStory: select( storeNamespace ).isLoadingStory( story.id ),
		storyError: select( storeNamespace ).getStoryError( story.id ),
		view: select( storeNamespace ).getView(),
	} ) );

	const fieldIdx = view.fields.findIndex( f => f === field.slug );

	return (
		<div className="newspack-story-budget__table-row-field">
			{ fieldIdx === 0 && isLoadingStory ? (
				<Spinner
					style={ {
						width: '13px',
						height: '13px',
					} }
				/>
			) : (
				<StoryField
					fieldId={ field.slug }
					storyId={ story.id }
					allowEdit={ allowEdit }
					saveInPlace
				/>
			) }
			{ fieldIdx === 0 && ! isLoadingStory && storyError && (
				<Tooltip text={ storyError }>
					<span className="newspack-story-budget__table-row-field-error">
						<Icon icon={ error } />
					</span>
				</Tooltip>
			) }
		</div>
	);
}
