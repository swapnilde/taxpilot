/**
 * Step 2: Product Types selector.
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { apiPost } from '../../common/api';
import { PRODUCT_TYPES } from '../../common/constants';

export default function ProductTypes( {
	data,
	updateData,
	onNext,
	onBack,
	showNotice,
} ) {
	const [ saving, setSaving ] = useState( false );

	const toggleType = ( type ) => {
		const current = data.productTypes || [];
		const updated = current.includes( type )
			? current.filter( ( t ) => t !== type )
			: [ ...current, type ];
		updateData( 'productTypes', updated );
	};

	const handleNext = async () => {
		if ( ! data.productTypes?.length ) {
			showNotice(
				__( 'Please select at least one product type.', 'taxpilot' ),
				'error'
			);
			return;
		}

		setSaving( true );
		try {
			const result = await apiPost( 'wizard/product-types', {
				product_types: data.productTypes,
			} );
			showNotice(
				`${ __(
					'Product types saved! Tax classes created:',
					'taxpilot'
				) } ${ result.tax_classes?.join( ', ' ) || 'Standard' }`
			);
			onNext();
		} catch {
			showNotice(
				__( 'Failed to save product types.', 'taxpilot' ),
				'error'
			);
		} finally {
			setSaving( false );
		}
	};

	return (
		<div>
			<h2>{ __( 'Product Types', 'taxpilot' ) }</h2>
			<p className="description">
				{ __(
					"Select all product types you sell. We'll create the appropriate WooCommerce tax classes.",
					'taxpilot'
				) }
			</p>

			<div className="taxpilot-product-type-cards">
				{ PRODUCT_TYPES.map( ( type ) => (
					<div
						key={ type.value }
						className={ `taxpilot-product-type-card${
							data.productTypes?.includes( type.value )
								? ' taxpilot-product-type-card--selected'
								: ''
						}` }
						onClick={ () => toggleType( type.value ) }
						onKeyDown={ ( e ) =>
							e.key === 'Enter' && toggleType( type.value )
						}
						role="checkbox"
						aria-checked={ data.productTypes?.includes(
							type.value
						) }
						tabIndex={ 0 }
					>
						<h3>{ type.label }</h3>
						<p>{ type.description }</p>
					</div>
				) ) }
			</div>

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
