/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#1e3a8a',
          light: '#e6eaf2',
          dark: '#152a5c',
        },
      },
    },
  },
  safelist: [
    // Palette dynamique utilisée pour les rôles (user-manager permissions matrix + badges)
    ...[
      'purple','blue','green','orange','pink','teal','indigo','rose','amber','cyan','gray'
    ].flatMap(c => [
      `bg-${c}-50`, `bg-${c}-100`, `bg-${c}-200`,
      `text-${c}-700`, `text-${c}-800`,
      `border-${c}-300`, `border-${c}-400`,
      `hover:bg-${c}-200`,
    ]),
  ],
  plugins: [
    require('@tailwindcss/forms'),
  ],
} 