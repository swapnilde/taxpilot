/**
 * TaxPilot for WooCommerce — Wizard Entry Point.
 */
import { createRoot, render } from '@wordpress/element';
import App from './App';

const rootEl = document.getElementById( 'taxpilot-wizard-root' );
if ( rootEl ) {
	if ( createRoot ) {
		const root = createRoot( rootEl );
		root.render( <App /> );
	} else {
		render( <App />, rootEl );
	}
}
