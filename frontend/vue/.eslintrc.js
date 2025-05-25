module.exports = {
    env: { browser: true, es2021: true },
    extends: ['plugin:vue/vue3-essential', 'prettier'],
    plugins: ['vue', 'prettier'],
    rules: {
        'prettier/prettier': 'error',
    },
};