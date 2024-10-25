/** @type { import('@storybook/html-vite').StorybookConfig } */
const config = {
  stories: ["../modules/**/*.stories.@(js|jsx|ts|tsx|mdx|html)"],
  addons: ["@storybook/addon-links", "@storybook/addon-essentials", "@chromatic-com/storybook", "@storybook/addon-interactions"],
  framework: {
    name: "@storybook/html-vite",
    options: {},
  },
  viteFinal: (config) => {
    // Tell Vite to treat .twig files as assets, not JS
    config.assetsInclude = ['**/*.twig'];
    return config;
  },
}
export default config
