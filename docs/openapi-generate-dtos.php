<?php

declare(strict_types=1);

/**
 * Generates PHP DTO classes from an OpenAPI 3 spec's component schemas,
 * using cebe/php-openapi to parse the spec.
 * Supports a custom `x-php-namespace` attribute in OpenAPI, to support sub-namespaces.
 *
 * Re-running this script against an *existing* output file will only
 * regenerate the property declarations (and, for enums, the case list).
 * Any other class members you've hand-written into a previously generated
 * file -- methods, constants, traits, use-statements, custom comments --
 * are detected and preserved verbatim.
 *
 * Usage:
 *   - copy into a separate directory
 *   - composer require cebe/php-openapi
 *   - php openapi-generate-dtos.php ../antragsgruen/docs/openapi.yaml ../antragsgruen/models/api/ "app\\models\\api"
 */

require __DIR__ . '/vendor/autoload.php';

use cebe\openapi\Reader;
use cebe\openapi\spec\Schema;

// ---------------------------------------------------------------------------
// CLI args
// ---------------------------------------------------------------------------

$inputFile = $argv[1] ?? null;
$outputDir = $argv[2] ?? null;
$namespace = $argv[3] ?? 'app\\models\\api';

if (!$inputFile || !$outputDir) {
    fwrite(STDERR, "Usage: php generate-dtos.php <openapi.yaml> <output-dir> [namespace]\n");
    exit(1);
}

if (!is_file($inputFile)) {
    fwrite(STDERR, "Input file not found: {$inputFile}\n");
    exit(1);
}

