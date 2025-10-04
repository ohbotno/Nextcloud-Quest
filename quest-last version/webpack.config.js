const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')

webpackConfig.entry = {
    main: path.join(__dirname, 'src', 'main.js'),
}

webpackConfig.output = {
    path: path.resolve(__dirname, './js'),
    publicPath: '/js/',
    filename: 'nextcloud-quest-[name].js',
    chunkFilename: 'chunks/nextcloud-quest-[name]-[hash].js',
}

module.exports = webpackConfig