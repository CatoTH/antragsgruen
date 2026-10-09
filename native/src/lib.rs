//! Optional PHP extension for Antragsgrün. See README.md for how to build and enable it.
//!
//! The PHP code checks antragsgruen_native_version() before using any function, and falls back to
//! its own implementation if the extension is missing or too old.

use ext_php_rs::prelude::*;

pub mod lcs;

/// Incremented whenever a function is added or its contract changes; checked by the PHP side.
pub const API_VERSION: i64 = 1;

/// The API version of this extension. See app\components\diff\Engine::nativeLcsAvailable().
#[php_function]
pub fn antragsgruen_native_version() -> i64 {
    API_VERSION
}

/// Longest common subsequence over two lists of comparison IDs, see lcs::lcs_ops().
/// Returns the operations in forward order: 0 = unmodified, 1 = deleted, 2 = inserted.
#[php_function]
pub fn antragsgruen_lcs_ops(ids1: Vec<i64>, ids2: Vec<i64>) -> Vec<i64> {
    lcs::lcs_ops(&ids1, &ids2)
}

#[php_module]
pub fn get_module(module: ModuleBuilder) -> ModuleBuilder {
    module
        .function(wrap_function!(antragsgruen_native_version))
        .function(wrap_function!(antragsgruen_lcs_ops))
}