if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
    fwrite(STDERR, "Could not create output directory: {$outputDir}\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// Load the spec
// ---------------------------------------------------------------------------

$ext = strtolower(pathinfo($inputFile, PATHINFO_EXTENSION));

try {
    $openApi = in_array($ext, ['yaml', 'yml'], true)
        ? Reader::readFromYamlFile(realpath($inputFile))
        : Reader::readFromJsonFile(realpath($inputFile));
} catch (\Throwable $e) {
    fwrite(STDERR, "Failed to parse OpenAPI file: {$e->getMessage()}\n");
    exit(1);
}

$schemas = $openApi->components->schemas ?? [];

if (empty($schemas)) {
    fwrite(STDERR, "No component schemas found in spec.\n");
    exit(0);
}

// ---------------------------------------------------------------------------
// Generation
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Resolve per-schema namespaces (x-php-namespace extension) up front, so
// that cross-schema $ref type-hints can tell whether the target lives in
// the same namespace (short class name) or a different one (FQCN needed).
// ---------------------------------------------------------------------------

$schemaSubNamespace = []; // raw schema name => sub-namespace string or null
$schemaFullNamespace = []; // raw schema name => fully-qualified namespace string

foreach ($schemas as $name => $schema) {
    $resolved = resolveSchema($schema);
    $subNs = $resolved !== null ? getXPhpNamespace($resolved) : null;

    $schemaSubNamespace[$name] = $subNs;
    $schemaFullNamespace[$name] = $subNs !== null ? ($namespace . '\\' . $subNs) : $namespace;
}

// First pass: generate backed enum classes for any top-level schema that is
// itself an enum (e.g. components.schemas.Status: {type: string, enum: [...]}).
foreach ($schemas as $name => $schema) {
    /** @var Schema $schema */
    if (!empty($schema->enum)) {
        $className = toClassName($name);
        $classNamespace = $schemaFullNamespace[$name];
        $classOutputDir = outputDirFor($outputDir, $schemaSubNamespace[$name]);
        writeEnumFile($className, $classNamespace, $schema, $classOutputDir);
    }
}

// Second pass: generate DTO classes for object schemas (including those
// composed via allOf).
foreach ($schemas as $name => $schema) {
    /** @var Schema $schema */
    if (!empty($schema->enum)) {
        continue; // already handled above
    }

    $merged = mergeAllOf($schema, $schemas);

    if ($merged === null) {
        fwrite(STDERR, "Skipping '{$name}': not an object schema (no properties, no allOf).\n");
        continue;
    }

    $className = toClassName($name);
    $classNamespace = $schemaFullNamespace[$name];
    $classOutputDir = outputDirFor($outputDir, $schemaSubNamespace[$name]);

    writeClassFile($className, $classNamespace, $merged, $schemas, $classOutputDir, $schemaFullNamespace);
}

// ---------------------------------------------------------------------------
// File writers (handle "update in place" vs "create new")
// ---------------------------------------------------------------------------

/**
 * Writes (or merges into) a DTO class file. Properties are defined via
 * constructor property promotion, e.g.:
 *
 *   public function __construct(
 *       public int $id,
 *       public ?string $name = null,
 *   ) {
 *   }
 *
 * @param array<string,string> $schemaFullNamespace Raw schema name => fully-qualified namespace,
 *                                                   used to decide short class name vs FQCN for $ref properties.
 */
function writeClassFile(string $className, string $namespace, array $normalized, array $allSchemas, string $outputDir, array $schemaFullNamespace): void
{
    $paramLines = buildConstructorParams($className, $normalized, $allSchemas, $outputDir, $namespace, $schemaFullNamespace);
    $path = rtrim($outputDir, '/') . '/' . $className . '.php';

    if (is_file($path)) {
        $existing = file_get_contents($path);
        $merged = mergeConstructorParams($existing, $className, $paramLines);

        if ($merged === null) {
            fwrite(STDERR, "Could not parse existing file, leaving it untouched: {$path}\n");
            return;
        }

        file_put_contents($path, $merged);
        fwrite(STDOUT, "Updated constructor properties in {$path} (preserved existing methods/constructor body)\n");
        return;
    }

    $code = wrapConstructorClass($className, $namespace, $paramLines);
    file_put_contents($path, $code);
    fwrite(STDOUT, "Wrote {$path}\n");
}

/**
 * Writes (or merges into) a backed-enum file.
 */
function writeEnumFile(string $className, string $namespace, Schema $schema, string $outputDir): void
{
    $caseLines = buildEnumCaseLines($schema);
    $backingType = $schema->type === 'integer' ? 'int' : 'string';
    $path = rtrim($outputDir, '/') . '/' . $className . '.php';

    if (is_file($path)) {
        $existing = file_get_contents($path);
        $merged = mergeGeneratedMembers($existing, 'enum', $className, $caseLines, 'isEnumCaseLine', $backingType);

        if ($merged === null) {
            fwrite(STDERR, "Could not parse existing enum file, leaving it untouched: {$path}\n");
            return;
        }

        file_put_contents($path, $merged);
        fwrite(STDOUT, "Updated cases in {$path} (preserved existing methods/members)\n");
        return;
    }

    $code = wrapMembers('enum', $className, $namespace, $caseLines, $backingType);
    file_put_contents($path, $code);
    fwrite(STDOUT, "Wrote enum {$path}\n");
}

/**
 * Wraps a list of enum case lines in a fresh enum file's header/footer --
 * used only when the target file doesn't exist yet.
 */
function wrapMembers(string $kind, string $className, string $namespace, array $memberLines, ?string $backingType = null): string
{
    $body = implode("\n", $memberLines);

    $declaration = $kind === 'enum'
        ? "enum {$className}: {$backingType}"
        : "class {$className}";

    return "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\n{$declaration}\n{\n{$body}\n}\n";
}

/**
 * Wraps a list of promoted constructor parameter lines in a fresh class
 * file -- used only when the target file doesn't exist yet.
 */
function wrapConstructorClass(string $className, string $namespace, array $paramLines): string
{
    $params = implode("\n", $paramLines);

    return "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nclass {$className}\n{\n    public function __construct(\n{$params}\n    ) {\n    }\n}\n";
}

/**
 * Reads the `x-php-namespace` vendor extension off a schema, if present.
 * Returns null if the extension isn't set.
 *
 * cebe/php-openapi exposes vendor extensions (any "x-*" key) via
 * getExtensions(). Depending on library version, the key may or may not
 * retain the "x-" prefix, so we check both forms defensively.
 *
 * NOTE: this is the one piece of this script that relies directly on
 * cebe/php-openapi's extension API and could not be executed against the
 * real library in the environment this was written in (no Packagist
 * access). Please sanity-check this against your installed version --
 * e.g. var_dump($schema->getExtensions()) on a schema that has
 * x-php-namespace set -- and adjust the key lookup below if needed.
 */
function getXPhpNamespace(Schema $schema): ?string
{
    if (!method_exists($schema, 'getExtensions')) {
        return null;
    }

    $extensions = $schema->getExtensions() ?? [];

    foreach (['x-php-namespace', 'php-namespace'] as $key) {
        if (isset($extensions[$key]) && is_string($extensions[$key]) && $extensions[$key] !== '') {
            return trim($extensions[$key], '\\');
        }
    }

    return null;
}

/**
 * Computes the output directory for a schema given its sub-namespace
 * (e.g. base "src/Model" + sub-namespace "SpeakingList" -> "src/Model/SpeakingList"),
 * creating the directory if needed.
 */
function outputDirFor(string $baseOutputDir, ?string $subNamespace): string
{
    if ($subNamespace === null) {
        return $baseOutputDir;
    }

    $relativePath = str_replace('\\', '/', $subNamespace);
    $dir = rtrim($baseOutputDir, '/') . '/' . $relativePath;

    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        fwrite(STDERR, "Could not create namespace output directory: {$dir}\n");
        return $baseOutputDir;
    }

    return $dir;
}

