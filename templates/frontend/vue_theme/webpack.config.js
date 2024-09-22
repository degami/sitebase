const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
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
      'vue$': 'vue/dist/vue.esm-bundler.js', // Usa il bundle compatibile con Vue 3
      react: false, // Indica a Webpack di non cercare React
      'react-dom': false,
    },
    extensions: ['.js', '.vue', '.json'],
  },
};
