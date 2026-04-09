/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './views/**/*.php',
    './app/**/*.php',
    './routes/**/*.php',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        zen: {
          900: '#141414',
          800: '#1F2022',
          700: '#262628',
          600: '#2B2C2E',
          500: '#3A3B3D',
        },
        gray: {
          950: '#0F0F10',
          900: '#151515',
          800: '#2A2B2D',
          700: '#444548',
          600: '#5A5B5E',
        },
        text: {
          primary: '#F2F2F2',
          secondary: '#DADADA',
          muted: '#AFAFB2',
        },
        accent: {
          DEFAULT: '#D9312E',
          dark: '#B41F1B',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'monospace'],
      },
    },
  },
  plugins: [],
}
