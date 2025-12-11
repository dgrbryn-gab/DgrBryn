/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.twig',
    './assets/**/*.js',
    './node_modules/bootstrap/js/**/*.js',
  ],
  theme: {
    extend: {
      fontFamily: {
        display: ['Playfair Display', 'serif'],
        roboto: ['Roboto', 'sans-serif'],
      },
      colors: {
        burgundy: {
          DEFAULT: '#7F1734',   // main
          light: '#9E2B47',     // hover
          dark: '#4A0E0E',      // deep tone
        },
        cream: {
          DEFAULT: '#F8EAD8',   // main cream
          light: '#FFF6EE',     // softer tone
        },
        gold: '#EFCFB4',
      },
      animation: {
        'fade-in': 'fadeIn 1s ease-out',
        'fade-in-up': 'fadeInUp 1s ease-out 0.2s',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        fadeInUp: {
          '0%': { opacity: '0', transform: 'translateY(20px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
      },
    },
  },
  // Safelist Bootstrap classes to prevent purging during production build
  safelist: [
    // Bootstrap grid
    { pattern: /^col-/ },
    { pattern: /^row/ },
    { pattern: /^container/ },
    // Bootstrap utilities
    { pattern: /^(d-|p-|m-|mt-|mb-|ml-|mr-|pt-|pb-|pl-|pr-|flex-|justify-|align-|text-|bg-|border-|shadow-|rounded-)/ },
    // Bootstrap components
    { pattern: /^(btn|btn-|card|alert|modal|dropdown|navbar)/ },
  ],
  plugins: [
    require('@tailwindcss/aspect-ratio'),
    require('@tailwindcss/line-clamp'),
  ],
};
