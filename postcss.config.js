module.exports = {
  plugins: [
    // Handle @import statements
    require('postcss-import')({
      path: ['src/css']
    }),
    
    // Enable modern CSS features with fallbacks
    require('postcss-preset-env')({
      stage: 1,
      features: {
        'nesting-rules': true,
        'custom-properties': true,
        'color-mix': true
      }
    }),
    
    // Process CSS nesting for broader browser support
    require('postcss-nesting'),
    
    // Add vendor prefixes automatically
    require('autoprefixer'),
    
    // Minify and optimize CSS for production
    require('cssnano')({
      preset: ['default', {
        discardComments: {
          removeAll: true
        },
        normalizeWhitespace: true,
        mergeLonghand: true,
        mergeRules: true
      }]
    })
  ]
}
