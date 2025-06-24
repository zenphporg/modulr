<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
  ->withPaths([
    __DIR__.'/src',
    //__DIR__.'/tests',
  ])

    // Skip directories and files that shouldn't be modified
  ->withSkip([
    __DIR__.'/vendor',
    __DIR__.'/node_modules',
    __DIR__.'/build',
    __DIR__.'/dist',
    // Skip any generated or compiled files
    '*/stubs/*',
    '*/stub/*',
  ])

    // Performance optimizations
  ->withCache(__DIR__.'/var/cache/rector', FileCacheStorage::class)
  ->withImportNames(removeUnusedImports: true)

    // PHP 8.3 modernization
  ->withPhpSets(php83: true)

    // Core rule sets (excluding formatting since handled separately)
  ->withPreparedSets(
    deadCode: true,           // Safe dead code removal + docblock cleanup
    codeQuality: true,        // Code quality improvements
    typeDeclarations: true,   // Type declarations + typed properties + void returns
    privatization: true,      // Visibility improvements
    naming: true,             // Naming conventions
    earlyReturn: true,        // Early return patterns
    instanceOf: true          // instanceof optimizations
  )

    // Specific rules not covered by prepared sets
  ->withRules([
    // Strict types declaration
    DeclareStrictTypesRector::class,

    // PHP 8.3 Override attribute for overridden methods
    AddOverrideAttributeToOverriddenMethodsRector::class,
  ])

    // Configure specific rules
  ->withConfiguredRule(AddOverrideAttributeToOverriddenMethodsRector::class, [
    'allow_override_empty_method' => false,
  ]);