// ---------------------------------------------------------------------------
// Merging generated members into an existing file
// ---------------------------------------------------------------------------

/**
 * Re-generates only the "generated" members (properties for classes, cases
 * for enums) inside an existing file, leaving everything else in the class
 * body (methods, constants, traits, custom comments, hand-added properties
 * that don't match the generated pattern, etc.) untouched.
 *
 * Returns null if the class/enum declaration couldn't be located, so the
 * caller can bail out without touching the file.
 */
function mergeGeneratedMembers(
    string $source,
    string $kind,
    string $className,
    array $newMemberLines,
    string $isGeneratedLineFn,
    ?string $backingType = null
): ?string {
    $keyword = $kind === 'enum' ? 'enum' : 'class';
    $pattern = '/\b' . $keyword . '\s+' . preg_quote($className, '/') . '\b[^{]*\{/';

    if (!preg_match($pattern, $source, $match, PREG_OFFSET_CAPTURE)) {
        return null;
    }

    $bodyStart = $match[0][1] + strlen($match[0][0]); // just after the opening "{"
    $bodyEnd = findMatchingBrace($source, $match[0][1] + strlen($match[0][0]) - 1);

    if ($bodyEnd === null) {
        return null;
    }

    $before = substr($source, 0, $bodyStart);
    $body = substr($source, $bodyStart, $bodyEnd - $bodyStart);
    $after = substr($source, $bodyEnd); // starts with the closing "}"

    // If this is an enum and its backing type changed (e.g. spec switched a
    // field from string to int), update the declaration line too.
    if ($kind === 'enum' && $backingType !== null) {
        $before = preg_replace(
            '/(\benum\s+' . preg_quote($className, '/') . '\s*:\s*)(string|int)/',
            '${1}' . $backingType,
            $before
        );
    }

    $preserved = stripGeneratedLines($body, $isGeneratedLineFn);
    $preservedTrimmed = trimBlankLines($preserved);

    $newBody = implode("\n", $newMemberLines);
    if ($preservedTrimmed !== '') {
        $newBody .= "\n\n" . $preservedTrimmed;
    }

    return $before . "\n" . $newBody . "\n" . $after;
}

