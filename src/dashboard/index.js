/**
 * TaxPilot for WooCommerce — Dashboard Entry Point.
 */
import { createRoot, render } from '@wordpress/element';
import App from './App';

const rootEl = document.getElementById( 'taxpilot-dashboard-root' );
if ( rootEl ) {
	if ( createRoot ) {
		const root = createRoot( rootEl );
		root.render( <App /> );
	} else {
		render( <App />, rootEl );
	}
}
