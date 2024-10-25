import { defineConfig } from 'vite';
import path from 'path';
import { glob } from 'glob';
import postcssImport from 'postcss-import';
import postcssNested from 'postcss-nested';
import postcssCustomMedia from 'postcss-custom-media';
import autoprefixer from 'autoprefixer';
import cssnano from 'cssnano'; // Minifier and optimizer
import fs from 'fs/promises'; // Promises API for file handling
import postcss from 'postcss';

// Helper function to compile and optimize PCSS files
async function compileCSS(file) {
  const css = await fs.readFile(file, 'utf-8');

  // Create a PostCSS processor with all plugins
  const result = await postcss([
    postcssImport(),
    postcssNested(),
    postcssCustomMedia(),
    autoprefixer(), // Adds vendor prefixes
    cssnano({ preset: 'advanced' }), // Minify and optimize CSS
  ]).process(css, { from: file });

  return result.css;
}

export default defineConfig(async () => {
  const pcssFiles = await glob('./modules/*/components/*/*.pcss');

  console.log('PCSS Files:', pcssFiles); // Debugging line

  return {
    build: {
      rollupOptions: {
        input: pcssFiles,
      },
      emptyOutDir: false, // Avoid removing existing files
      assetsInlineLimit: 0, // Emit assets as files, not inline
    },
    plugins: [
      {
        name: 'compile-pcss',
        apply: 'build',
        async buildStart() {
          for (const file of pcssFiles) {
            const outputDir = path.dirname(file); // Use the same directory
            const outputName = `${path.basename(file, '.pcss')}.css`;
            const outputPath = path.join(outputDir, outputName);

            console.log('Processing File:', file.split('/').pop()); // Debugging line

            // Compile and optimize the CSS
            const optimizedCSS = await compileCSS(file);

            // Write the optimized CSS directly to the original directory
            await fs.writeFile(outputPath, optimizedCSS, 'utf-8');
            console.log(`Optimized CSS written to: ${outputPath}`);
          }
        },
      },
    ],
    css: {
      postcss: {
        plugins: [
          postcssImport(),
          postcssNested(),
          postcssCustomMedia(),
          autoprefixer(),
          cssnano({ preset: 'advanced' }), // Minify and optimize
        ],
      },
    },
  };
});