/**
 * Removes leading/trailing blank (whitespace-only) lines from a block of
 * text without touching the indentation of the first/last real line.
 */
function trimBlankLines(string $text): string
{
    $lines = explode("\n", $text);

    while (count($lines) > 0 && trim($lines[0]) === '') {
        array_shift($lines);
    }
    while (count($lines) > 0 && trim($lines[count($lines) - 1]) === '') {
        array_pop($lines);
    }

    return implode("\n", $lines);
}

/**
 * Finds the index of the closing parenthesis matching the opening
 * parenthesis at $openParenIndex, by simple depth counting. Same caveats
 * as findMatchingBrace() -- not a real tokenizer, but sufficient for
 * typical generated/hand-written constructor signatures.
 */
function findMatchingParen(string $source, int $openParenIndex): ?int
{
    $depth = 0;
    $len = strlen($source);

    for ($i = $openParenIndex; $i < $len; $i++) {
        $char = $source[$i];

        if ($char === '(') {
            $depth++;
        } elseif ($char === ')') {
            $depth--;
            if ($depth === 0) {
                return $i;
            }
        }
    }

    return null;
}

/**
 * Re-generates only the promoted-property parameter list inside an
 * existing class's constructor, leaving the constructor body and every
 * other class member (other methods, constants, comments, etc.) intact.
 *
 * If the existing file has no constructor yet (e.g. it predates the move
 * to constructor promotion, or was hand-written with plain properties), a
 * new constructor is inserted with an empty body, and any old-style plain
 * property declarations are stripped as part of the migration.
 *
 * Returns null if the class declaration itself couldn't be located.
 */
function mergeConstructorParams(string $source, string $className, array $newParamLines): ?string
{
    $classPattern = '/\bclass\s+' . preg_quote($className, '/') . '\b[^{]*\{/';

    if (!preg_match($classPattern, $source, $classMatch, PREG_OFFSET_CAPTURE)) {
        return null;
    }

    $classBodyStart = $classMatch[0][1] + strlen($classMatch[0][0]);
    $classBodyEnd = findMatchingBrace($source, $classMatch[0][1] + strlen($classMatch[0][0]) - 1);

    if ($classBodyEnd === null) {
        return null;
    }

    $before = substr($source, 0, $classBodyStart);
    $classBody = substr($source, $classBodyStart, $classBodyEnd - $classBodyStart);
    $after = substr($source, $classBodyEnd); // starts with the class's closing "}"

    $newParams = implode("\n", $newParamLines);

    // Look for an existing constructor inside the class body.
    if (preg_match('/public\s+function\s+__construct\s*\(/', $classBody, $ctorMatch, PREG_OFFSET_CAPTURE)) {
        $ctorKeywordStart = $ctorMatch[0][1];
        $openParenIndex = $ctorKeywordStart + strlen($ctorMatch[0][0]) - 1; // index of the "("
        $closeParenIndex = findMatchingParen($classBody, $openParenIndex);

        if ($closeParenIndex === null) {
            return null; // malformed constructor signature -- bail out rather than mangle the file
        }

        // Find the constructor body "{ ... }" right after the params,
        // preserved exactly as-is (this is where hand-written logic lives).
        $afterParams = substr($classBody, $closeParenIndex + 1);
        if (!preg_match('/^\s*\{/', $afterParams, $bodyOpenMatch, PREG_OFFSET_CAPTURE)) {
            return null; // constructor with no body? bail out rather than guess
        }
        $bodyOpenIndexInAfterParams = $bodyOpenMatch[0][1] + strpos($bodyOpenMatch[0][0], '{');
        $bodyOpenIndex = $closeParenIndex + 1 + $bodyOpenIndexInAfterParams;
        $bodyCloseIndex = findMatchingBrace($classBody, $bodyOpenIndex);

        if ($bodyCloseIndex === null) {
            return null;
        }

        $beforeCtor = substr($classBody, 0, $ctorKeywordStart);
        $ctorBody = substr($classBody, $bodyOpenIndex, $bodyCloseIndex - $bodyOpenIndex + 1); // includes { ... }
        $restAfterCtor = substr($classBody, $bodyCloseIndex + 1);

        // Old-style plain property declarations sitting before the
        // constructor (e.g. from a pre-promotion version of this file)
        // are stripped as part of migrating to constructor promotion.
        $beforeCtorCleaned = trimBlankLines(stripGeneratedLines($beforeCtor, 'isPropertyDeclLine'));

        $newClassBody = '';
        if ($beforeCtorCleaned !== '') {
            $newClassBody .= $beforeCtorCleaned . "\n\n";
        }
        $newClassBody .= "    public function __construct(\n{$newParams}\n    ) {$ctorBody}";

        $restTrimmed = trimBlankLines($restAfterCtor);
        if ($restTrimmed !== '') {
            $newClassBody .= "\n\n" . $restTrimmed;
        }

        return $before . "\n" . $newClassBody . "\n" . $after;
    }

    // No constructor found at all -- migrate from plain properties (if
    // any) and insert a fresh constructor with an empty body, keeping
    // any other existing members (methods, etc.) after it.
    $preserved = trimBlankLines(stripGeneratedLines($classBody, 'isPropertyDeclLine'));

    $newClassBody = "    public function __construct(\n{$newParams}\n    ) {\n    }";
    if ($preserved !== '') {
        $newClassBody .= "\n\n" . $preserved;
    }

    return $before . "\n" . $newClassBody . "\n" . $after;
}

