/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./app/Views/**/*.php', './app/Helpers/**/*.php', './public/assets/js/**/*.js'],
  corePlugins: {
    // Bootstrap's reboot is the base layer; Tailwind supplies layout/utility classes.
    preflight: false,
    // Avoid clashing with Bootstrap's `.collapse` component class.
    visibility: false,
  },
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#eef6f1', 100: '#d9ebe0', 200: '#b5d7c3', 300: '#88bc9f', 400: '#5b9a78',
          500: '#3b7d5b', 600: '#2a6849', 700: '#1d5a3f', 800: '#174832', 900: '#133b2a', 950: '#0c261b',
        },
        gold: { 50: '#fbf5e6', 100: '#f5e7c2', 200: '#ecd28f', 300: '#e2bb5f', 400: '#d8a946', 500: '#c8912e', 600: '#a87222', 700: '#83561d' },
        ink: { DEFAULT: '#1c2b24', soft: '#3d4c44', muted: '#6a7a71', faint: '#94a39a' },
        paper: '#fdfcf8',
        canvas: '#eef3ee',
        line: '#e1e7e0',
      },
      fontFamily: {
        sans: ['"DM Sans"', 'system-ui', '-apple-system', '"Segoe UI"', 'Roboto', 'sans-serif'],
        serif: ['"DM Serif Display"', 'Georgia', '"Times New Roman"', 'serif'],
        mono: ['ui-monospace', 'SFMono-Regular', 'Consolas', 'monospace'],
      },
      boxShadow: {
        card: '0 1px 2px rgba(18, 48, 33, .04), 0 4px 16px -6px rgba(18, 48, 33, .08)',
        lift: '0 10px 30px -12px rgba(18, 48, 33, .35)',
      },
    },
  },
  plugins: [],
};
