# Optional native extension

This directory contains an **optional** PHP extension, written in Rust using
[ext-php-rs](https://github.com/extphprs/ext-php-rs). It speeds up compute-intensive parts of Antragsgrün.

Antragsgrün works exactly the same without it: the PHP code checks whether the extension is loaded
(and recent enough) and otherwise falls back to its own implementation. Both implementations produce
identical results; `tests/Unit/NativeExtensionTest.php` verifies this.

## What it does

Currently, it implements the longest-common-subsequence table of the diff engine
(`app\components\diff\Engine::compareArrays()`, see [docs/technical/diff-pipeline.md](../docs/technical/diff-pipeline.md)).
This is where most of the time goes when paragraphs are long and heavily rewritten.

Measured on a synthetic motion with long paragraphs (61,000 characters, 30 amendments):

| View | Without extension | With extension |
|---|---|---|
| Motion view, empty cache | 659 ms | 356 ms |
| Merge view / inline amendments (not cached) | 557 ms | 260 ms |
| Same, with 100 amendments | 2,455 ms | 1,360 ms |

For motions with short or only slightly changed paragraphs, the difference is negligible.

## Building

### Requirements

- A Rust toolchain (stable; developed with Rust 1.99). See https://rustup.rs/
- `clang` / `libclang` (used to generate the bindings to the PHP headers)
- The development files of the PHP version that will load the extension (`php-config` must be available)
- PHP 8.1 or newer

On Debian / Ubuntu, for example:
```bash
apt-get install build-essential clang libclang-dev php8.4-dev
```
On macOS with Homebrew PHP, everything except Rust is usually already installed (`xcode-select --install` provides clang).

### Compile

```bash
cd native
cargo build --release
```

The extension is created at:
- Linux: `native/target/release/libantragsgruen_native.so`
- macOS: `native/target/release/libantragsgruen_native.dylib`

The extension is built against the PHP installation found via `php` / `php-config` in `PATH`.
If several PHP versions are installed, select one explicitly:
```bash
PHP=/usr/bin/php8.4 PHP_CONFIG=/usr/bin/php-config8.4 cargo build --release
```

An extension only works with the PHP version (and thread-safety variant) it was built for.
**Rebuild it after every PHP version upgrade** (e.g. 8.4 → 8.5). Patch releases (8.4.1 → 8.4.2) do not require a rebuild.

### Run the Rust unit tests

```bash
cd native
cargo test --lib
```

## Enabling the extension

Add it to the `php.ini` of every PHP SAPI running Antragsgrün, i.e. both PHP-FPM / the web server module
and the CLI (which runs background jobs and console commands):

```ini
extension=/var/www/antragsgruen/native/target/release/libantragsgruen_native.so
```

Alternatively, copy the file into the directory printed by `php-config --extension-dir` and use
`extension=libantragsgruen_native.so`. Restart PHP-FPM / the web server afterwards.

### Verify

```bash
php -r 'var_dump(function_exists("antragsgruen_native_version") ? antragsgruen_native_version() : "not loaded");'
```

To verify that both implementations produce identical diffs on this machine:
```bash
php -d extension=native/target/release/libantragsgruen_native.so vendor/bin/codecept run Unit NativeExtensionTest
```
(Without the extension loaded, these tests are skipped.)

## Development notes

- `src/lib.rs` contains the PHP-facing functions, `src/lcs.rs` the algorithm. PHP function signatures for
  PHPStan and IDEs are in `antragsgruen_native.stub.php`; keep it in sync.
- The output **must** be identical to the PHP implementation, including tie-breaks between equally long
  subsequences; otherwise diffs would differ depending on the server setup.
  Avoid anything depending on Unicode tables (grapheme clusters, case folding, regular expressions with
  Unicode classes) – their behavior differs between library versions and from PHP's ICU-based functions.
- When adding a function or changing a contract, increase `API_VERSION` in `src/lib.rs` and
  `NATIVE_API_VERSION` in `components/diff/Engine.php` (or the respective PHP class). Older builds of the
  extension are then ignored instead of being called with an unsupported contract.