/**
 * Finds the index of the closing brace matching the opening brace at
 * $openBraceIndex, by simple depth counting. Ignores braces inside strings
 * and comments well enough for generated/typical code, but is not a full
 * PHP tokenizer -- pathological hand-edits (braces inside string literals
 * that look like code) could confuse it.
 */
function findMatchingBrace(string $source, int $openBraceIndex): ?int
{
    $depth = 0;
    $len = strlen($source);

    for ($i = $openBraceIndex; $i < $len; $i++) {
        $char = $source[$i];

        if ($char === '{') {
            $depth++;
        } elseif ($char === '}') {
            $depth--;
            if ($depth === 0) {
                return $i;
            }
        }
    }

    return null;
}

/**
 * Removes lines from a class/enum body that match the "generated" pattern
 * (and, for properties, a directly-preceding single-line @var docblock),
 * returning whatever's left.
 */
function stripGeneratedLines(string $body, string $isGeneratedLineFn): string
{
    $lines = explode("\n", $body);
    $kept = [];
    $pendingDocblock = null;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Hold a single-line `/** @var ... */` docblock to decide once we
        // see what follows it.
        if (preg_match('/^\/\*\*\s*@var\b.*\*\/$/', $trimmed)) {
            $pendingDocblock = $line;
            continue;
        }

        if ($trimmed === '') {
            // Don't let a blank line strand a held docblock as orphaned;
            // flush it before the blank line.
            if ($pendingDocblock !== null) {
                $kept[] = $pendingDocblock;
                $pendingDocblock = null;
            }
            $kept[] = $line;
            continue;
        }

        if ($isGeneratedLineFn($trimmed)) {
            // Drop this line, and drop the docblock that was held for it.
            $pendingDocblock = null;
            continue;
        }

        // Not a generated line -- flush any held docblock first, then keep
        // this line as-is (it's part of a method, constant, comment, etc.).
        if ($pendingDocblock !== null) {
            $kept[] = $pendingDocblock;
            $pendingDocblock = null;
        }
        $kept[] = $line;
    }

    if ($pendingDocblock !== null) {
        $kept[] = $pendingDocblock;
    }

    return implode("\n", $kept);
}

