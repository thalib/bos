To set up Prettier for formatting Vue.js code, follow these steps:

1. Install Prettier

Run the following command in your Vue.js project directory to install Prettier:

```bash
npm install --save-dev prettier
```

2. Install Prettier Plugins for Vue

To ensure Prettier works well with Vue files, install the necessary plugin:

```bash
npm install --save-dev prettier-plugin-vue
```

3. Create a Prettier Configuration File

Add a .prettierrc file in the root of your project with your preferred settings. For example:

```json
{
  "semi": true,
  "singleQuote": true,
  "tabWidth": 2,
  "trailingComma": "es5",
  "vueIndentScriptAndStyle": true
}
```

4. Integrate Prettier with ESLint (Optional)

If you're using ESLint, install the Prettier ESLint plugin:

```bash
npm install --save-dev eslint-config-prettier eslint-plugin-prettier
```

Update your .eslintrc.js file to include Prettier:

```json
module.exports = {
  extends: [
    'eslint:recommended',
    'plugin:vue/vue3-recommended',
    'plugin:prettier/recommended'
  ],
  rules: {
    'prettier/prettier': ['error']
  }
};
```

5. Set Up Auto-Formatting in VS Code
Install the Prettier - Code Formatter extension in VS Code.
Go to Settings (Ctrl + , or Cmd + ,), search for Format On Save, and enable it.
Ensure Prettier is set as the default formatter:

Open the Command Palette (Ctrl + Shift + P or Cmd + Shift + P).

Search for Preferences: Open Settings (JSON) and add:

```json
"[vue]": {
  "editor.defaultFormatter": "esbenp.prettier-vscode"
}
```

6. Format Your Code

You can now format your Vue.js files by saving them or using the command Format Document in VS Code.

This setup ensures your Vue.js code is consistently formatted and easy to read! 😊

## Refrence 

- https://www.digitalocean.com/community/tutorials/vuejs-vue-eslint-prettier
- https://www.digitalocean.com/community/tutorials/vuejs-vue-eslint-prettier
- https://dev.to/devidev/setting-up-eslint-9130-with-prettier-typescript-vuejs-and-vscode-autosave-autoformat-n0
- https://github.blog/ai-and-ml/github-copilot/from-chaos-to-clarity-using-github-copilot-agents-to-improve-developer-workflows/
