/* globals newspackStoryBudget */
import { apiFetch } from '@wordpress/data-controls';

const { apiNamespace } = newspackStoryBudget;

export function* getFields() {
	try {
		const result = yield apiFetch( { path: `${ apiNamespace }/fields` } );
		return {
			type: 'FIELDS_SET',
			payload: result,
		};
	} catch ( error ) {
		return {
			type: 'FIELDS_ERROR',
			payload: error,
		};
	}
}

export function* getBudgets() {
	try {
		const result = yield apiFetch( { path: `${ apiNamespace }/budgets` } );
		const { budgets, total } = result;
		while ( budgets.length < total ) {
			const next = yield apiFetch( {
				path: `${ apiNamespace }/budgets?offset=${ budgets.length }`,
			} );
			budgets.push( ...next.budgets );
		}
		return {
			type: 'BUDGETS_SET',
			payload: budgets,
		};
	} catch ( error ) {
		return {
			type: 'BUDGETS_ERROR',
			payload: error,
		};
	}
}

export function* getStories() {
	yield { type: 'FETCH_START' };
	try {
		const result = yield apiFetch( { path: `${ apiNamespace }/stories` } );
		const { stories, total } = result;
		yield {
			type: 'FETCH_PROGRESS',
			payload: { result, progress: stories.length / total },
		};
		while ( stories.length < total ) {
			const next = yield apiFetch( {
				path: `${ apiNamespace }/stories?offset=${ stories.length }`,
			} );
			stories.push( ...next.stories );
			yield {
				type: 'FETCH_PROGRESS',
				payload: { result: next, progress: stories.length / total },
			};
		}
		return {
			type: 'STORIES_SET',
			payload: stories,
		};
	} catch ( error ) {
		return {
			type: 'STORIES_ERROR',
			payload: error,
		};
	}
}

export function* getStory( id ) {
	yield { type: 'FETCH_STORY_START', payload: id };
	try {
		const result = yield apiFetch( {
			path: `${ apiNamespace }/stories/${ id }`,
		} );
		yield { type: 'FETCH_STORY_SUCCESS', payload: id };
		return {
			type: 'STORIES_ADD',
			payload: result,
		};
	} catch ( error ) {
		yield { type: 'FETCH_STORY_ERROR', payload: id };
		return {
			type: 'STORIES_ERROR',
			payload: error,
		};
	}
}