/**
 * Matches a generated property declaration, e.g.:
 *   public int $id;
 *   public ?string $userToken;
 *   public ?Speaker $speaker;
 */
function isPropertyDeclLine(string $trimmedLine): bool
{
    return (bool) preg_match(
        '/^(public|protected|private)\s+(static\s+)?\??[A-Za-z0-9_\\\\]+\s+\$[A-Za-z0-9_]+\s*;$/',
        $trimmedLine
    );
}

/**
 * Matches a generated enum case, e.g.:
 *   case InProgress = 'in_progress';
 *   case Active = 1;
 */
function isEnumCaseLine(string $trimmedLine): bool
{
    return (bool) preg_match('/^case\s+[A-Za-z_][A-Za-z0-9_]*\s*=\s*.+;$/', $trimmedLine);
}

// ---------------------------------------------------------------------------
// Schema -> PHP helpers
// ---------------------------------------------------------------------------

/**
 * Converts a schema name to a valid, PascalCase PHP class name.
 */
function toClassName(string $schemaName): string
{
    $clean = preg_replace('/[^A-Za-z0-9]+/', ' ', $schemaName);
    $parts = array_filter(explode(' ', (string) $clean));
    $pascal = implode('', array_map('ucfirst', $parts));

    return $pascal !== '' ? $pascal : 'Model';
}

/**
 * Converts a property name to camelCase (OpenAPI properties are sometimes
 * snake_case; PHP property convention here is camelCase per the example).
 */
function toPropertyName(string $propName): string
{
    if (!str_contains($propName, '_') && !str_contains($propName, '-')) {
        return $propName;
    }

    $clean = preg_replace('/[_\-]+/', ' ', $propName);
    $parts = array_filter(explode(' ', (string) $clean));
    $first = true;
    $camel = '';
    foreach ($parts as $part) {
        $camel .= $first ? lcfirst($part) : ucfirst($part);
        $first = false;
    }

    return $camel !== '' ? $camel : $propName;
}

/**
 * Resolves an OpenAPI schema/type into a PHP type string (without nullability).
 *
 * @param Schema|\cebe\openapi\spec\Reference $schema
 * @param array<string,string> $schemaFullNamespace Raw schema name => fully-qualified namespace
 * @param string $currentNamespace The namespace of the class currently being rendered,
 *                                  used to decide short class name vs FQCN for $ref properties.
 */
function resolvePhpType($schema, array $allSchemas, array $schemaFullNamespace = [], string $currentNamespace = ''): string
{
    if ($schema instanceof \cebe\openapi\spec\Reference) {
        $refName = refTargetName($schema);
        if ($refName !== null) {
            return classNameForSchema($refName, $schemaFullNamespace, $currentNamespace);
        }
        $schema = $schema->resolve();
    }

    // cebe/php-openapi auto-resolves $ref'd schemas (object properties,
    // array items, etc.) into the *same instance* as the named component
    // schema, rather than leaving a Reference wrapper in place. Detect this
    // by identity match against all named schemas, so a directly-referenced
    // property (e.g. `settings: { $ref: '#/components/schemas/Settings' }`)
    // resolves to the real class instead of falling through to a generic
    // 'object' type below.
    if ($schema instanceof Schema) {
        $matchedName = findNamedSchemaForInstance($schema, $allSchemas);
        if ($matchedName !== null) {
            return classNameForSchema($matchedName, $schemaFullNamespace, $currentNamespace);
        }
    }

    if (!$schema instanceof Schema) {
        return 'mixed';
    }

    $type = $schema->type;
    $format = $schema->format;

    switch ($type) {
        case 'integer':
            return 'int';
        case 'number':
            return 'float';
        case 'boolean':
            return 'bool';
        case 'string':
            if ($format === 'date' || $format === 'date-time') {
                return '\\DateTime';
            }
            return 'string';
        case 'array':
            return 'array';
        case 'object':
            return 'object';
        default:
            return 'mixed';
    }
}

