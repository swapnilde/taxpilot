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
			return __( 'Applying…', 'taxzen-for-woocommerce' );
		}
		const count = rateCount || data.rates?.length || 0;
		const action = confirmOverride
			? __( 'Confirm & Apply', 'taxzen-for-woocommerce' )
			: __( 'Apply', 'taxzen-for-woocommerce' );
		return `✓ ${ action } ${ count } ${ __(
			'Rates',
			'taxzen-for-woocommerce'
		) }`;
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
					'taxzen-for-woocommerce'
				) }`
			);
		} catch {
			showNotice(
				__(
					'Failed to apply rates. Please try again.',
					'taxzen-for-woocommerce'
				),
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
			<div className="taxzen-success">
				<div className="taxzen-success-icon">🎉</div>
				<h2>
					{ __( 'Tax Setup Complete!', 'taxzen-for-woocommerce' ) }
				</h2>
				<p>
					{ result.applied }{ ' ' }
					{ __(
						'tax rates have been applied to your WooCommerce store.',
						'taxzen-for-woocommerce'
					) }
				</p>
				{ result.errors?.length > 0 && (
					<div style={ { marginBottom: 'var(--tw-space-4)' } }>
						<span className="taxzen-badge taxzen-badge--warning">
							{ result.errors.length }{ ' ' }
							{ __(
								'errors occurred',
								'taxzen-for-woocommerce'
							) }
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
									...
									{ __(
										'and',
										'taxzen-for-woocommerce'
									) }{ ' ' }
									{ result.errors.length - 5 }{ ' ' }
									{ __( 'more', 'taxzen-for-woocommerce' ) }
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
							window.taxZenData?.adminUrl +
							'admin.php?page=taxzen'
						}
						className="taxzen-btn taxzen-btn--primary taxzen-btn--lg"
					>
						{ __(
							'Go to Dashboard →',
							'taxzen-for-woocommerce'
						) }
					</a>
					<a
						href={
							window.taxZenData?.adminUrl +
							'admin.php?page=wc-settings&tab=tax'
						}
						className="taxzen-btn taxzen-btn--secondary taxzen-btn--lg"
					>
						{ __(
							'View WooCommerce Tax Settings',
							'taxzen-for-woocommerce'
						) }
					</a>
					{ onStartOver && (
						<button
							className="taxzen-btn taxzen-btn--outline"
							onClick={ onStartOver }
						>
							{ __(
								'🔄 Start Over',
								'taxzen-for-woocommerce'
							) }
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
			<div className="taxzen-loading">
				<div className="taxzen-spinner"></div>
				<span className="taxzen-loading-text">
					{ __(
						'Loading rate preview…',
						'taxzen-for-woocommerce'
					) }
				</span>
			</div>
		);
	}

	return (
		<div>
			<h2>{ __( 'Apply Tax Rates', 'taxzen-for-woocommerce' ) }</h2>
			<p className="description">
				{ __(
					'Review the summary below and click "Apply" to configure your WooCommerce tax tables.',
					'taxzen-for-woocommerce'
				) }
			</p>

			<div className="taxzen-apply-summary">
				<h3>{ __( 'Setup Summary', 'taxzen-for-woocommerce' ) }</h3>
				<div className="taxzen-apply-stat">
					<span className="taxzen-apply-stat-label">
						{ __( 'Store Country', 'taxzen-for-woocommerce' ) }
					</span>
					<span className="taxzen-apply-stat-value">
						{ COUNTRIES[ data.country ] || data.country } (
						{ data.currency })
					</span>
				</div>
				<div className="taxzen-apply-stat">
					<span className="taxzen-apply-stat-label">
						{ __( 'Product Types', 'taxzen-for-woocommerce' ) }
					</span>
					<span className="taxzen-apply-stat-value">
						{ data.productTypes
							?.map(
								( t ) =>
									t.charAt( 0 ).toUpperCase() + t.slice( 1 )
							)
							.join( ', ' ) || 'None' }
					</span>
				</div>
				<div className="taxzen-apply-stat">
					<span className="taxzen-apply-stat-label">
						{ __( 'Target Countries', 'taxzen-for-woocommerce' ) }
					</span>
					<span className="taxzen-apply-stat-value">
						{ countryCount }
					</span>
				</div>
				<div className="taxzen-apply-stat">
					<span className="taxzen-apply-stat-label">
						{ __(
							'Tax Rates to Apply',
							'taxzen-for-woocommerce'
						) }
					</span>
					<span className="taxzen-apply-stat-value">
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
						'taxzen-for-woocommerce'
					) }
				</div>
			) }

			<div className="taxzen-step-actions">
				<button
					className="taxzen-btn taxzen-btn--secondary"
					onClick={ onBack }
				>
					{ __( '← Back', 'taxzen-for-woocommerce' ) }
				</button>
				<div style={ { display: 'flex', gap: 'var(--tw-space-3)' } }>
					{ confirmOverride && (
						<button
							className="taxzen-btn taxzen-btn--outline"
							onClick={ () => setConfirmOverride( false ) }
						>
							{ __( 'Cancel', 'taxzen-for-woocommerce' ) }
						</button>
					) }
					<button
						className="taxzen-btn taxzen-btn--success taxzen-btn--lg"
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
