const prefixer = require('postcss-prefix-selector');

module.exports = {
  plugins: {
    '@tailwindcss/postcss': {},
    [prefixer({
      prefix: '.gr-bloc',
      transform(prefix, selector, prefixedSelector, filepath) {
        if (selector.startsWith('.gr-') || selector.startsWith('body') || selector.startsWith('@')) {
          return selector;
        }
        return `${prefix} ${selector}`;
      },
    })]: {},
    autoprefixer: {},
  },
};
