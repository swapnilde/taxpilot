/**
 * TaxPilot for WooCommerce — API helper.
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Make a REST API call to the TaxPilot endpoints.
 *
 * @param {string} endpoint - Endpoint path (e.g., 'wizard/store-setup').
 * @param {Object} options  - Fetch options (method, data, etc.).
 * @return {Promise} Response data.
 */
export async function apiCall( endpoint, options = {} ) {
	const { method = 'GET', data = null } = options;

	const fetchOptions = {
		path: `/taxpilot/v1/${ endpoint }`,
		method,
	};

	if ( data && method !== 'GET' ) {
		fetchOptions.data = data;
	}

	try {
		return await apiFetch( fetchOptions );
	} catch ( error ) {
		console.error( `TaxPilot API Error [${ endpoint }]:`, error );
		throw error;
	}
}

/**
 * Shorthand for GET requests.
 *
 * @param {string} endpoint API endpoint path.
 * @return {Promise} API response promise.
 */
export const apiGet = ( endpoint ) => apiCall( endpoint, { method: 'GET' } );

/**
 * Shorthand for POST requests.
 *
 * @param {string} endpoint API endpoint path.
 * @param {Object} data     POST body data.
 * @return {Promise} API response promise.
 */
export const apiPost = ( endpoint, data ) =>
	apiCall( endpoint, { method: 'POST', data } );

/**
 * Shorthand for DELETE requests.
 *
 * @param {string} endpoint API endpoint path.
 * @return {Promise} API response promise.
 */
export const apiDelete = ( endpoint ) =>
	apiCall( endpoint, { method: 'DELETE' } );
