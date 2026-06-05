/**
 * Step 4: Preview Rates.
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { apiGet } from '../../common/api';
import { COUNTRIES } from '../../common/constants';

export default function PreviewRates( {
	data,
	updateData,
	onNext,
	onBack,
	showNotice,
} ) {
	const [ loading, setLoading ] = useState( true );
	const [ ratesData, setRatesData ] = useState( {
		rates: [],
		source: '',
		count: 0,
	} );

	const fetchRates = useCallback( async () => {
		setLoading( true );
		try {
			const result = await apiGet( 'wizard/preview-rates' );
			setRatesData( result );
			updateData( 'rates', result.rates || [] );
		} catch {
			showNotice(
				__(
					'Failed to fetch rates. Check your settings.',
					'taxzen-for-woocommerce'
				),
				'error'
			);
		} finally {
			setLoading( false );
		}
	}, [ updateData, showNotice ] );

	useEffect( () => {
		fetchRates();
	}, [ fetchRates ] );

	if ( loading ) {
		return (
			<div className="taxzen-loading">
				<div className="taxzen-spinner taxzen-spinner--lg"></div>
				<span className="taxzen-loading-text">
					{ __(
						'Fetching tax rates for',
						'taxzen-for-woocommerce'
					) }{ ' ' }
					{ data.targetCountries?.length || 0 }{ ' ' }
					{ __( 'countries…', 'taxzen-for-woocommerce' ) }
				</span>
			</div>
		);
	}

	return (
		<div>
			<h2>{ __( 'Preview Tax Rates', 'taxzen-for-woocommerce' ) }</h2>
			<p className="description">
				{ __(
					'Review the tax rates we found. These will be applied to your WooCommerce tax tables.',
					'taxzen-for-woocommerce'
				) }{ ' ' }
				<span className="taxzen-badge taxzen-badge--info">
					{ __( 'Source:', 'taxzen-for-woocommerce' ) }{ ' ' }
					{ ratesData.source || 'static' }
				</span>
			</p>

			<div className="taxzen-rate-preview">
				<table className="taxzen-table">
					<thead>
						<tr>
							<th>
								{ __( 'Country', 'taxzen-for-woocommerce' ) }
							</th>
							<th>
								{ __( 'State', 'taxzen-for-woocommerce' ) }
							</th>
							<th>
								{ __( 'Rate', 'taxzen-for-woocommerce' ) }
							</th>
							<th>
								{ __( 'Name', 'taxzen-for-woocommerce' ) }
							</th>
							<th>
								{ __( 'Type', 'taxzen-for-woocommerce' ) }
							</th>
							<th>
								{ __( 'Shipping', 'taxzen-for-woocommerce' ) }
							</th>
						</tr>
					</thead>
					<tbody>
						{ ratesData.rates?.map( ( rate, index ) => (
							<tr key={ index }>
								<td>
									<strong>{ rate.country_code }</strong>
									{ COUNTRIES[ rate.country_code ] && (
										<span
											style={ {
												color: 'var(--tw-gray-400)',
												marginLeft: '4px',
												fontSize:
													'var(--tw-font-size-xs)',
											} }
										>
											{ COUNTRIES[ rate.country_code ] }
										</span>
									) }
								</td>
								<td>{ rate.state || '—' }</td>
								<td>
									<strong>
										{ parseFloat( rate.rate ).toFixed( 2 ) }
										%
									</strong>
								</td>
								<td>{ rate.rate_name }</td>
								<td>
									<span
										className={ `taxzen-badge taxzen-badge--${
											rate.rate_type === 'standard'
												? 'success'
												: 'info'
										}` }
									>
										{ rate.rate_type }
									</span>
								</td>
								<td>{ rate.shipping ? '✓' : '—' }</td>
							</tr>
						) ) }
						{ ( ! ratesData.rates ||
							ratesData.rates.length === 0 ) && (
							<tr>
								<td
									colSpan="6"
									style={ {
										textAlign: 'center',
										padding: 'var(--tw-space-8)',
									} }
								>
									{ __(
										'No rates found. Try selecting different countries.',
										'taxzen-for-woocommerce'
									) }
								</td>
							</tr>
						) }
					</tbody>
				</table>
			</div>

			<p
				style={ {
					fontSize: 'var(--tw-font-size-sm)',
					color: 'var(--tw-gray-500)',
					marginTop: 'var(--tw-space-3)',
				} }
			>
				{ ratesData.count }{ ' ' }
				{ __( 'rates found', 'taxzen-for-woocommerce' ) }
			</p>

			<div className="taxzen-step-actions">
				<button
					className="taxzen-btn taxzen-btn--secondary"
					onClick={ onBack }
				>
					{ __( '← Back', 'taxzen-for-woocommerce' ) }
				</button>
				<div style={ { display: 'flex', gap: 'var(--tw-space-3)' } }>
					<button
						className="taxzen-btn taxzen-btn--outline"
						onClick={ fetchRates }
					>
						{ __( '↻ Refresh', 'taxzen-for-woocommerce' ) }
					</button>
					<button
						className="taxzen-btn taxzen-btn--primary taxzen-btn--lg"
						onClick={ onNext }
						disabled={ ! ratesData.rates?.length }
					>
						{ __( 'Continue →', 'taxzen-for-woocommerce' ) }
					</button>
				</div>
			</div>
		</div>
	);
}
