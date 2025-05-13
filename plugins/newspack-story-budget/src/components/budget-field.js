/* eslint @wordpress/no-unsafe-wp-apis: 0 */

/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';
import {
	__experimentalHStack as HStack,
	__experimentalVStack as VStack,
	__experimentalInputControl as InputControl,
	Dropdown,
	Button,
} from '@wordpress/components';
import { __experimentalInspectorPopoverHeader as InspectorPopoverHeader } from '@wordpress/block-editor';
import { useState } from '@wordpress/element';
import classnames from 'classnames';

export default ( { budget, onUpdateBudget = () => {} } ) => {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ name, setName ] = useState( budget.name );

	const onSave = () => {
		if ( name !== budget.name ) {
			onUpdateBudget( budget.id, { ...budget, name } );
		}
		setIsOpen( false );
	}

	const onCancel = () => {
		setName( budget.name );
		setIsOpen( false );
	}

	return (
			<div className="newspack-story-budget__field">
				<Dropdown
					open={ isOpen }
					popoverProps={
						{
							placement: 'bottom-start',
							shift: true,
						}
					}
					contentClassName="newspack-story-budget__field__popover"
					onToggle={ () => {
						setIsOpen( ! isOpen );
					} }
					onClose={ () => {
						setIsOpen( false );
					} }
					renderToggle={ ( { onToggle } ) => (
						<Button
							className={ classnames( 'newspack-story-budget__field__button', 'newspack-story-budget__field__popover-button' ) }
							variant="tertiary"
							onClick={ onToggle }
							aria-expanded={ isOpen }
						>
							<h2>{ budget.name }</h2>
						</Button>
					) }
					renderContent={ ( { onClose } ) => (
						<>
							<InspectorPopoverHeader
								title={ __( 'Budget Name', 'newspack-story-budget' ) }
								onClose={ onClose }
							/>
							<VStack
								spacing={ 2 }
							>
								<div className="newspack-story-budget__field__content">
									<InputControl
										value={ name }
										onChange={ ( newName ) => {
											setName( newName );
										} }
									/>
								</div>
								<HStack
									expanded
									spacing={ 2 }
									justify="end"
									direction="row-reverse"
								>
									<Button
										variant="primary"
										disabled={ name === budget.name }
										type="submit"
										onClick={ onSave }
									>
										{ __( 'Save', 'newspack-story-budget' ) }
									</Button>
									<Button
										variant="secondary"
										onClick={ onCancel }
									>
										{ __( 'Cancel', 'newspack-story-budget' ) }
									</Button>
								</HStack>
							</VStack>
						</>
					) }
				/>
			</div>
	);
}
