/**
 * TaxZen for WooCommerce — Dashboard App.
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { apiGet, apiPost } from '../common/api';
import { COUNTRIES } from '../common/constants';
import './dashboard.css';

export default function App() {
	const currentYear = new Date().getFullYear();
	const currentQuarter = Math.floor( ( new Date().getMonth() + 3 ) / 3 );

	const [ stats, setStats ] = useState( null );
	const [ rates, setRates ] = useState( [] );
	const [ alerts, setAlerts ] = useState( [] );
	const [ unreadCount, setUnreadCount ] = useState( 0 );
	const [ loading, setLoading ] = useState( true );
	const [ refreshing, setRefreshing ] = useState( false );
	const [ ossYear, setOssYear ] = useState( currentYear );
	const [ ossQuarter, setOssQuarter ] = useState( currentQuarter );

	const getSeverityIcon = ( severity ) => {
		if ( severity === 'critical' ) {
			return '🚨';
		}
		if ( severity === 'warning' ) {
			return '⚠️';
		}
		return 'ℹ️';
	};

	useEffect( () => {
		loadDashboard();
	}, [] );

	const loadDashboard = async () => {
		try {
			const [ statsRes, ratesRes, alertsRes ] = await Promise.all( [
				apiGet( 'rates/stats' ),
				apiGet( 'rates?limit=20' ),
				apiGet( 'alerts?limit=10' ),
			] );
			setStats( statsRes );
			setRates( ratesRes.rates || [] );
			setAlerts( alertsRes.alerts || [] );
			setUnreadCount( alertsRes.unread_count || 0 );
		} catch ( err ) {
			console.error( 'Dashboard load error:', err );
		} finally {
			setLoading( false );
		}
	};

	const handleRefresh = async () => {
		setRefreshing( true );
		try {
			await apiPost( 'rates/refresh' );
			await loadDashboard();
		} catch ( err ) {
			console.error( 'Refresh error:', err );
		} finally {
			setRefreshing( false );
		}
	};

	const handleMarkAllRead = async () => {
		await apiPost( 'alerts/read-all' );
		setUnreadCount( 0 );
		setAlerts( alerts.map( ( a ) => ( { ...a, is_read: '1' } ) ) );
	};

	const handleExportCSV = () => {
		const baseUrl = window.taxZenData?.restUrl || '/wp-json/taxzen/v1/';
		const nonce = window.taxZenData?.nonce || '';
		const url = `${ baseUrl }reports/csv${
			nonce ? '?_wpnonce=' + nonce : ''
		}`;
		window.open( url, '_blank' );
	};

	const handleExportPDF = () => {
		const baseUrl = window.taxZenData?.restUrl || '/wp-json/taxzen/v1/';
		const nonce = window.taxZenData?.nonce || '';
		const url = `${ baseUrl }reports/pdf${
			nonce ? '?_wpnonce=' + nonce : ''
		}`;
		window.open( url, '_blank' );
	};

	const handleExportOSS = () => {
		const baseUrl = window.taxZenData?.restUrl || '/wp-json/taxzen/v1/';
		const nonce = window.taxZenData?.nonce || '';
		const url = `${ baseUrl }reports/oss/csv?year=${ ossYear }&quarter=${ ossQuarter }${
			nonce ? '&_wpnonce=' + nonce : ''
		}`;
		window.open( url, '_blank' );
	};

	if ( loading ) {
		return (
			<div className="taxzen-loading">
				<div className="taxzen-spinner taxzen-spinner--lg"></div>
				<span className="taxzen-loading-text">
					{ __( 'Loading dashboard…', 'taxzen-for-woocommerce' ) }
				</span>
			</div>
		);
	}

	const settings = window.taxZenData?.settings || {};
	const wizardCompleted = settings.wizard_completed;

	// Show wizard prompt if not completed.
	if ( ! wizardCompleted ) {
		return (
			<div className="taxzen-empty">
				<div className="taxzen-empty-icon">🧙</div>
				<h2 className="taxzen-empty-title">
					{ __( 'Welcome to TaxZen!', 'taxzen-for-woocommerce' ) }
				</h2>
				<p className="taxzen-empty-message">
					{ __(
						'Run the setup wizard to configure your tax rates.',
						'taxzen-for-woocommerce'
					) }
				</p>
				<a
					href={
						( window.taxZenData?.adminUrl || '/wp-admin/' ) +
						'admin.php?page=taxzen-wizard'
					}
					className="taxzen-btn taxzen-btn--primary taxzen-btn--lg"
					style={ { marginTop: 'var(--tw-space-4)' } }
				>
					{ __( 'Start Setup Wizard →', 'taxzen-for-woocommerce' ) }
				</a>
			</div>
		);
	}

	return (
		<div className="taxzen-dashboard">
			{ /* Stats Grid */ }
			<div className="taxzen-stats-grid">
				<div className="taxzen-stat-card">
					<div className="taxzen-stat-label">
						{ __( 'Total Rates', 'taxzen-for-woocommerce' ) }
					</div>
					<div className="taxzen-stat-value">
						{ stats?.total_rates || 0 }
					</div>
					<div className="taxzen-stat-meta">
						{ __( 'Active tax rates', 'taxzen-for-woocommerce' ) }
					</div>
				</div>
				<div className="taxzen-stat-card">
					<div className="taxzen-stat-label">
						{ __( 'Countries', 'taxzen-for-woocommerce' ) }
					</div>
					<div className="taxzen-stat-value">
						{ stats?.total_countries || 0 }
					</div>
					<div className="taxzen-stat-meta">
						{ __(
							'Countries configured',
							'taxzen-for-woocommerce'
						) }
					</div>
				</div>
				<div className="taxzen-stat-card">
					<div className="taxzen-stat-label">
						{ __( 'Alerts', 'taxzen-for-woocommerce' ) }
					</div>
					<div className="taxzen-stat-value">{ unreadCount }</div>
					<div className="taxzen-stat-meta">
						{ __(
							'Unread notifications',
							'taxzen-for-woocommerce'
						) }
					</div>
				</div>
				<div className="taxzen-stat-card">
					<div className="taxzen-stat-label">
						{ __( 'Last Updated', 'taxzen-for-woocommerce' ) }
					</div>
					<div
						className="taxzen-stat-value"
						style={ { fontSize: 'var(--tw-font-size-sm)' } }
					>
						{ stats?.last_update
							? new Date( stats.last_update ).toLocaleDateString()
							: '—' }
					</div>
					<div className="taxzen-stat-meta">
						{ __(
							'Rate data refresh',
							'taxzen-for-woocommerce'
						) }
					</div>
				</div>
			</div>

			{ /* Action buttons */ }
			<div className="taxzen-dashboard-actions">
				<button
					className="taxzen-btn taxzen-btn--primary"
					onClick={ handleRefresh }
					disabled={ refreshing }
				>
					{ refreshing
						? __( 'Refreshing…', 'taxzen-for-woocommerce' )
						: __( '↻ Refresh Rates', 'taxzen-for-woocommerce' ) }
				</button>
				<button
					className="taxzen-btn taxzen-btn--outline"
					onClick={ handleExportCSV }
				>
					{ __( '📄 Export CSV', 'taxzen-for-woocommerce' ) }
				</button>
				<button
					className="taxzen-btn taxzen-btn--outline"
					onClick={ handleExportPDF }
				>
					{ __( '📥 Export PDF', 'taxzen-for-woocommerce' ) }
				</button>
				<a
					href={
						( window.taxZenData?.adminUrl || '/wp-admin/' ) +
						'admin.php?page=taxzen-wizard&restart=1'
					}
					className="taxzen-btn taxzen-btn--secondary"
				>
					{ __( '🧙 Re-run Wizard', 'taxzen-for-woocommerce' ) }
				</a>
			</div>

			{ /* OSS Report Generator */ }
			<div
				className="taxzen-card"
				style={ { marginBottom: 'var(--tw-space-6)' } }
			>
				<div className="taxzen-card-header">
					<h3 className="taxzen-card-title">
						🇪🇺{ ' ' }
						{ __(
							'EU OSS/MOSS Report Generator',
							'taxzen-for-woocommerce'
						) }
					</h3>
				</div>
				<div
					style={ {
						padding: 'var(--tw-space-4)',
						display: 'flex',
						gap: 'var(--tw-space-4)',
						alignItems: 'center',
					} }
				>
					<select
						value={ ossYear }
						onChange={ ( e ) => setOssYear( e.target.value ) }
						style={ {
							padding: '8px 32px 8px 12px',
							borderRadius: '4px',
							border: '1px solid #ccc',
						} }
					>
						{ [ 0, 1, 2, 3 ].map( ( offset ) => (
							<option
								key={ currentYear - offset }
								value={ currentYear - offset }
							>
								{ currentYear - offset }
							</option>
						) ) }
					</select>
					<select
						value={ ossQuarter }
						onChange={ ( e ) => setOssQuarter( e.target.value ) }
						style={ {
							padding: '8px 32px 8px 12px',
							borderRadius: '4px',
							border: '1px solid #ccc',
						} }
					>
						<option value="1">
							{ __(
								'Q1 (Jan - Mar)',
								'taxzen-for-woocommerce'
							) }
						</option>
						<option value="2">
							{ __(
								'Q2 (Apr - Jun)',
								'taxzen-for-woocommerce'
							) }
						</option>
						<option value="3">
							{ __(
								'Q3 (Jul - Sep)',
								'taxzen-for-woocommerce'
							) }
						</option>
						<option value="4">
							{ __(
								'Q4 (Oct - Dec)',
								'taxzen-for-woocommerce'
							) }
						</option>
					</select>
					<button
						className="taxzen-btn taxzen-btn--primary"
						onClick={ handleExportOSS }
					>
						{ __(
							'📥 Export OSS CSV',
							'taxzen-for-woocommerce'
						) }
					</button>
				</div>
				<p
					style={ {
						margin: '0 var(--tw-space-4) var(--tw-space-4)',
						fontSize: '13px',
						color: '#666',
					} }
				>
					{ __(
						'Automatically aggregates non-B2B WooCommerce orders shipped to EU member states by destination country and tax rate.',
						'taxzen-for-woocommerce'
					) }
				</p>
			</div>

			{ /* Rates table */ }
			<div className="taxzen-card">
				<div className="taxzen-card-header">
					<h3 className="taxzen-card-title">
						{ __(
							'Current Tax Rates',
							'taxzen-for-woocommerce'
						) }
					</h3>
					<span className="taxzen-badge taxzen-badge--info">
						{ rates.length }{ ' ' }
						{ __( 'rates', 'taxzen-for-woocommerce' ) }
					</span>
				</div>
				{ rates.length > 0 ? (
					<div className="taxzen-table-scrollable">
						<table className="taxzen-table">
							<thead>
								<tr>
									<th>
										{ __(
											'Country',
											'taxzen-for-woocommerce'
										) }
									</th>
									<th>
										{ __(
											'State',
											'taxzen-for-woocommerce'
										) }
									</th>
									<th>
										{ __(
											'Rate',
											'taxzen-for-woocommerce'
										) }
									</th>
									<th>
										{ __(
											'Name',
											'taxzen-for-woocommerce'
										) }
									</th>
									<th>
										{ __(
											'Type',
											'taxzen-for-woocommerce'
										) }
									</th>
									<th>
										{ __(
											'Source',
											'taxzen-for-woocommerce'
										) }
									</th>
								</tr>
							</thead>
							<tbody>
								{ rates.map( ( rate ) => (
									<tr key={ rate.id }>
										<td>
											<strong>
												{ rate.country_code }
											</strong>
											{ COUNTRIES[
												rate.country_code
											] && (
												<span
													style={ {
														color: 'var(--tw-gray-400)',
														marginLeft: '4px',
														fontSize:
															'var(--tw-font-size-xs)',
													} }
												>
													{
														COUNTRIES[
															rate.country_code
														]
													}
												</span>
											) }
										</td>
										<td>{ rate.state || '—' }</td>
										<td>
											<strong>
												{ parseFloat(
													rate.rate
												).toFixed( 2 ) }
												%
											</strong>
										</td>
										<td>{ rate.rate_name }</td>
										<td>
											<span
												className={ `taxzen-badge taxzen-badge--${
													rate.rate_type ===
													'standard'
														? 'success'
														: 'info'
												}` }
											>
												{ rate.rate_type }
											</span>
										</td>
										<td>
											<span
												className={ `taxzen-badge taxzen-badge--${
													rate.source === 'static'
														? 'warning'
														: 'success'
												}` }
											>
												{ rate.source }
											</span>
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</div>
				) : (
					<div className="taxzen-empty">
						<p className="taxzen-empty-message">
							{ __(
								'No tax rates configured yet.',
								'taxzen-for-woocommerce'
							) }
						</p>
					</div>
				) }
			</div>

			{ /* Alerts */ }
			<div className="taxzen-card">
				<div className="taxzen-card-header">
					<h3 className="taxzen-card-title">
						{ __( 'Recent Alerts', 'taxzen-for-woocommerce' ) }
						{ unreadCount > 0 && (
							<span
								className="taxzen-badge taxzen-badge--danger"
								style={ { marginLeft: '8px' } }
							>
								{ unreadCount }
							</span>
						) }
					</h3>
					{ unreadCount > 0 && (
						<button
							className="taxzen-btn taxzen-btn--secondary"
							onClick={ handleMarkAllRead }
							style={ { fontSize: 'var(--tw-font-size-xs)' } }
						>
							{ __(
								'Mark all read',
								'taxzen-for-woocommerce'
							) }
						</button>
					) }
				</div>
				{ alerts.length > 0 ? (
					<div>
						{ alerts.map( ( alert ) => (
							<div
								key={ alert.id }
								className="taxzen-alert-item"
								style={
									alert.is_read === '0'
										? { background: 'var(--tw-primary-50)' }
										: {}
								}
							>
								<div
									className={ `taxzen-alert-icon taxzen-alert-icon--${ alert.severity }` }
								>
									{ getSeverityIcon( alert.severity ) }
								</div>
								<div className="taxzen-alert-content">
									<h4 className="taxzen-alert-title">
										{ alert.title }
									</h4>
									<p className="taxzen-alert-message">
										{ alert.message }
									</p>
								</div>
								<span className="taxzen-alert-time">
									{ new Date(
										alert.created_at
									).toLocaleDateString() }
								</span>
							</div>
						) ) }
					</div>
				) : (
					<div
						className="taxzen-empty"
						style={ { padding: 'var(--tw-space-6)' } }
					>
						<p className="taxzen-empty-message">
							{ __(
								'No alerts. Everything looks good! ✅',
								'taxzen-for-woocommerce'
							) }
						</p>
					</div>
				) }
			</div>
		</div>
	);
}