/**
 * Builds the FQCN (or short class name, if same namespace as the class
 * currently being rendered) for a named component schema.
 */
function classNameForSchema(string $schemaName, array $schemaFullNamespace, string $currentNamespace): string
{
    $className = toClassName($schemaName);
    $targetNamespace = $schemaFullNamespace[$schemaName] ?? $currentNamespace;

    if ($targetNamespace !== '' && $targetNamespace !== $currentNamespace) {
        return '\\' . $targetNamespace . '\\' . $className;
    }

    return $className;
}

/**
 * Finds the raw schema name (the key under components.schemas) whose
 * resolved Schema instance is identical (===) to the given $schema. This is
 * how we detect cebe/php-openapi's auto-resolved $refs, since at that point
 * we no longer have the original Reference wrapper (or its $ref string) to
 * read the target name from -- only object identity ties it back to a name.
 */
function findNamedSchemaForInstance(Schema $schema, array $allSchemas): ?string
{
    foreach ($allSchemas as $schemaName => $namedSchema) {
        $resolved = resolveSchema($namedSchema);
        if ($resolved !== null && $resolved === $schema) {
            return $schemaName;
        }
    }

    return null;
}

/**
 * Extracts the schema name a $ref points to, e.g. "#/components/schemas/Foo" -> "Foo".
 */
function refTargetName(\cebe\openapi\spec\Reference $ref): ?string
{
    $refString = (string) $ref->getJsonReference()->getJsonPointer();
    $parts = explode('/', trim($refString, '/'));

    return end($parts) ?: null;
}

/**
 * Builds a docblock hint for array item types, e.g. "string[]" or "Foo[]".
 */
function arrayItemDocType(Schema $schema, array $allSchemas, array $schemaFullNamespace = [], string $currentNamespace = ''): ?string
{
    if ($schema->type !== 'array' || $schema->items === null) {
        return null;
    }

    // resolvePhpType() handles cebe/php-openapi's auto-resolved $refs
    // (matching by object identity against named component schemas), so
    // array items get the same treatment as any other $ref'd property.
    $itemType = resolvePhpType($schema->items, $allSchemas, $schemaFullNamespace, $currentNamespace);

    return $itemType . '[]';
}

/**
 * Resolves a schema or reference into a concrete Schema instance.
 *
 * @param Schema|\cebe\openapi\spec\Reference $schema
 */
function resolveSchema($schema): ?Schema
{
    if ($schema instanceof \cebe\openapi\spec\Reference) {
        $schema = $schema->resolve();
    }

    return $schema instanceof Schema ? $schema : null;
}

/**
 * Flattens a schema's properties + required list, recursively merging any
 * `allOf` branches (including $refs to other object schemas, the common
 * "base schema + extension" pattern). Returns null if the schema has no
 * properties at all (i.e. it's not something we can turn into a DTO).
 *
 * @return array{properties: array<string, mixed>, required: string[]}|null
 */
function mergeAllOf($schema, array $allSchemas): ?array
{
    $resolved = resolveSchema($schema);
    if ($resolved === null) {
        return null;
    }

    $properties = [];
    $required = [];

    if (!empty($resolved->allOf)) {
        foreach ($resolved->allOf as $branch) {
            $branchMerged = mergeAllOf($branch, $allSchemas);
            if ($branchMerged !== null) {
                $properties = array_merge($properties, $branchMerged['properties']);
                $required = array_merge($required, $branchMerged['required']);
            }
        }
    }

    if (!empty($resolved->properties)) {
        $properties = array_merge($properties, $resolved->properties);
    }

    if (!empty($resolved->required)) {
        $required = array_merge($required, $resolved->required);
    }

    if (empty($properties)) {
        return null;
    }

    return [
        'properties' => $properties,
        'required' => array_values(array_unique($required)),
    ];
}

