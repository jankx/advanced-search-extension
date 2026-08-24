const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const fs = require('fs');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');

// Find all block entry points
const blocksDir = path.resolve(__dirname, 'blocks');
const entryPoints = {};

if (fs.existsSync(blocksDir)) {
    fs.readdirSync(blocksDir).forEach((file) => {
        const blockPath = path.join(blocksDir, file);
        if (fs.statSync(blockPath).isDirectory()) {
            const indexTsx = path.join(blockPath, 'src/index.tsx');
            const indexTs = path.join(blockPath, 'src/index.ts');
            const indexJs = path.join(blockPath, 'src/index.js');

            if (fs.existsSync(indexTsx)) {
                entryPoints[file] = indexTsx;
            } else if (fs.existsSync(indexTs)) {
                entryPoints[file] = indexTs;
            } else if (fs.existsSync(indexJs)) {
                entryPoints[file] = indexJs;
            }
        }
    });
}

module.exports = {
    ...defaultConfig,
    entry: entryPoints,
    output: {
        ...defaultConfig.output,
        path: path.resolve(__dirname, 'dist/blocks'),
        filename: '[name]/index.js',
        clean: false // important to avoid deleting other files
    },
    // Prevent MiniCssExtractPlugin from hashing or flattening the paths if needed
    // Usually defaultConfig handles CSS based on entry names.
};
