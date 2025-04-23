/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{vue,js,ts,jsx,tsx}",
    "./includes/**/*.php",
    "./src/sass/**/*.scss"
  ],
  theme: {
    extend: {
      fontFamily: {
        'poppins': ['Poppins', 'sans-serif'],
        'source-sans': ['"Source Sans 3"', 'sans-serif'],
      }
    }
  },
  plugins: [],
}