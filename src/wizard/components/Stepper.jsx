/**
 * Stepper component — visual step indicator with clickable navigation.
 */
import { __ } from '@wordpress/i18n';
import { WIZARD_STEPS } from '../../common/constants';

export default function Stepper( {
	steps = WIZARD_STEPS,
	currentStep = 0,
	onStepClick,
} ) {
	return (
		<div className="taxzen-stepper">
			{ steps.map( ( step, index ) => {
				let status = 'inactive';
				if ( index < currentStep ) {
					status = 'completed';
				}
				if ( index === currentStep ) {
					status = 'active';
				}

				const isClickable = onStepClick && index < currentStep;

				return (
					<div
						key={ step.key }
						className="taxzen-step-wrapper"
						style={ { display: 'flex', alignItems: 'center' } }
					>
						<div
							className={ `taxzen-step taxzen-step--${ status }${
								isClickable ? ' taxzen-step--clickable' : ''
							}` }
							onClick={
								isClickable
									? () => onStepClick( index )
									: undefined
							}
							onKeyDown={
								isClickable
									? ( e ) =>
											e.key === 'Enter' &&
											onStepClick( index )
									: undefined
							}
							role={ isClickable ? 'button' : undefined }
							tabIndex={ isClickable ? 0 : undefined }
							title={
								isClickable
									? `${ __(
											'Go back to',
											'taxzen-for-woocommerce'
									  ) } ${ step.label }`
									: undefined
							}
						>
							<span className="taxzen-step-number">
								{ status === 'completed' ? '✓' : step.number }
							</span>
							<span className="taxzen-step-label">
								{ step.label }
							</span>
						</div>
						{ index < steps.length - 1 && (
							<div
								className={ `taxzen-step-connector${
									index < currentStep
										? ' taxzen-step-connector--completed'
										: ''
								}` }
							/>
						) }
					</div>
				);
			} ) }
		</div>
	);
}
