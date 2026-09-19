import js from "@eslint/js";
import globals from "globals";

export default [
  {
    ignores: [
      "vendor/**",
      "node_modules/**",
      "evaluation/data/**",
      "evaluation/reports/**",
    ],
  },
  js.configs.recommended,
  {
    files: ["public/assets/js/**/*.js"],
    languageOptions: { globals: globals.browser },
    rules: {
      "no-unused-vars": [
        "error",
        { argsIgnorePattern: "^_", varsIgnorePattern: "^_" },
      ],
    },
  },
  {
    files: ["tests/**/*.js", "*.config.js", "eslint.config.js"],
    languageOptions: { globals: globals.node },
  },
];
