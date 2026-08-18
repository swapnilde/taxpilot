/**
 * TaxZen for WooCommerce — Shared constants.
 */

import { __ } from '@wordpress/i18n';

export const REST_NAMESPACE = 'taxzen/v1';

export const WIZARD_STEPS = [
	{
		key: 'store-setup',
		label: __( 'Store Setup', 'taxzen-for-woocommerce' ),
		number: 1,
	},
	{
		key: 'product-types',
		label: __( 'Product Types', 'taxzen-for-woocommerce' ),
		number: 2,
	},
	{
		key: 'target-countries',
		label: __( 'Countries', 'taxzen-for-woocommerce' ),
		number: 3,
	},
	{
		key: 'preview-rates',
		label: __( 'Preview Rates', 'taxzen-for-woocommerce' ),
		number: 4,
	},
	{
		key: 'apply-rates',
		label: __( 'Apply', 'taxzen-for-woocommerce' ),
		number: 5,
	},
];

export const PRODUCT_TYPES = [
	{
		value: 'physical',
		label: __( 'Physical Goods', 'taxzen-for-woocommerce' ),
		description: __(
			'Tangible products shipped to customers.',
			'taxzen-for-woocommerce'
		),
	},
	{
		value: 'digital',
		label: __( 'Digital Goods', 'taxzen-for-woocommerce' ),
		description: __(
			'Downloads, software, e-books, online courses.',
			'taxzen-for-woocommerce'
		),
	},
	{
		value: 'services',
		label: __( 'Services', 'taxzen-for-woocommerce' ),
		description: __(
			'Consulting, freelancing, SaaS subscriptions.',
			'taxzen-for-woocommerce'
		),
	},
];

export const REGION_PRESETS = {
	eu: {
		label: __( 'European Union', 'taxzen-for-woocommerce' ),
		countries: [
			'AT',
			'BE',
			'BG',
			'HR',
			'CY',
			'CZ',
			'DK',
			'EE',
			'FI',
			'FR',
			'DE',
			'GR',
			'HU',
			'IE',
			'IT',
			'LV',
			'LT',
			'LU',
			'MT',
			'NL',
			'PL',
			'PT',
			'RO',
			'SK',
			'SI',
			'ES',
			'SE',
		],
	},
	north_america: {
		label: __( 'North America', 'taxzen-for-woocommerce' ),
		countries: [ 'US', 'CA', 'MX' ],
	},
	apac: {
		label: __( 'Asia-Pacific', 'taxzen-for-woocommerce' ),
		countries: [ 'AU', 'NZ', 'JP', 'SG', 'IN', 'KR', 'TH', 'MY', 'PH' ],
	},
	uk: {
		label: __( 'United Kingdom', 'taxzen-for-woocommerce' ),
		countries: [ 'GB' ],
	},
};

