import { v4wp } from '@kucrut/vite-for-wp';
import vue from '@vitejs/plugin-vue';
import basicSsl from '@vitejs/plugin-basic-ssl';

// https://vitejs.dev/config/
export default {
  plugins: [
    v4wp({
      input: 'src/main.js',
      outDir: 'dist',
    }),
    vue(),
    basicSsl(),
  ],
  resolve: {
    alias: {
      // Support runtime compilation of Vue components for plugins
      vue: 'vue/dist/vue.esm-bundler.js',
    },
  },
  server: {
    host: true,
    https: true,
    cors: false,
  },
};
