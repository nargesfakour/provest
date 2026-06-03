/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './index.html',
    './src/**/*.{js,ts,jsx,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        primary: '#00c896',
        dark: '#1a2332',
        yes: '#00a87a',
        'yes-bg': '#e8faf4',
        no: '#e05050',
        'no-bg': '#fff0f0',
      },
      fontFamily: {
        sans: ['Vazirmatn', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