export const COUNTRIES = {
	US: __( 'United States', 'taxzen-for-woocommerce' ),
	CA: __( 'Canada', 'taxzen-for-woocommerce' ),
	MX: __( 'Mexico', 'taxzen-for-woocommerce' ),
	GB: __( 'United Kingdom', 'taxzen-for-woocommerce' ),
	DE: __( 'Germany', 'taxzen-for-woocommerce' ),
	FR: __( 'France', 'taxzen-for-woocommerce' ),
	IT: __( 'Italy', 'taxzen-for-woocommerce' ),
	ES: __( 'Spain', 'taxzen-for-woocommerce' ),
	NL: __( 'Netherlands', 'taxzen-for-woocommerce' ),
	BE: __( 'Belgium', 'taxzen-for-woocommerce' ),
	AT: __( 'Austria', 'taxzen-for-woocommerce' ),
	PT: __( 'Portugal', 'taxzen-for-woocommerce' ),
	IE: __( 'Ireland', 'taxzen-for-woocommerce' ),
	SE: __( 'Sweden', 'taxzen-for-woocommerce' ),
	DK: __( 'Denmark', 'taxzen-for-woocommerce' ),
	FI: __( 'Finland', 'taxzen-for-woocommerce' ),
	PL: __( 'Poland', 'taxzen-for-woocommerce' ),
	CZ: __( 'Czech Republic', 'taxzen-for-woocommerce' ),
	RO: __( 'Romania', 'taxzen-for-woocommerce' ),
	HU: __( 'Hungary', 'taxzen-for-woocommerce' ),
	GR: __( 'Greece', 'taxzen-for-woocommerce' ),
	BG: __( 'Bulgaria', 'taxzen-for-woocommerce' ),
	HR: __( 'Croatia', 'taxzen-for-woocommerce' ),
	SK: __( 'Slovakia', 'taxzen-for-woocommerce' ),
	SI: __( 'Slovenia', 'taxzen-for-woocommerce' ),
	LT: __( 'Lithuania', 'taxzen-for-woocommerce' ),
	LV: __( 'Latvia', 'taxzen-for-woocommerce' ),
	EE: __( 'Estonia', 'taxzen-for-woocommerce' ),
	LU: __( 'Luxembourg', 'taxzen-for-woocommerce' ),
	MT: __( 'Malta', 'taxzen-for-woocommerce' ),
	CY: __( 'Cyprus', 'taxzen-for-woocommerce' ),
	AU: __( 'Australia', 'taxzen-for-woocommerce' ),
	NZ: __( 'New Zealand', 'taxzen-for-woocommerce' ),
	JP: __( 'Japan', 'taxzen-for-woocommerce' ),
	IN: __( 'India', 'taxzen-for-woocommerce' ),
	SG: __( 'Singapore', 'taxzen-for-woocommerce' ),
	KR: __( 'South Korea', 'taxzen-for-woocommerce' ),
	TH: __( 'Thailand', 'taxzen-for-woocommerce' ),
	MY: __( 'Malaysia', 'taxzen-for-woocommerce' ),
	PH: __( 'Philippines', 'taxzen-for-woocommerce' ),
	CH: __( 'Switzerland', 'taxzen-for-woocommerce' ),
	NO: __( 'Norway', 'taxzen-for-woocommerce' ),
	IS: __( 'Iceland', 'taxzen-for-woocommerce' ),
	ZA: __( 'South Africa', 'taxzen-for-woocommerce' ),
	BR: __( 'Brazil', 'taxzen-for-woocommerce' ),
	TR: __( 'Turkey', 'taxzen-for-woocommerce' ),
	AE: __( 'United Arab Emirates', 'taxzen-for-woocommerce' ),
	SA: __( 'Saudi Arabia', 'taxzen-for-woocommerce' ),
};

export const CURRENCIES = {
	USD: __( 'US Dollar', 'taxzen-for-woocommerce' ),
	EUR: __( 'Euro', 'taxzen-for-woocommerce' ),
	GBP: __( 'British Pound', 'taxzen-for-woocommerce' ),
	CAD: __( 'Canadian Dollar', 'taxzen-for-woocommerce' ),
	AUD: __( 'Australian Dollar', 'taxzen-for-woocommerce' ),
	NZD: __( 'New Zealand Dollar', 'taxzen-for-woocommerce' ),
	JPY: __( 'Japanese Yen', 'taxzen-for-woocommerce' ),
	INR: __( 'Indian Rupee', 'taxzen-for-woocommerce' ),
	SGD: __( 'Singapore Dollar', 'taxzen-for-woocommerce' ),
	CHF: __( 'Swiss Franc', 'taxzen-for-woocommerce' ),
	SEK: __( 'Swedish Krona', 'taxzen-for-woocommerce' ),
	NOK: __( 'Norwegian Krone', 'taxzen-for-woocommerce' ),
	DKK: __( 'Danish Krone', 'taxzen-for-woocommerce' ),
	PLN: __( 'Polish Zloty', 'taxzen-for-woocommerce' ),
	CZK: __( 'Czech Koruna', 'taxzen-for-woocommerce' ),
	HUF: __( 'Hungarian Forint', 'taxzen-for-woocommerce' ),
	RON: __( 'Romanian Leu', 'taxzen-for-woocommerce' ),
	BGN: __( 'Bulgarian Lev', 'taxzen-for-woocommerce' ),
	HRK: __( 'Croatian Kuna', 'taxzen-for-woocommerce' ),
	BRL: __( 'Brazilian Real', 'taxzen-for-woocommerce' ),
	MXN: __( 'Mexican Peso', 'taxzen-for-woocommerce' ),
	ZAR: __( 'South African Rand', 'taxzen-for-woocommerce' ),
	TRY: __( 'Turkish Lira', 'taxzen-for-woocommerce' ),
	AED: __( 'UAE Dirham', 'taxzen-for-woocommerce' ),
	SAR: __( 'Saudi Riyal', 'taxzen-for-woocommerce' ),
	KRW: __( 'South Korean Won', 'taxzen-for-woocommerce' ),
	THB: __( 'Thai Baht', 'taxzen-for-woocommerce' ),
	MYR: __( 'Malaysian Ringgit', 'taxzen-for-woocommerce' ),
	PHP: __( 'Philippine Peso', 'taxzen-for-woocommerce' ),
	ISK: __( 'Icelandic Króna', 'taxzen-for-woocommerce' ),
};
