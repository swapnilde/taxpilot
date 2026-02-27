/**
 * Step 5: Apply Rates to WooCommerce.
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { apiPost, apiGet } from '../../common/api';
import { COUNTRIES } from '../../common/constants';

export default function ApplyRates( {
	data,
	onBack,
	showNotice,
	onStartOver,
} ) {
	const [ applying, setApplying ] = useState( false );
	const [ result, setResult ] = useState( null );
	const [ confirmOverride, setConfirmOverride ] = useState( false );
	const [ rateCount, setRateCount ] = useState( data.rates?.length || 0 );
	const [ loadingPreview, setLoadingPreview ] = useState( false );

	// If we don't have rates data (e.g. landing here on re-run), fetch preview.
	useEffect( () => {
		if ( ! data.rates?.length ) {
			setLoadingPreview( true );
			apiGet( 'wizard/preview-rates' )
				.then( ( res ) => {
					setRateCount( res.count || 0 );
				} )
				.catch( () => {
					// Rates will show as 0, user can go back to fix.
				} )
				.finally( () => setLoadingPreview( false ) );
		}
	}, [ data.rates ] );

	const getApplyButtonLabel = () => {
		if ( applying ) {
			return __( 'Applying…', 'taxpilot' );
		}
		const count = rateCount || data.rates?.length || 0;
		const action = confirmOverride
			? __( 'Confirm & Apply', 'taxpilot' )
			: __( 'Apply', 'taxpilot' );
		return `✓ ${ action } ${ count } ${ __( 'Rates', 'taxpilot' ) }`;
	};

	const handleApply = async () => {
		// Show confirmation if not already confirmed.
		if ( ! confirmOverride ) {
			setConfirmOverride( true );
			return;
		}

		setApplying( true );
		try {
			const res = await apiPost( 'wizard/apply-rates' );
			setResult( res );
			showNotice(
				`${ res.applied } ${ __(
					'tax rates applied to WooCommerce!',
					'taxpilot'
				) }`
			);
		} catch {
			showNotice(
				__( 'Failed to apply rates. Please try again.', 'taxpilot' ),
				'error'
			);
		} finally {
			setApplying( false );
			setConfirmOverride( false );
		}
	};

	// Success state.
	if ( result?.success ) {
		return (
			<div className="taxpilot-success">
				<div className="taxpilot-success-icon">🎉</div>
				<h2>{ __( 'Tax Setup Complete!', 'taxpilot' ) }</h2>
				<p>
					{ result.applied }{ ' ' }
					{ __(
						'tax rates have been applied to your WooCommerce store.',
						'taxpilot'
					) }
				</p>
				{ result.errors?.length > 0 && (
					<div style={ { marginBottom: 'var(--tw-space-4)' } }>
						<span className="taxpilot-badge taxpilot-badge--warning">
							{ result.errors.length }{ ' ' }
							{ __( 'errors occurred', 'taxpilot' ) }
						</span>
						<ul
							style={ {
								textAlign: 'left',
								marginTop: 'var(--tw-space-2)',
								fontSize: 'var(--tw-font-size-sm)',
							} }
						>
							{ result.errors.slice( 0, 5 ).map( ( err, i ) => (
								<li
									key={ i }
									style={ { color: 'var(--tw-danger-600)' } }
								>
									{ err.country }: { err.error }
								</li>
							) ) }
							{ result.errors.length > 5 && (
								<li style={ { color: 'var(--tw-gray-500)' } }>
									...{ __( 'and', 'taxpilot' ) }{ ' ' }
									{ result.errors.length - 5 }{ ' ' }
									{ __( 'more', 'taxpilot' ) }
								</li>
							) }
						</ul>
					</div>
				) }
				<div
					style={ {
						display: 'flex',
						gap: 'var(--tw-space-3)',
						justifyContent: 'center',
						flexWrap: 'wrap',
					} }
				>
					<a
						href={
							window.taxPilotData?.adminUrl +
							'admin.php?page=taxpilot'
						}
						className="taxpilot-btn taxpilot-btn--primary taxpilot-btn--lg"
					>
						{ __( 'Go to Dashboard →', 'taxpilot' ) }
					</a>
					<a
						href={
							window.taxPilotData?.adminUrl +
							'admin.php?page=wc-settings&tab=tax'
						}
						className="taxpilot-btn taxpilot-btn--secondary taxpilot-btn--lg"
					>
						{ __( 'View WooCommerce Tax Settings', 'taxpilot' ) }
					</a>
					{ onStartOver && (
						<button
							className="taxpilot-btn taxpilot-btn--outline"
							onClick={ onStartOver }
						>
							{ __( '🔄 Start Over', 'taxpilot' ) }
						</button>
					) }
				</div>
			</div>
		);
	}

	// Confirmation / apply state.
	const displayRateCount = rateCount || data.rates?.length || 0;
	const countryCount = data.targetCountries?.length || 0;

	if ( loadingPreview ) {
		return (
			<div className="taxpilot-loading">
				<div className="taxpilot-spinner"></div>
				<span className="taxpilot-loading-text">
					{ __( 'Loading rate preview…', 'taxpilot' ) }
				</span>
			</div>
		);
	}

	return (
		<div>
			<h2>{ __( 'Apply Tax Rates', 'taxpilot' ) }</h2>
			<p className="description">
				{ __(
					'Review the summary below and click "Apply" to configure your WooCommerce tax tables.',
					'taxpilot'
				) }
			</p>

			<div className="taxpilot-apply-summary">
				<h3>{ __( 'Setup Summary', 'taxpilot' ) }</h3>
				<div className="taxpilot-apply-stat">
					<span className="taxpilot-apply-stat-label">
						{ __( 'Store Country', 'taxpilot' ) }
					</span>
					<span className="taxpilot-apply-stat-value">
						{ COUNTRIES[ data.country ] || data.country } (
						{ data.currency })
					</span>
				</div>
				<div className="taxpilot-apply-stat">
					<span className="taxpilot-apply-stat-label">
						{ __( 'Product Types', 'taxpilot' ) }
					</span>
					<span className="taxpilot-apply-stat-value">
						{ data.productTypes
							?.map(
								( t ) =>
									t.charAt( 0 ).toUpperCase() + t.slice( 1 )
							)
							.join( ', ' ) || 'None' }
					</span>
				</div>
				<div className="taxpilot-apply-stat">
					<span className="taxpilot-apply-stat-label">
						{ __( 'Target Countries', 'taxpilot' ) }
					</span>
					<span className="taxpilot-apply-stat-value">
						{ countryCount }
					</span>
				</div>
				<div className="taxpilot-apply-stat">
					<span className="taxpilot-apply-stat-label">
						{ __( 'Tax Rates to Apply', 'taxpilot' ) }
					</span>
					<span className="taxpilot-apply-stat-value">
						{ displayRateCount }
					</span>
				</div>
			</div>

			{ /* Confirmation banner */ }
			{ confirmOverride && (
				<div
					style={ {
						background: 'var(--tw-warning-50, #fffbeb)',
						border: '1px solid var(--tw-warning-300, #fcd34d)',
						borderRadius: 'var(--tw-radius-lg, 8px)',
						padding: 'var(--tw-space-4, 16px)',
						marginBottom: 'var(--tw-space-4, 16px)',
						fontSize: 'var(--tw-font-size-sm, 14px)',
						color: 'var(--tw-warning-800, #92400e)',
					} }
				>
					⚠️{ ' ' }
					{ __(
						'This will update your WooCommerce tax tables. Any manually added rates may be affected. Click "Apply" again to confirm.',
						'taxpilot'
					) }
				</div>
			) }

			<div className="taxpilot-step-actions">
				<button
					className="taxpilot-btn taxpilot-btn--secondary"
					onClick={ onBack }
				>
					{ __( '← Back', 'taxpilot' ) }
				</button>
				<div style={ { display: 'flex', gap: 'var(--tw-space-3)' } }>
					{ confirmOverride && (
						<button
							className="taxpilot-btn taxpilot-btn--outline"
							onClick={ () => setConfirmOverride( false ) }
						>
							{ __( 'Cancel', 'taxpilot' ) }
						</button>
					) }
					<button
						className="taxpilot-btn taxpilot-btn--success taxpilot-btn--lg"
						onClick={ handleApply }
						disabled={ applying || displayRateCount === 0 }
					>
						{ getApplyButtonLabel() }
					</button>
				</div>
			</div>
		</div>
	);
}
