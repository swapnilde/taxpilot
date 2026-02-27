/**
 * Step 3: Target Countries selector.
 */
import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { apiPost } from '../../common/api';
import { COUNTRIES, REGION_PRESETS } from '../../common/constants';

export default function TargetCountries( {
	data,
	updateData,
	onNext,
	onBack,
	showNotice,
} ) {
	const [ saving, setSaving ] = useState( false );
	const [ search, setSearch ] = useState( '' );

	const selected = data.targetCountries || [];

	const toggleCountry = ( code ) => {
		const updated = selected.includes( code )
			? selected.filter( ( c ) => c !== code )
			: [ ...selected, code ];
		updateData( 'targetCountries', updated );
	};

	const toggleRegion = ( regionKey ) => {
		const preset = REGION_PRESETS[ regionKey ];
		if ( ! preset ) {
			return;
		}

		const allSelected = preset.countries.every( ( c ) =>
			selected.includes( c )
		);
		if ( allSelected ) {
			// Deselect all in this region.
			const updated = selected.filter(
				( c ) => ! preset.countries.includes( c )
			);
			updateData( 'targetCountries', updated );
		} else {
			// Select all in this region.
			const updated = [
				...new Set( [ ...selected, ...preset.countries ] ),
			];
			updateData( 'targetCountries', updated );
		}
	};

	const selectAll = () => {
		updateData( 'targetCountries', Object.keys( COUNTRIES ) );
	};

	const clearAll = () => {
		updateData( 'targetCountries', [] );
	};

	const filteredCountries = useMemo( () => {
		const term = search.toLowerCase();
		return Object.entries( COUNTRIES ).filter(
			( [ code, name ] ) =>
				name.toLowerCase().includes( term ) ||
				code.toLowerCase().includes( term )
		);
	}, [ search ] );

	const handleNext = async () => {
		if ( ! selected.length ) {
			showNotice(
				__( 'Please select at least one country.', 'taxpilot' ),
				'error'
			);
			return;
		}

		setSaving( true );
		try {
			await apiPost( 'wizard/target-countries', { countries: selected } );
			showNotice(
				`${ selected.length } ${ __(
					'countries selected!',
					'taxpilot'
				) }`
			);
			onNext();
		} catch {
			showNotice(
				__( 'Failed to save countries.', 'taxpilot' ),
				'error'
			);
		} finally {
			setSaving( false );
		}
	};

	return (
		<div>
			<h2>{ __( 'Target Countries', 'taxpilot' ) }</h2>
			<p className="description">
				{ __(
					"Select the countries where you sell. We'll fetch the correct tax rates for each one.",
					'taxpilot'
				) }
			</p>

			{ /* Region presets */ }
			<div className="taxpilot-region-presets">
				{ Object.entries( REGION_PRESETS ).map( ( [ key, preset ] ) => (
					<button
						key={ key }
						className="taxpilot-region-btn"
						onClick={ () => toggleRegion( key ) }
					>
						{ preset.label }
					</button>
				) ) }
				<button className="taxpilot-region-btn" onClick={ selectAll }>
					{ __( 'Select All', 'taxpilot' ) }
				</button>
				<button className="taxpilot-region-btn" onClick={ clearAll }>
					{ __( 'Clear All', 'taxpilot' ) }
				</button>
			</div>

			{ /* Search */ }
			<input
				type="text"
				className="taxpilot-search"
				placeholder={ __( 'Search countries…', 'taxpilot' ) }
				value={ search }
				onChange={ ( e ) => setSearch( e.target.value ) }
			/>

			{ /* Country grid */ }
			<div className="taxpilot-country-grid">
				{ filteredCountries.map( ( [ code, name ] ) => (
					<div
						key={ code }
						className={ `taxpilot-country-item${
							selected.includes( code )
								? ' taxpilot-country-item--selected'
								: ''
						}` }
						onClick={ () => toggleCountry( code ) }
						onKeyDown={ ( e ) =>
							e.key === 'Enter' && toggleCountry( code )
						}
						role="checkbox"
						aria-checked={ selected.includes( code ) }
						tabIndex={ 0 }
					>
						<span>{ selected.includes( code ) ? '☑' : '☐' }</span>
						<span>{ name }</span>
					</div>
				) ) }
			</div>

			<p
				style={ {
					marginTop: 'var(--tw-space-3)',
					fontSize: 'var(--tw-font-size-sm)',
					color: 'var(--tw-gray-500)',
				} }
			>
				{ selected.length } { __( 'countries selected', 'taxpilot' ) }
			</p>

			<div className="taxpilot-step-actions">
				<button
					className="taxpilot-btn taxpilot-btn--secondary"
					onClick={ onBack }
				>
					{ __( '← Back', 'taxpilot' ) }
				</button>
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
