/**
 * Step 4: Preview Rates.
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { apiGet } from '../../common/api';
import { COUNTRIES } from '../../common/constants';

export default function PreviewRates({
	data,
	updateData,
	onNext,
	onBack,
	showNotice,
}) {
	const [loading, setLoading] = useState(true);
	const [ratesData, setRatesData] = useState({
		rates: [],
		source: '',
		count: 0,
	});

	const fetchRates = useCallback(async () => {
		setLoading(true);
		try {
			const result = await apiGet('wizard/preview-rates');
			setRatesData(result);
			updateData('rates', result.rates || []);
		} catch {
			showNotice(
				__('Failed to fetch rates. Check your settings.', 'taxpilot-for-woocommerce'),
				'error'
			);
		} finally {
			setLoading(false);
		}
	}, [updateData, showNotice]);

	useEffect(() => {
		fetchRates();
	}, [fetchRates]);

	if (loading) {
		return (
			<div className="taxpilot-loading">
				<div className="taxpilot-spinner taxpilot-spinner--lg"></div>
				<span className="taxpilot-loading-text">
					{__('Fetching tax rates for', 'taxpilot-for-woocommerce')}{' '}
					{data.targetCountries?.length || 0}{' '}
					{__('countries…', 'taxpilot-for-woocommerce')}
				</span>
			</div>
		);
	}

	return (
		<div>
			<h2>{__('Preview Tax Rates', 'taxpilot-for-woocommerce')}</h2>
			<p className="description">
				{__(
					'Review the tax rates we found. These will be applied to your WooCommerce tax tables.',
					'taxpilot-for-woocommerce'
				)}{' '}
				<span className="taxpilot-badge taxpilot-badge--info">
					{__('Source:', 'taxpilot-for-woocommerce')}{' '}
					{ratesData.source || 'static'}
				</span>
			</p>

			<div className="taxpilot-rate-preview">
				<table className="taxpilot-table">
					<thead>
						<tr>
							<th>{__('Country', 'taxpilot-for-woocommerce')}</th>
							<th>{__('State', 'taxpilot-for-woocommerce')}</th>
							<th>{__('Rate', 'taxpilot-for-woocommerce')}</th>
							<th>{__('Name', 'taxpilot-for-woocommerce')}</th>
							<th>{__('Type', 'taxpilot-for-woocommerce')}</th>
							<th>{__('Shipping', 'taxpilot-for-woocommerce')}</th>
						</tr>
					</thead>
					<tbody>
						{ratesData.rates?.map((rate, index) => (
							<tr key={index}>
								<td>
									<strong>{rate.country_code}</strong>
									{COUNTRIES[rate.country_code] && (
										<span
											style={{
												color: 'var(--tw-gray-400)',
												marginLeft: '4px',
												fontSize:
													'var(--tw-font-size-xs)',
											}}
										>
											{COUNTRIES[rate.country_code]}
										</span>
									)}
								</td>
								<td>{rate.state || '—'}</td>
								<td>
									<strong>
										{parseFloat(rate.rate).toFixed(2)}
										%
									</strong>
								</td>
								<td>{rate.rate_name}</td>
								<td>
									<span
										className={`taxpilot-badge taxpilot-badge--${rate.rate_type === 'standard'
												? 'success'
												: 'info'
											}`}
									>
										{rate.rate_type}
									</span>
								</td>
								<td>{rate.shipping ? '✓' : '—'}</td>
							</tr>
						))}
						{(!ratesData.rates ||
							ratesData.rates.length === 0) && (
								<tr>
									<td
										colSpan="6"
										style={{
											textAlign: 'center',
											padding: 'var(--tw-space-8)',
										}}
									>
										{__(
											'No rates found. Try selecting different countries.',
											'taxpilot-for-woocommerce'
										)}
									</td>
								</tr>
							)}
					</tbody>
				</table>
			</div>

			<p
				style={{
					fontSize: 'var(--tw-font-size-sm)',
					color: 'var(--tw-gray-500)',
					marginTop: 'var(--tw-space-3)',
				}}
			>
				{ratesData.count} {__('rates found', 'taxpilot-for-woocommerce')}
			</p>

			<div className="taxpilot-step-actions">
				<button
					className="taxpilot-btn taxpilot-btn--secondary"
					onClick={onBack}
				>
					{__('← Back', 'taxpilot-for-woocommerce')}
				</button>
				<div style={{ display: 'flex', gap: 'var(--tw-space-3)' }}>
					<button
						className="taxpilot-btn taxpilot-btn--outline"
						onClick={fetchRates}
					>
						{__('↻ Refresh', 'taxpilot-for-woocommerce')}
					</button>
					<button
						className="taxpilot-btn taxpilot-btn--primary taxpilot-btn--lg"
						onClick={onNext}
						disabled={!ratesData.rates?.length}
					>
						{__('Continue →', 'taxpilot-for-woocommerce')}
					</button>
				</div>
			</div>
		</div>
	);
}
