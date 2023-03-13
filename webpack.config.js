const path = require('path')
const { CleanWebpackPlugin } = require('clean-webpack-plugin')

module.exports = {
  entry: {
    'mobile': path.resolve(__dirname, '/src/mobile.ts'),
    'dolibarr': path.resolve(__dirname, 'src/dolibarr.ts')
  },
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
    filename: '[name].js',
    path: path.resolve(__dirname, 'js', 'content'),
  },
  plugins: [
    new CleanWebpackPlugin(),
  ]
}
