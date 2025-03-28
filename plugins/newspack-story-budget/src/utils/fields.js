export const getDisplayValue = ( field, value ) => {
	if (
		value === null ||
		value === undefined ||
		( Array.isArray( value ) && ! value.length ) ||
		( [ 'date', 'datetime', 'text', 'longtext' ].includes( field.type ) &&
			! value )
	) {
		return null;
	}
	if ( field.options?.length ) {
		if ( Array.isArray( value ) ) {
			value = value.map(
				v => field.options.find( o => o.value === v )?.label || v
			);
		}
		value = field.options.find( o => o.value === value )?.label || value;
	}
	if ( field.type === 'date' ) {
		return new Date( value * 1000 ).toLocaleDateString( undefined, {
			dateStyle: 'medium',
		} );
	}
	if ( field.type === 'datetime' ) {
		return new Date( value * 1000 ).toLocaleString( undefined, {
			dateStyle: 'medium',
			timeStyle: 'short',
		} );
	}
	if ( field.type === 'boolean' ) {
		return value ? 'Yes' : 'No';
	}
	if ( Array.isArray( value ) ) {
		return value.join( ', ' );
	}
	return value;
};
