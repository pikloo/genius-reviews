const path = require('path');

module.exports = {
  mode: 'production',
  entry: {
    admin: './admin/js/genius-reviews-admin.js',
    public: './public/js/genius-reviews-public.js',
  },
  output: {
    filename: 'genius-reviews-[name].bundle.js',
    path: path.resolve(__dirname, 'build'),
  },
   module: {
    rules: [
      {
        test: /\.css$/,
        use: [
          'style-loader',
          'css-loader',
          'postcss-loader',
        ],
      },
    ],
  },
};