/**
 * Builds the list of promoted constructor parameter lines (with @var
 * docblocks where relevant) for a DTO class. May also write out additional
 * files for inline enum properties (enums declared directly on a property
 * rather than as a standalone named schema) -- those inherit the parent
 * DTO's namespace and output directory, since they aren't standalone
 * schemas with their own x-php-namespace extension.
 *
 * Required (non-nullable) parameters are emitted first, followed by
 * optional/nullable ones with a `= null` default -- PHP requires
 * parameters without a default to precede those with one, so this
 * reordering is necessary even though it may not match the property order
 * in the OpenAPI spec.
 *
 * @param array<string,string> $schemaFullNamespace Raw schema name => fully-qualified namespace
 * @return string[]
 */
function buildConstructorParams(
    string $className,
    array $normalized,
    array $allSchemas,
    string $outputDir,
    string $currentNamespace,
    array $schemaFullNamespace = []
): array {
    $properties = $normalized['properties'];
    $required = $normalized['required'];

    $requiredLines = [];
    $optionalLines = [];

    foreach ($properties as $propName => $propSchema) {
        $phpName = toPropertyName($propName);
        $isRequired = in_array($propName, $required, true);

        $resolvedSchema = resolveSchema($propSchema);
        $isRef = $propSchema instanceof \cebe\openapi\spec\Reference;

        $nullable = $isRequired === false || ($resolvedSchema !== null && $resolvedSchema->nullable === true);

        if (!$isRef && $resolvedSchema !== null && !empty($resolvedSchema->enum)) {
            $enumClassName = $className . ucfirst($phpName);
            writeEnumFile($enumClassName, $currentNamespace, $resolvedSchema, $outputDir);
            $phpType = $enumClassName;
        } else {
            $phpType = resolvePhpType($propSchema, $allSchemas, $schemaFullNamespace, $currentNamespace);
        }

        $typeDeclaration = ($nullable ? '?' : '') . $phpType;
        $default = $nullable ? ' = null' : '';

        $entryLines = [];

        if ($resolvedSchema !== null && $resolvedSchema->type === 'array') {
            $itemDocType = arrayItemDocType($resolvedSchema, $allSchemas, $schemaFullNamespace, $currentNamespace);
            if ($itemDocType !== null) {
                $entryLines[] = "        /** @var {$itemDocType}" . ($nullable ? '|null' : '') . " */";
            }
        }

        $entryLines[] = "        public {$typeDeclaration} \${$phpName}{$default},";

        if ($nullable) {
            $optionalLines = array_merge($optionalLines, $entryLines);
        } else {
            $requiredLines = array_merge($requiredLines, $entryLines);
        }
    }

    return array_merge($requiredLines, $optionalLines);
}

/**
 * Builds the list of `case Name = value;` lines for a backed enum.
 *
 * @return string[]
 */
function buildEnumCaseLines(Schema $schema): array
{
    $values = $schema->enum ?? [];
    $isInt = $schema->type === 'integer';

    $lines = [];
    foreach ($values as $value) {
        if ($value === null) {
            // PHP enums can't have a null case; nullability is expressed on
            // the property type instead.
            continue;
        }

        $caseName = enumCaseName((string) $value);
        $literal = $isInt ? (string) (int) $value : "'" . addslashes((string) $value) . "'";
        $lines[] = "    case {$caseName} = {$literal};";
    }

    return $lines;
}

/**
 * Converts an enum value like "in_progress" or "In Progress" into a valid
 * Uppercase PHP enum case name.
 */
function enumCaseName(string $value): string
{
    $clean = preg_replace('/[^A-Za-z0-9]+/', ' ', $value);
    $parts = array_filter(explode(' ', (string) $clean));
    $pascal = implode('_', array_map('strtoupper', $parts));

    if ($pascal === '') {
        return 'UNKNOWN';
    }

    if (preg_match('/^\d/', $pascal)) {
        $pascal = 'CASE' . $pascal;
    }

    return $pascal;
}
