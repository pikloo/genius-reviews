const path = require('path');

module.exports = {
  mode: 'production',

  entry: {
    admin: './admin/js/genius-reviews-admin.js',
    public: './public/js/genius-reviews-public.js',
  },

  output: {
    path: path.resolve(__dirname),
    filename: (pathData) => {
      if (pathData.chunk.name === 'admin') {
        return 'admin/build/genius-reviews-admin.bundle.js';
      }
      if (pathData.chunk.name === 'public') {
        return 'public/build/genius-reviews-public.bundle.js';
      }
      return '[name].js';
    },
  },

  module: {
    rules: [
      {
        test: /\.css$/,
        use: ['style-loader', 'css-loader', 'postcss-loader'],
      },
    ],
  },
};

