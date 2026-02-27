/**
 * Step 1: Store Setup — Country & Currency.
 */
import { useState, useEffect } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { apiGet, apiPost } from '../../common/api';
import { COUNTRIES, CURRENCIES } from '../../common/constants';

export default function StoreSetup( {
	data,
	updateData,
	onNext,
	showNotice,
	isRerun,
} ) {
	const [ saving, setSaving ] = useState( false );
	const [ loading, setLoading ] = useState( true );

	// Auto-detect store setup from WooCommerce.
	useEffect( () => {
		if ( ! data.country ) {
			apiGet( 'wizard/store-setup' )
				.then( ( result ) => {
					if ( result.country ) {
						updateData( 'country', result.country );
					}
					if ( result.currency ) {
						updateData( 'currency', result.currency );
					}
				} )
				.finally( () => setLoading( false ) );
		} else {
			setLoading( false );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps -- Intentionally runs only on mount to auto-detect WC settings.
	}, [] );

	const countryOptions = [
		{ value: '', label: __( '— Select Country —', 'taxpilot' ) },
		...Object.entries( COUNTRIES ).map( ( [ code, name ] ) => ( {
			value: code,
			label: `${ name } (${ code })`,
		} ) ),
	];

	const currencyOptions = [
		{ value: '', label: __( '— Select Currency —', 'taxpilot' ) },
		...Object.entries( CURRENCIES ).map( ( [ code, name ] ) => ( {
			value: code,
			label: `${ code } — ${ name }`,
		} ) ),
	];

	const handleNext = async () => {
		if ( ! data.country || ! data.currency ) {
			showNotice(
				__( 'Please select both country and currency.', 'taxpilot' ),
				'error'
			);
			return;
		}

		setSaving( true );
		try {
			await apiPost( 'wizard/store-setup', {
				country: data.country,
				currency: data.currency,
			} );
			showNotice( __( 'Store setup saved!', 'taxpilot' ) );
			onNext();
		} catch {
			showNotice(
				__( 'Failed to save store setup.', 'taxpilot' ),
				'error'
			);
		} finally {
			setSaving( false );
		}
	};

	if ( loading ) {
		return (
			<div className="taxpilot-loading">
				<div className="taxpilot-spinner"></div>
				<span className="taxpilot-loading-text">
					{ __( 'Detecting store settings…', 'taxpilot' ) }
				</span>
			</div>
		);
	}

	return (
		<div>
			<h2>
				{ isRerun
					? __( 'Update Store Setup', 'taxpilot' )
					: __( 'Store Setup', 'taxpilot' ) }
			</h2>
			<p className="description">
				{ isRerun
					? __(
							'Your current settings are pre-filled below. Update anything you need and continue through the wizard.',
							'taxpilot'
					  )
					: __(
							'Tell us about your store location and currency. We auto-detected your WooCommerce settings.',
							'taxpilot'
					  ) }
			</p>

			{ isRerun && (
				<div
					className="taxpilot-info-banner"
					style={ {
						background: 'var(--tw-primary-50, #eef2ff)',
						border: '1px solid var(--tw-primary-200, #c7d2fe)',
						borderRadius: 'var(--tw-radius-lg, 8px)',
						padding: 'var(--tw-space-4, 16px)',
						marginBottom: 'var(--tw-space-4, 16px)',
						fontSize: 'var(--tw-font-size-sm, 14px)',
						color: 'var(--tw-primary-700, #4338ca)',
					} }
				>
					ℹ️{ ' ' }
					{ __(
						'You are re-running the wizard. Your existing settings are pre-filled. Walk through each step to make changes, then re-apply your rates.',
						'taxpilot'
					) }
				</div>
			) }

			<div className="taxpilot-form-row">
				<div className="taxpilot-field">
					<SelectControl
						label={ __( 'Store Country', 'taxpilot' ) }
						value={ data.country }
						options={ countryOptions }
						onChange={ ( value ) => updateData( 'country', value ) }
					/>
				</div>
				<div className="taxpilot-field">
					<SelectControl
						label={ __( 'Store Currency', 'taxpilot' ) }
						value={ data.currency }
						options={ currencyOptions }
						onChange={ ( value ) =>
							updateData( 'currency', value )
						}
					/>
				</div>
			</div>

			<div className="taxpilot-step-actions">
				<div></div>
				<button
					className="taxpilot-btn taxpilot-btn--primary taxpilot-btn--lg"
					onClick={ handleNext }
					disabled={ saving }
				>
					{ saving
						? __( 'Saving…', 'taxpilot' )
						: __( 'Continue →', 'taxpilot' ) }
				</button>
			</div>
		</div>
	);
}
