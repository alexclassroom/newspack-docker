/* globals newspackStoryBudget */
/**
 * WordPress dependencies.
 */
import triggerFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies.
 */
import { getCurrentSite, getCredentials } from '../utils/sites';

const { apiNamespace } = newspackStoryBudget;

const remoteSite = getCurrentSite();

triggerFetch.use( ( options, next ) => {
	if ( ! options.newspackStoryBudget ) {
		return next( options );
	}

	if ( remoteSite ) {
		const { path, data, method } = options;
		const authorization = getCredentials( remoteSite );

		if ( ! authorization ) {
			return Promise.reject( {
				message: 'Credentials not found.',
			} );
		}

		const route = encodeURIComponent( apiNamespace + path );
		const url = `${ remoteSite }/?rest_route=/${ route }`;
		return next( {
			method,
			data,
			url,
			headers: {
				Authorization: `Basic ${ authorization }`,
			},
		} );
	}

	options.path = apiNamespace + options.path;
	return next( options );
} );

export function apiFetch( request ) {
	return {
		type: 'STORY_BUDGET_FETCH',
		request,
	};
}

export const controls = {
	STORY_BUDGET_FETCH( { request } ) {
		request.newspackStoryBudget = true;
		return triggerFetch( request );
	},
};
