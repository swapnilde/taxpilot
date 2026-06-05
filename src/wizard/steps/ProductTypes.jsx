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
				__(
					'Please select at least one product type.',
					'taxzen-for-woocommerce'
				),
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
					'taxzen-for-woocommerce'
				) } ${ result.tax_classes?.join( ', ' ) || 'Standard' }`
			);
			onNext();
		} catch {
			showNotice(
				__( 'Failed to save product types.', 'taxzen-for-woocommerce' ),
				'error'
			);
		} finally {
			setSaving( false );
		}
	};

	return (
		<div>
			<h2>{ __( 'Product Types', 'taxzen-for-woocommerce' ) }</h2>
			<p className="description">
				{ __(
					"Select all product types you sell. We'll create the appropriate WooCommerce tax classes.",
					'taxzen-for-woocommerce'
				) }
			</p>

			<div className="taxzen-product-type-cards">
				{ PRODUCT_TYPES.map( ( type ) => (
					<div
						key={ type.value }
						className={ `taxzen-product-type-card${
							data.productTypes?.includes( type.value )
								? ' taxzen-product-type-card--selected'
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

			<div className="taxzen-step-actions">
				<button
					className="taxzen-btn taxzen-btn--secondary"
					onClick={ onBack }
				>
					{ __( '← Back', 'taxzen-for-woocommerce' ) }
				</button>
				<button
					className="taxzen-btn taxzen-btn--primary taxzen-btn--lg"
					onClick={ handleNext }
					disabled={ saving }
				>
					{ saving
						? __( 'Saving…', 'taxzen-for-woocommerce' )
						: __( 'Continue →', 'taxzen-for-woocommerce' ) }
				</button>
			</div>
		</div>
	);
}
