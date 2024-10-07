module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./node_modules/flowbite/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        fade: '#F5F5F5'
      }
    },
  },
  plugins: [
    require('flowbite/plugin')
  ],
}