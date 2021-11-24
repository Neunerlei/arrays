# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

### [3.3.2](https://github.com/Neunerlei/arrays/compare/v3.3.1...v3.3.2) (2021-11-24)

### [3.3.1](https://github.com/Neunerlei/arrays/compare/v3.3.0...v3.3.1) (2021-11-24)


### Bug Fixes

* **flatten:** ensure that empty arrays at the paths end stay in the list ([fa4ae96](https://github.com/Neunerlei/arrays/commit/fa4ae96081a244af8517b1da7b0c6f30e8d510e1))

## [3.3.0](https://github.com/Neunerlei/arrays/compare/v3.2.0...v3.3.0) (2021-05-28)


### Features

* **getList:** allow $keyKey to be TRUE to keep the current list key ([491b838](https://github.com/Neunerlei/arrays/commit/491b838d12cbd1cec3c000c8e8c02b7318dd93b3))


### Bug Fixes

* **merge:** fix typos in exception messages ([faa4c70](https://github.com/Neunerlei/arrays/commit/faa4c7084b05905d40bbb746bca1eea5f7b2609c))

## [3.2.0](https://github.com/Neunerlei/arrays/compare/v3.1.0...v3.2.0) (2021-02-12)


### Features

* replace traits with abstract classes and inheritance ([c6718d0](https://github.com/Neunerlei/arrays/commit/c6718d0da05809f80a63e3db7bd505cc12066cf5))
* update dependencies ([d2a9244](https://github.com/Neunerlei/arrays/commit/d2a924479a1ac2164251bcf1cb52e1feae58df47))

## [3.1.0](https://github.com/Neunerlei/arrays/compare/v3.0.0...v3.1.0) (2021-02-12)


### Features

* introduce $allowEmpty param to parsePath() + allow empty paths in mergePaths ([a6f1315](https://github.com/Neunerlei/arrays/commit/a6f131591be546e96d8fb57c8757604e466b6ffa))

## [3.0.0](https://github.com/Neunerlei/arrays/compare/v2.0.2...v3.0.0) (2021-02-11)


### ⚠ BREAKING CHANGES

* changes the old instance based architecture to be trait
based. This removes the instance handling and duplicate method creation.
However, it may break apps that depend on the instance and class
replacement feature

### Features

* internal refactoring ([3dd3cff](https://github.com/Neunerlei/arrays/commit/3dd3cff3d2d9e1c27525b03a4190ade4bc385959))


### Bug Fixes

* **merge:** fix type hint for args ([1d82775](https://github.com/Neunerlei/arrays/commit/1d82775049e3f7b95566ae1d93b0fbd0c8e44363))

### [2.0.2](https://github.com/Neunerlei/arrays/compare/v2.0.1...v2.0.2) (2021-02-11)


### Bug Fixes

* **merge:** make sure __UNSET values don't get copied into the output by accident ([a3f7bb3](https://github.com/Neunerlei/arrays/commit/a3f7bb3027310063c963b96d3769aed6835c5358))

### [2.0.1](https://github.com/Neunerlei/arrays/compare/v2.0.0...v2.0.1) (2021-01-30)


### Bug Fixes

* **getList:** rework of the internals ([f504742](https://github.com/Neunerlei/arrays/commit/f50474211dd283ab87c8bbebb8c4c92a62b52644))

## [2.0.0](https://github.com/Neunerlei/arrays/compare/v1.3.8...v2.0.0) (2021-01-30)


### ⚠ BREAKING CHANGES

* **getList:** getList() no longer returns $default on empty input

### Features

* implement dumpToJson helper ([905b4e1](https://github.com/Neunerlei/arrays/commit/905b4e146880d0ac37414135076176ab933a1ec3))


### Bug Fixes

* **getList:** fix return value on empty $input ([abc4307](https://github.com/Neunerlei/arrays/commit/abc4307fcb6591661ec88e8ea8274709f7aa59b8))

### [1.3.8](https://github.com/Neunerlei/arrays/compare/v1.3.7...v1.3.8) (2020-11-26)


### Bug Fixes

* **ArrayDumper:** prevent double root xml element ([27fd262](https://github.com/Neunerlei/arrays/commit/27fd262b6d4c1e423c843ba4c53536167e193670))

### [1.3.7](https://github.com/Neunerlei/arrays/compare/v1.3.6...v1.3.7) (2020-11-18)


### Bug Fixes

* **ArrayDumper:** prevent double root xml element ([dfd05ba](https://github.com/Neunerlei/arrays/commit/dfd05ba9b879c6d6e07c93abc86af5c94d704104))

### [1.3.6](https://github.com/Neunerlei/arrays/compare/v1.3.5...v1.3.6) (2020-11-18)


### Bug Fixes

* **ArrayDumper:** create $xml root if required ([adfe86e](https://github.com/Neunerlei/arrays/commit/adfe86e2b51b688ada9a2ebcffda7fdd687df38c))

### [1.3.5](https://github.com/Neunerlei/arrays/compare/v1.3.4...v1.3.5) (2020-11-02)


### Bug Fixes

* **ArrayDumper:** implement cdata handling ([164b619](https://github.com/Neunerlei/arrays/commit/164b6192ead44cbe0379e262abfb053632574cd1))

### [1.3.4](https://github.com/Neunerlei/arrays/compare/v1.3.3...v1.3.4) (2020-09-04)

### [1.3.3](https://github.com/Neunerlei/arrays/compare/v1.3.2...v1.3.3) (2020-09-04)

### [1.3.2](https://github.com/Neunerlei/arrays/compare/v1.3.1...v1.3.2) (2020-09-04)

### [1.3.1](https://github.com/Neunerlei/arrays/compare/v1.3.0...v1.3.1) (2020-08-10)

## [1.3.0](https://github.com/Neunerlei/arrays/compare/v1.2.3...v1.3.0) (2020-08-09)


### Features

* remove dependency on neunerlei/options ([4100f95](https://github.com/Neunerlei/arrays/commit/4100f952c373aff5ec80951f8eb11af0a25710c3))


### Bug Fixes

* add missing tests and minor bugfixes ([f07d6f8](https://github.com/Neunerlei/arrays/commit/f07d6f834fa1a5301bbecf7c1cda09c55e146f8c))

### [1.2.3](https://github.com/Neunerlei/arrays/compare/v1.2.2...v1.2.3) (2020-08-09)

### [1.2.2](https://github.com/Neunerlei/arrays/compare/v1.2.1...v1.2.2) (2020-07-21)


### Bug Fixes

* some additional tests + bugfixing ([d06422f](https://github.com/Neunerlei/arrays/commit/d06422fdb25e0dc6e574ff14b4aaf51ed2d8bc14))

### [1.2.1](https://github.com/Neunerlei/arrays/compare/v1.2.0...v1.2.1) (2020-07-20)


### Bug Fixes

* fix lodash security warning in the docs ([60a1c92](https://github.com/Neunerlei/arrays/commit/60a1c927b864c01f011f6a48cc0ee5c5c779bd1a))

## [1.2.0](https://github.com/Neunerlei/arrays/compare/v1.1.8...v1.2.0) (2020-07-01)


### Features

* update code to be PSR-2 compliant ([2701040](https://github.com/Neunerlei/arrays/commit/2701040a7df6aab739ef0597b3522bcc1a6f3d54))


### Bug Fixes

* **doc:** update dependencies for security reasons ([207a88c](https://github.com/Neunerlei/arrays/commit/207a88c287d044163150a950950372fa9e3ee580))

### [1.1.8](https://github.com/Neunerlei/arrays/compare/v1.1.7...v1.1.8) (2020-03-11)

### [1.1.7](https://github.com/Neunerlei/arrays/compare/v1.1.6...v1.1.7) (2020-03-10)

### [1.1.6](https://github.com/Neunerlei/arrays/compare/v1.1.5...v1.1.6) (2020-03-10)

### [1.1.5](https://github.com/Neunerlei/arrays/compare/v1.1.4...v1.1.5) (2020-03-10)

### [1.1.4](https://github.com/Neunerlei/arrays/compare/v1.1.3...v1.1.4) (2020-03-10)

### [1.1.3](https://github.com/Neunerlei/arrays/compare/v1.1.2...v1.1.3) (2020-03-10)

### [1.1.2](https://github.com/Neunerlei/arrays/compare/v1.1.1...v1.1.2) (2020-03-10)

### [1.1.1](https://github.com/Neunerlei/arrays/compare/v1.1.0...v1.1.1) (2020-03-10)

## 1.1.0 (2020-03-10)


### Features

* initial commit ([453877e](https://github.com/Neunerlei/arrays/commit/453877e9d97bc1149081020f1e39376845109d54))
