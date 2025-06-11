/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { Icon } from '@wordpress/components';
import { seen, update, edit } from '@wordpress/icons';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import { NAMESPACE } from '../store/constants';
import TableRowField from '../components/table-row-field';
import { getFieldElements, getFilterByOperators } from '../utils/fields';
import { isBudgetStories } from '../utils/budgets';

/**
 * Hook to get all fields
 *
 * @return {Array} Array of fields
 */
export const useFields = () => {
	return useSelect( select => select( NAMESPACE ).getFields(), [] );
};

/**
 * Hook to get the fields for DataViews.
 *
 * @param {Object}  params           The hook parameters.
 * @param {boolean} params.allowEdit Whether to allow editing.
 *
 * @return {Array} The fields.
 */
export const useStoryFields = ( { allowEdit } ) => {
	const fields = useFields();

	return useMemo(
		() =>
			fields
				.filter( field => {
					// Skip the budgets field if we're viewing a budget's stories.
					if ( 'budgets' === field.slug && isBudgetStories() ) {
						return false;
					}
					return true;
				} )
				.map( field => ( {
					id: field.slug,
					label: field.name,
					isVisible: () =>
						field.show_in_table || field.always_visible_in_table,
					type: field.type,
					enableHiding: ! field.always_visible_in_table,
					enableSorting: field.is_sortable,
					elements: getFieldElements( field ),
					filterBy:
						field.is_filterable && field.is_filterable !== 'no'
							? {
									operators: getFilterByOperators( field ),
									isPrimary: field.is_filterable === 'always',
							  }
							: undefined,
					render: value => (
						<TableRowField
							story={ value.item }
							field={ field }
							allowEdit={ allowEdit }
						/>
					),
				} ) ),
		[ fields, allowEdit ]
	);
};

/**
 * Hook to get the actions for DataViews.
 *
 * @return {Array} The actions.
 */
export const useStoryActions = () => {
	const canManage = useSelect( select => select( NAMESPACE ).canManage() );

	const { fetchStory, clearErrors } = useDispatch( NAMESPACE );

	return useMemo(
		() => [
			...applyFilters( 'newspack-story-budget.actions', [
				{
					id: 'view',
					label: __( 'View', 'newspack-story-budget' ),
					isPrimary: true,
					icon: <Icon icon={ seen } />,
					callback: items => {
						fetchStory( items[ 0 ].id );
						window.location.hash = '#/stories/' + items[ 0 ].id;
					},
				},
				{
					id: 'refresh',
					label: __( 'Refresh', 'newspack-story-budget' ),
					isPrimary: false,
					supportsBulk: true,
					icon: <Icon icon={ update } />,
					callback: items => {
						for ( const item of items ) {
							clearErrors( item.id );
							fetchStory( item.id );
						}
					},
				},
				{
					id: 'edit',
					label: __( 'Edit Post', 'newspack-story-budget' ),
					isEligible: item => canManage && !! item.metadata?.edit_url,
					isPrimary: false,
					icon: <Icon icon={ edit } />,
					callback: items => {
						if ( items[ 0 ].metadata?.edit_url ) {
							window.open( items[ 0 ].metadata.edit_url );
						}
					},
				},
			] ),
		],
		[ canManage ]
	);
};
