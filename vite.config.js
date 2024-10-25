import { defineConfig } from 'vite';
import path from 'path';
import { glob } from 'glob';
import postcssImport from 'postcss-import';
import postcssNested from 'postcss-nested';
import postcssCustomMedia from 'postcss-custom-media';
import autoprefixer from 'autoprefixer';
import cssnano from 'cssnano';
import fs from 'fs/promises';
import postcss from 'postcss';

// Helper function to compile and optimize PCSS files
async function compileCSS(file) {
  const css = await fs.readFile(file, 'utf-8');
  const result = await postcss([
    postcssImport(),
    postcssNested(),
    postcssCustomMedia(),
    autoprefixer(),
    ...(process.env.NODE_ENV === 'production' ? [cssnano({ preset: 'advanced' })] : []),
  ]).process(css, { from: file });

  return result.css;
}

export default defineConfig(({ command }) => {
  const isBuild = command === 'build'; // Check if it's a build

  return {
    build: {
      rollupOptions: {
        input: glob.sync('./modules/*/components/*/*.pcss'), // Use PCSS files as input
        preserveEntrySignatures: false, // Prevent Rollup from expecting an entry point
      },
      emptyOutDir: false, // Don't wipe output directory
    },
    plugins: [
      {
        name: 'compile-pcss',
        apply: 'build', // Run only during build
        async buildStart() {
          const pcssFiles = glob.sync('./modules/*/components/*/*.pcss');
          console.log('PCSS Files:', pcssFiles);

          for (const file of pcssFiles) {
            const outputDir = path.dirname(file); // Save CSS next to the PCSS file
            const outputName = `${path.basename(file, '.pcss')}.css`;
            const outputPath = path.join(outputDir, outputName);

            console.log(`Processing File: ${file} -> ${outputPath}`);

            const optimizedCSS = await compileCSS(file);

            // Ensure the output directory exists
            await fs.mkdir(outputDir, { recursive: true });

            // Write the optimized CSS to the original location
            await fs.writeFile(outputPath, optimizedCSS, 'utf-8');
            console.log(`Optimized CSS written to: ${outputPath}`);
          }
        },
      },
      {
        name: 'pcss-hmr',
        apply: 'serve', // Only apply in dev mode
        async handleHotUpdate({ file, server }) {
          if (file.endsWith('.pcss')) {
            console.log(`Recompiling ${file}...`);

            const css = await compileCSS(file);

            const outputDir = path.dirname(file);
            const outputName = `${path.basename(file, '.pcss')}.css`;
            const outputPath = path.join(outputDir, outputName);

            await fs.mkdir(outputDir, { recursive: true });

            await fs.writeFile(outputPath, css, 'utf-8');
            console.log(`Updated CSS written to: ${outputPath}`);

            server.ws.send({
              type: 'update',
              updates: [
                {
                  type: 'css-update',
                  path: outputPath,
                  timestamp: Date.now(),
                },
              ],
            });

            return []; // Prevent full page reload
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
        ],
      },
    },
    server: {
      watch: {
        ignored: ['./modules/**/*.css'], // Avoid watching generated CSS files to prevent loops
      },
    },
  };
});

