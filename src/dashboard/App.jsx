/**
 * TaxPilot for WooCommerce — Dashboard App.
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
		const baseUrl = window.taxPilotData?.restUrl || '/wp-json/taxpilot/v1/';
		const nonce = window.taxPilotData?.nonce || '';
		const url = `${ baseUrl }reports/csv${
			nonce ? '?_wpnonce=' + nonce : ''
		}`;
		window.open( url, '_blank' );
	};

	const handleExportPDF = () => {
		const baseUrl = window.taxPilotData?.restUrl || '/wp-json/taxpilot/v1/';
		const nonce = window.taxPilotData?.nonce || '';
		const url = `${ baseUrl }reports/pdf${
			nonce ? '?_wpnonce=' + nonce : ''
		}`;
		window.open( url, '_blank' );
	};

	const handleExportOSS = () => {
		const baseUrl = window.taxPilotData?.restUrl || '/wp-json/taxpilot/v1/';
		const nonce = window.taxPilotData?.nonce || '';
		const url = `${ baseUrl }reports/oss/csv?year=${ ossYear }&quarter=${ ossQuarter }${
			nonce ? '&_wpnonce=' + nonce : ''
		}`;
		window.open( url, '_blank' );
	};

	if ( loading ) {
		return (
			<div className="taxpilot-loading">
				<div className="taxpilot-spinner taxpilot-spinner--lg"></div>
				<span className="taxpilot-loading-text">
					{ __( 'Loading dashboard…', 'taxpilot' ) }
				</span>
			</div>
		);
	}

	const settings = window.taxPilotData?.settings || {};
	const wizardCompleted = settings.wizard_completed;

	// Show wizard prompt if not completed.
	if ( ! wizardCompleted ) {
		return (
			<div className="taxpilot-empty">
				<div className="taxpilot-empty-icon">🧙</div>
				<h2 className="taxpilot-empty-title">
					{ __( 'Welcome to TaxPilot!', 'taxpilot' ) }
				</h2>
				<p className="taxpilot-empty-message">
					{ __(
						'Run the setup wizard to configure your tax rates.',
						'taxpilot'
					) }
				</p>
				<a
					href={
						( window.taxPilotData?.adminUrl || '/wp-admin/' ) +
						'admin.php?page=taxpilot-wizard'
					}
					className="taxpilot-btn taxpilot-btn--primary taxpilot-btn--lg"
					style={ { marginTop: 'var(--tw-space-4)' } }
				>
					{ __( 'Start Setup Wizard →', 'taxpilot' ) }
				</a>
			</div>
		);
	}

	return (
		<div className="taxpilot-dashboard">
			{ /* Stats Grid */ }
			<div className="taxpilot-stats-grid">
				<div className="taxpilot-stat-card">
					<div className="taxpilot-stat-label">
						{ __( 'Total Rates', 'taxpilot' ) }
					</div>
					<div className="taxpilot-stat-value">
						{ stats?.total_rates || 0 }
					</div>
					<div className="taxpilot-stat-meta">
						{ __( 'Active tax rates', 'taxpilot' ) }
					</div>
				</div>
				<div className="taxpilot-stat-card">
					<div className="taxpilot-stat-label">
						{ __( 'Countries', 'taxpilot' ) }
					</div>
					<div className="taxpilot-stat-value">
						{ stats?.total_countries || 0 }
					</div>
					<div className="taxpilot-stat-meta">
						{ __( 'Countries configured', 'taxpilot' ) }
					</div>
				</div>
				<div className="taxpilot-stat-card">
					<div className="taxpilot-stat-label">
						{ __( 'Alerts', 'taxpilot' ) }
					</div>
					<div className="taxpilot-stat-value">{ unreadCount }</div>
					<div className="taxpilot-stat-meta">
						{ __( 'Unread notifications', 'taxpilot' ) }
					</div>
				</div>
				<div className="taxpilot-stat-card">
					<div className="taxpilot-stat-label">
						{ __( 'Last Updated', 'taxpilot' ) }
					</div>
					<div
						className="taxpilot-stat-value"
						style={ { fontSize: 'var(--tw-font-size-sm)' } }
					>
						{ stats?.last_update
							? new Date( stats.last_update ).toLocaleDateString()
							: '—' }
					</div>
					<div className="taxpilot-stat-meta">
						{ __( 'Rate data refresh', 'taxpilot' ) }
					</div>
				</div>
			</div>

			{ /* Action buttons */ }
			<div className="taxpilot-dashboard-actions">
				<button
					className="taxpilot-btn taxpilot-btn--primary"
					onClick={ handleRefresh }
					disabled={ refreshing }
				>
					{ refreshing
						? __( 'Refreshing…', 'taxpilot' )
						: __( '↻ Refresh Rates', 'taxpilot' ) }
				</button>
				<button
					className="taxpilot-btn taxpilot-btn--outline"
					onClick={ handleExportCSV }
				>
					{ __( '📄 Export CSV', 'taxpilot' ) }
				</button>
				<button
					className="taxpilot-btn taxpilot-btn--outline"
					onClick={ handleExportPDF }
				>
					{ __( '📥 Export PDF', 'taxpilot' ) }
				</button>
				<a
					href={
						( window.taxPilotData?.adminUrl || '/wp-admin/' ) +
						'admin.php?page=taxpilot-wizard&restart=1'
					}
					className="taxpilot-btn taxpilot-btn--secondary"
				>
					{ __( '🧙 Re-run Wizard', 'taxpilot' ) }
				</a>
			</div>

			{ /* OSS Report Generator */ }
			<div
				className="taxpilot-card"
				style={ { marginBottom: 'var(--tw-space-6)' } }
			>
				<div className="taxpilot-card-header">
					<h3 className="taxpilot-card-title">
						🇪🇺 { __( 'EU OSS/MOSS Report Generator', 'taxpilot' ) }
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
							{ __( 'Q1 (Jan - Mar)', 'taxpilot' ) }
						</option>
						<option value="2">
							{ __( 'Q2 (Apr - Jun)', 'taxpilot' ) }
						</option>
						<option value="3">
							{ __( 'Q3 (Jul - Sep)', 'taxpilot' ) }
						</option>
						<option value="4">
							{ __( 'Q4 (Oct - Dec)', 'taxpilot' ) }
						</option>
					</select>
					<button
						className="taxpilot-btn taxpilot-btn--primary"
						onClick={ handleExportOSS }
					>
						{ __( '📥 Export OSS CSV', 'taxpilot' ) }
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
						'taxpilot'
					) }
				</p>
			</div>

			{ /* Rates table */ }
			<div className="taxpilot-card">
				<div className="taxpilot-card-header">
					<h3 className="taxpilot-card-title">
						{ __( 'Current Tax Rates', 'taxpilot' ) }
					</h3>
					<span className="taxpilot-badge taxpilot-badge--info">
						{ rates.length } { __( 'rates', 'taxpilot' ) }
					</span>
				</div>
				{ rates.length > 0 ? (
					<div className="taxpilot-table-scrollable">
						<table className="taxpilot-table">
							<thead>
								<tr>
									<th>{ __( 'Country', 'taxpilot' ) }</th>
									<th>{ __( 'State', 'taxpilot' ) }</th>
									<th>{ __( 'Rate', 'taxpilot' ) }</th>
									<th>{ __( 'Name', 'taxpilot' ) }</th>
									<th>{ __( 'Type', 'taxpilot' ) }</th>
									<th>{ __( 'Source', 'taxpilot' ) }</th>
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
												className={ `taxpilot-badge taxpilot-badge--${
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
												className={ `taxpilot-badge taxpilot-badge--${
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
					<div className="taxpilot-empty">
						<p className="taxpilot-empty-message">
							{ __( 'No tax rates configured yet.', 'taxpilot' ) }
						</p>
					</div>
				) }
			</div>

			{ /* Alerts */ }
			<div className="taxpilot-card">
				<div className="taxpilot-card-header">
					<h3 className="taxpilot-card-title">
						{ __( 'Recent Alerts', 'taxpilot' ) }
						{ unreadCount > 0 && (
							<span
								className="taxpilot-badge taxpilot-badge--danger"
								style={ { marginLeft: '8px' } }
							>
								{ unreadCount }
							</span>
						) }
					</h3>
					{ unreadCount > 0 && (
						<button
							className="taxpilot-btn taxpilot-btn--secondary"
							onClick={ handleMarkAllRead }
							style={ { fontSize: 'var(--tw-font-size-xs)' } }
						>
							{ __( 'Mark all read', 'taxpilot' ) }
						</button>
					) }
				</div>
				{ alerts.length > 0 ? (
					<div>
						{ alerts.map( ( alert ) => (
							<div
								key={ alert.id }
								className="taxpilot-alert-item"
								style={
									alert.is_read === '0'
										? { background: 'var(--tw-primary-50)' }
										: {}
								}
							>
								<div
									className={ `taxpilot-alert-icon taxpilot-alert-icon--${ alert.severity }` }
								>
									{ getSeverityIcon( alert.severity ) }
								</div>
								<div className="taxpilot-alert-content">
									<h4 className="taxpilot-alert-title">
										{ alert.title }
									</h4>
									<p className="taxpilot-alert-message">
										{ alert.message }
									</p>
								</div>
								<span className="taxpilot-alert-time">
									{ new Date(
										alert.created_at
									).toLocaleDateString() }
								</span>
							</div>
						) ) }
					</div>
				) : (
					<div
						className="taxpilot-empty"
						style={ { padding: 'var(--tw-space-6)' } }
					>
						<p className="taxpilot-empty-message">
							{ __(
								'No alerts. Everything looks good! ✅',
								'taxpilot'
							) }
						</p>
					</div>
				) }
			</div>
		</div>
	);
}
