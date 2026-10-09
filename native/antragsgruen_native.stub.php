<?php
/**
 * Signatures of the functions provided by the optional native extension (see README.md), for PHPStan and IDEs.
 * This file is never executed.
 */

/**
 * The API version of the loaded extension.
 */
function antragsgruen_native_version(): int {}

/**
 * Longest common subsequence over two lists of comparison IDs.
 * Returns the operations in forward order: 0 = unmodified, 1 = deleted, 2 = inserted.
 *
 * @param int[] $ids1
 * @param int[] $ids2
 * @return int[]
 */
function antragsgruen_lcs_ops(array $ids1, array $ids2): array {}
