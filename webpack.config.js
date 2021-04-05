const path = require('path')
const { CleanWebpackPlugin } = require('clean-webpack-plugin')

module.exports = {
  entry: './src/mobile.ts',
  // devtool: process.env.NODE_ENV === 'production' ? false : 'eval',
  devtool: 'source-map',
  mode: 'production',
  module: {
    rules: [
      {
        test: /\.tsx?$/,
        use: 'ts-loader',
        exclude: /node_modules/,
      }
    ]
  },
  resolve: {
    extensions: [ '.tsx', '.ts', '.js' ],
  },
  output: {
    filename: 'mobile.js',
    path: path.resolve(__dirname, 'js', 'content'),
  },
  plugins: [
    new CleanWebpackPlugin(),
  ]
}
