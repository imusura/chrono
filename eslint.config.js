import js from '@eslint/js'
import vue from 'eslint-plugin-vue'
import ts from 'typescript-eslint'
import prettier from 'eslint-config-prettier'

export default ts.config(
  js.configs.recommended,
  ...ts.configs.recommended,
  ...vue.configs['flat/recommended'],
  prettier,
  {
    files: ['resources/js/**/*.{ts,vue}'],
    languageOptions: {
      parserOptions: {
        parser: ts.parser,
      },
    },
    rules: {
      'vue/multi-word-component-names': 'off',
      '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
    },
  },
  {
    ignores: ['vendor/**', 'node_modules/**', 'public/**', 'storage/**', 'bootstrap/ssr/**'],
  },
)
