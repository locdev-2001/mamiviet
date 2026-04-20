import { defineConfig } from "vite";
import react from "@vitejs/plugin-react-swc";
import laravel from "laravel-vite-plugin";
import path from "path";

export default defineConfig({
  plugins: [
    laravel({
      input: ["src/index.css", "src/styles/font.css", "src/styles/main.css", "src/main.tsx"],
      refresh: true,
    }),
    react(),
  ],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
});
