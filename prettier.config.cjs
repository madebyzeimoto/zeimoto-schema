// ==========================================================
// Tooling :: Prettier
// ==========================================================
//
// Defines formatting behavior for supported plugin files.
//
// ==========================================================

/** @type {import("prettier").Config} */
module.exports = {
  printWidth: 120,
  tabWidth: 2,
  useTabs: false,
  semi: false,
  singleQuote: false,
  trailingComma: "es5",
  bracketSpacing: true,
  arrowParens: "always",
  htmlWhitespaceSensitivity: "ignore",
  proseWrap: "preserve",
  endOfLine: "lf",
}
