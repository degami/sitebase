const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  devtool: 'eval-source-map',
  mode: 'production', // Imposta il mode, oppure 'development' per lo sviluppo
  entry: path.resolve(__dirname, 'main.js'), // Percorso del file di ingresso
  output: {
    path: path.resolve(__dirname, '../../../pub/vue_theme/js'), // Percorso di uscita
    publicPath: '/vue_theme/js/', // Percorso pubblico per il caricamento degli asset
    filename: 'bundle.js',
  },
  module: {
    rules: [
      {
        test: require.resolve('jquery'),
        loader: 'expose-loader',
        options: {
          exposes: ['$', 'jQuery'],
        },
      },
      {
        test: /\.vue$/,
        loader: 'vue-loader',
      },
      {
        test: /\.css$/,
        use: [
          //MiniCssExtractPlugin.loader, // Estrae il CSS in un file separato
          'style-loader', 
          'css-loader',
        ],
      },
      {
        test: /\.js$/,
        use: 'babel-loader',
        exclude: /node_modules/,
      },
      {
        test: /\.scss$/,
        include: path.resolve(__dirname, 'js'),
        use: [
          //MiniCssExtractPlugin.loader, // Estrae il CSS in un file separato
          'vue-style-loader', // Oppure 'style-loader'
          'css-loader',
          'sass-loader', // Aggiunge supporto per SCSS
        ],
      },
    ],
  },
  plugins: [
    new VueLoaderPlugin(),
    new MiniCssExtractPlugin({
      filename: '../../../pub/vue_theme/css/style.css', // Percorso e nome del file CSS generato
    }),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'js'), // Definisce l'alias @ come src
      'vue$': 'vue/dist/vue.esm-bundler.js', // Usa il bundle compatibile con Vue 3
      react: false, // Indica a Webpack di non cercare React
      'react-dom': false,
      jquery: require.resolve('jquery'),
    },
    extensions: ['.js', '.vue', '.json'],
  },
};
