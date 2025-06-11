/* eslint @wordpress/no-unsafe-wp-apis: 0 */
/**
 * External dependencies.
 */
import debounce from 'lodash/debounce';

/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { DataViews } from '@wordpress/dataviews/wp';
import {
	__experimentalHStack as HStack,
	__experimentalVStack as VStack,
	Button,
	Modal,
	Notice,
	ProgressBar,
	ToggleControl,
} from '@wordpress/components';
import { update } from '@wordpress/icons';

/**
 * Internal dependencies.
 */
import utils from '../utils';
import { NAMESPACE as storeNamespace } from '../store/constants';
import { useStoryFields, useStoryActions } from '../hooks';

export default () => {
	const {
		view,
		stories,
		isLoading,
		isRefreshing,
		progress,
		errors,
		canManage,
		canRefreshStories,
	} = useSelect( select => ( {
		view: select( storeNamespace ).getView(),
		stories: select( storeNamespace ).getStories(),
		isLoading: select( storeNamespace ).isLoading(),
		isRefreshing: select( storeNamespace ).isRefreshing(),
		progress: select( storeNamespace ).getProgress(),
		errors: select( storeNamespace ).getErrors(),
		canManage: select( storeNamespace ).canManage(),
		canRefreshStories: select( storeNamespace ).canRefreshStories(),
	} ) );
	const [ editMode, setEditMode ] = useState( false );
	const [ isReconnectingRemoteSite, setIsReconnectingRemoteSite ] =
		useState( false );
	const currentStories = stories.slice(
		( view.page - 1 ) * view.perPage,
		( view.page - 1 ) * view.perPage + view.perPage
	);

	const {
		clearErrors,
		setView,
		setSearching,
		search,
		fetchFields,
		refreshStories,
	} = useDispatch( storeNamespace );

	const doSearch = debounce( search, 300 );

	useEffect( () => {
		if ( view.search ) {
			setSearching();
			doSearch( view.search );
		}
	}, [ view.search ] );

	useEffect( () => {
		return () => {
			if ( utils.budgets.isBudgetStories() ) {
				utils.budgets.redirectWithCleanUrl();
			}
		};
	}, [] );

	const dataViewFields = useStoryFields( {
		allowEdit: editMode && ! isRefreshing,
	} );

	const actions = useStoryActions();

	if ( isLoading && undefined !== progress && progress < 1 ) {
		return (
			<div className="newspack-story-budget__loading">
				<ProgressBar value={ Math.ceil( progress * 100 ) } />
				<p>{ __( 'Fetching Stories…', 'newspack-story-budget' ) }</p>
			</div>
		);
	}

	const refresh = () => {
		clearErrors();
		fetchFields();
		refreshStories( false );
	};

	if ( errors?.stories ) {
		return (
			<Modal
				isOpen
				isDismissible={ false }
				size="small"
				title={ __( 'Something went wrong', 'newspack-story-budget' ) }
			>
				<VStack spacing={ 4 }>
					<Notice
						className="newspack-story-budget__error"
						isDismissible={ false }
						status="error"
					>
						{ errors.stories }
					</Notice>
					<HStack
						expanded
						spacing={ 2 }
						justify="end"
						direction="row-reverse"
					>
						{ utils.sites.isRemoteSite() ? (
							<>
								<Button
									variant="primary"
									onClick={ () => {
										utils.sites.connect();
										setIsReconnectingRemoteSite( true );
									} }
									isBusy={ isReconnectingRemoteSite }
									disabled={ isReconnectingRemoteSite }
								>
									{ __(
										'Reconnect',
										'newspack-story-budget'
									) }
								</Button>
								<Button
									variant="secondary"
									href={ utils.sites.getLeaveSiteUrl() }
								>
									{ __(
										'Leave remote site',
										'newspack-story-budget'
									) }
								</Button>
							</>
						) : (
							<Button
								variant="primary"
								onClick={ () => {
									window.location.reload();
								} }
							>
								{ __( 'Reload page', 'newspack-story-budget' ) }
							</Button>
						) }
					</HStack>
				</VStack>
			</Modal>
		);
	}

	return (
		<DataViews
			isLoading={ isLoading }
			fields={ dataViewFields }
			view={ view }
			onChangeView={ setView }
			actions={ actions }
			data={ isLoading ? [] : currentStories }
			paginationInfo={ {
				totalItems: stories.length,
				totalPages: Math.ceil( stories.length / view.perPage ),
			} }
			defaultLayouts={ {
				table: {
					showMedia: false,
				},
			} }
			header={
				<HStack style={ { marginLeft: '8px' } }>
					{ canRefreshStories && (
						<Button
							className={
								isLoading || isRefreshing
									? 'newspack-story-budget__refresh-button-is-busy'
									: 'newspack-story-budget__refresh-button'
							}
							icon={ update }
							disabled={ isLoading || isRefreshing }
							label={
								isLoading || isRefreshing
									? __(
											'Loading stories…',
											'newspack-story-budget'
									  )
									: __(
											'Refresh all stories',
											'newspack-story-budget'
									  )
							}
							size="compact"
							onClick={ refresh }
						/>
					) }
					{ canManage && (
						<ToggleControl
							label={ __( 'Edit mode', 'newspack-story-budget' ) }
							checked={ editMode }
							onChange={ setEditMode }
							__nextHasNoMarginBottom
						/>
					) }
				</HStack>
			}
		/>
	);
};
