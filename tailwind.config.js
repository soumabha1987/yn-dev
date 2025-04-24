import colors from 'tailwindcss/colors'
import defaultTheme from 'tailwindcss/defaultTheme'
import typography from '@tailwindcss/typography'

const navyColor = {
    50: '#E7E9EF',
    100: '#C2C9D6',
    200: '#A3ADC2',
    300: '#697A9B',
    400: '#5C6B8A',
    450: '#465675',
    500: '#384766',
    600: '#313E59',
    700: '#26334D',
    750: '#222E45',
    800: '#202B40',
    900: '#192132',
}

const primaryColor = {
    100: '#dbeafe',
    200: '#bfdbfe',
    300: '#93c5fd',
    400: '#60a5fa',
    500: '#3b82f6',
    600: '#2563eb',
}

const secondaryColors = {
    orange: {
        100: '#FFE5BA',
        300: '#FFAA5C',
        500: '#FF652C',
        700: '#c2410c',
    },
    yellow: {
        100: '#FFFFB6',
        500: '#FFEB55',
    },
    green: {
        100: '#D3FFE3',
        300: '#6AFF96',
        500: '#01524C',
    },
}

const customColors = {
    navy: navyColor,
    'slate-150': '#E9EEF5',
    'primary': primaryColor[600],
    'primary-400': primaryColor[400],
    'primary-200': primaryColor[200],
    'primary-light': primaryColor[100],
    'primary-focus': '#1d4ed8',
    secondary: '#F000B9',
    'secondary-focus': '#BD0090',
    'secondary-orange': secondaryColors.orange[500],
    'secondary-orange-light': secondaryColors.orange[100],
    'secondary-orange-dark': secondaryColors.orange[700],
    'secondary-yellow': secondaryColors.yellow[500],
    'secondary-yellow-light': secondaryColors.yellow[100],
    'secondary-green': secondaryColors.green[500],
    'secondary-green-light': secondaryColors.green[100],
    'secondary-green-dark': secondaryColors.green[300],
    'accent-light': colors.indigo['400'],
    accent: '#5f5af6',
    'accent-focus': '#4d47f5',
    info: colors.sky['500'],
    'info-focus': colors.sky['600'],
    success: colors.emerald['500'],
    'success-focus': colors.emerald['600'],
    warning: '#ff9800',
    'warning-focus': '#e68200',
    error: '#ef4444',
    'error-focus': '#dc2626',
    custom: {
        50: 'rgba(var(--c-50), <alpha-value>)',
        100: 'rgba(var(--c-100), <alpha-value>)',
        200: 'rgba(var(--c-200), <alpha-value>)',
        300: 'rgba(var(--c-300), <alpha-value>)',
        400: 'rgba(var(--c-400), <alpha-value>)',
        500: 'rgba(var(--c-500), <alpha-value>)',
        600: 'rgba(var(--c-600), <alpha-value>)',
        700: 'rgba(var(--c-700), <alpha-value>)',
        800: 'rgba(var(--c-800), <alpha-value>)',
        900: 'rgba(var(--c-900), <alpha-value>)',
        950: 'rgba(var(--c-950), <alpha-value>)',
    },
};

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Enums/**/*.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            aspectRatio: {
                '3/2': '3 / 2',
            },
            container: {
                center: true,
                padding: {
                    DEFAULT: '1rem',
                    sm: '2rem',
                    lg: '4rem',
                    xl: '6rem',
                    '2xl': '8rem',
                },
            },
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.serif],
            },
            fontSize: {
                tiny: ['0.475rem', '0.6725rem'],
                'tiny+': ['0.5275rem', '0.675rem'],
                'xs+': ['0.6625rem', '0.925rem'],
                'sm+': ['0.875rem', '1.215rem'],
            },
            colors: {
                ...customColors,
            },
            opacity: {
                15: '.15',
            },
            spacing: {
                4.5: '1.125rem',
                5.5: '1.375rem',
                18: '4.5rem',
            },
            boxShadow: {
                soft: '0 3px 10px 0 rgb(48 46 56 / 6%)',
                'soft-dark': '0 3px 10px 0 rgb(25 33 50 / 30%)',
            },
            zIndex: {
                1: '1',
                2: '2',
                3: '3',
                4: '4',
                5: '5',
                'max': '9999',
            },
            animation: {
                wiggle: 'wiggle 0.5s ease-in-out forwards',
            },
            keyframes: {
                wiggle: {
                    '0%, 100%': { transform: 'translateX(0)' },
                    '25%': { transform: 'translateX(-0.25rem)' },
                    '50%': { transform: 'translateX(0.25rem)' },
                    '75%': { transform: 'translateX(-0.25rem)' },
                },
            },
        },
    },
    corePlugins: {
        textOpacity: false,
        backgroundOpacity: false,
        borderOpacity: false,
        divideOpacity: false,
        placeholderOpacity: false,
        ringOpacity: false,
    },
    plugins: [typography],
    safelist: [
        {
            pattern: /secondary-orange/,
            variants: ['hover', 'focus'],
        },
    ]
}
