/**
 * TaxPilot for WooCommerce — Wizard App.
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { Snackbar } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { WIZARD_STEPS } from '../common/constants';
import { apiGet } from '../common/api';
import Stepper from './components/Stepper';
import StoreSetup from './steps/StoreSetup';
import ProductTypes from './steps/ProductTypes';
import TargetCountries from './steps/TargetCountries';
import PreviewRates from './steps/PreviewRates';
import ApplyRates from './steps/ApplyRates';
import './wizard.css';

export default function App() {
	const [ currentStep, setCurrentStep ] = useState( 0 );
	const [ wizardData, setWizardData ] = useState( {
		country: '',
		currency: '',
		productTypes: [],
		targetCountries: [],
		rates: [],
	} );
	const [ notice, setNotice ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ isRerun, setIsRerun ] = useState( false );

	// Check URL params for restart mode.
	const urlParams = new URLSearchParams( window.location.search );
	const forceRestart = urlParams.get( 'restart' ) === '1';

	// Load existing wizard state on mount.
	useEffect( () => {
		apiGet( 'wizard/state' )
			.then( ( state ) => {
				setWizardData( ( prev ) => ( {
					...prev,
					country: state.base_country || '',
					currency: state.base_currency || '',
					productTypes: state.product_types || [],
					targetCountries: state.target_countries || [],
				} ) );

				// If re-running (restart=1 param), always start from step 0
				// but keep the existing data pre-filled for easy editing.
				if ( forceRestart ) {
					setIsRerun( true );
					setCurrentStep( 0 );
					return;
				}

				// Otherwise resume at the furthest incomplete step.
				if ( state.wizard_completed ) {
					// If wizard was completed, show a "completed" view at step 0
					// that lets the user choose to re-run or go to dashboard.
					setIsRerun( true );
					setCurrentStep( 0 );
				} else if ( state.target_countries?.length > 0 ) {
					setCurrentStep( 3 );
				} else if ( state.product_types?.length > 0 ) {
					setCurrentStep( 2 );
				} else if ( state.base_country ) {
					setCurrentStep( 1 );
				}
			} )
			.catch( () => {
				setNotice( {
					message: __( 'Failed to load wizard state.', 'taxpilot' ),
					type: 'error',
				} );
			} )
			.finally( () => setLoading( false ) );
	}, [ forceRestart ] );

	const updateData = useCallback( ( key, value ) => {
		setWizardData( ( prev ) => ( { ...prev, [ key ]: value } ) );
	}, [] );

	const goNext = useCallback( () => {
		if ( currentStep < WIZARD_STEPS.length - 1 ) {
			setCurrentStep( ( prev ) => prev + 1 );
		}
	}, [ currentStep ] );

	const goBack = useCallback( () => {
		if ( currentStep > 0 ) {
			setCurrentStep( ( prev ) => prev - 1 );
		}
	}, [ currentStep ] );

	const goToStep = useCallback(
		( stepIndex ) => {
			// Allow navigating to any completed or current step.
			if ( stepIndex >= 0 && stepIndex <= currentStep ) {
				setCurrentStep( stepIndex );
			}
		},
		[ currentStep ]
	);

	const showNotice = useCallback( ( message, type = 'success' ) => {
		setNotice( { message, type } );
	}, [] );

	const handleStartOver = useCallback( () => {
		setCurrentStep( 0 );
		setWizardData( {
			country: '',
			currency: '',
			productTypes: [],
			targetCountries: [],
			rates: [],
		} );
		setIsRerun( false );
		showNotice( __( 'Wizard reset! Starting fresh.', 'taxpilot' ) );
	}, [ showNotice ] );

	const renderStep = () => {
		switch ( currentStep ) {
			case 0:
				return (
					<StoreSetup
						data={ wizardData }
						updateData={ updateData }
						onNext={ goNext }
						showNotice={ showNotice }
						isRerun={ isRerun }
					/>
				);
			case 1:
				return (
					<ProductTypes
						data={ wizardData }
						updateData={ updateData }
						onNext={ goNext }
						onBack={ goBack }
						showNotice={ showNotice }
					/>
				);
			case 2:
				return (
					<TargetCountries
						data={ wizardData }
						updateData={ updateData }
						onNext={ goNext }
						onBack={ goBack }
						showNotice={ showNotice }
					/>
				);
			case 3:
				return (
					<PreviewRates
						data={ wizardData }
						updateData={ updateData }
						onNext={ goNext }
						onBack={ goBack }
						showNotice={ showNotice }
					/>
				);
			case 4:
				return (
					<ApplyRates
						data={ wizardData }
						onBack={ goBack }
						showNotice={ showNotice }
						onStartOver={ handleStartOver }
					/>
				);
			default:
				return null;
		}
	};

	if ( loading ) {
		return (
			<div className="taxpilot-loading">
				<div className="taxpilot-spinner taxpilot-spinner--lg"></div>
				<span className="taxpilot-loading-text">
					{ __( 'Loading wizard…', 'taxpilot' ) }
				</span>
			</div>
		);
	}

	return (
		<div className="taxpilot-wizard">
			<Stepper
				steps={ WIZARD_STEPS }
				currentStep={ currentStep }
				onStepClick={ goToStep }
			/>
			<div className="taxpilot-step-content">{ renderStep() }</div>
			{ notice && (
				<Snackbar onRemove={ () => setNotice( null ) }>
					{ notice.message }
				</Snackbar>
			) }
		</div>
	);
}
