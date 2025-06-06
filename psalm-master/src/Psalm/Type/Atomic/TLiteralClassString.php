<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

use function preg_quote;
use function preg_replace;
use function str_contains;
use function stripos;
use function strtolower;

/**
 * Denotes a specific class string, generated by expressions like `A::class`.
 *
 * @psalm-immutable
 */
final class TLiteralClassString extends TLiteralString
{
    public function __construct(
        string $value, /**
         * Whether or not this type can represent a child of the class named in $value
         */
        public bool $definite_class = false,
        bool $from_docblock = false,
    ) {
        parent::__construct($value, $from_docblock);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'class-string(' . $this->value . ')';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    #[Override]
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): string {
        return 'string';
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'class-string';
        }

        return $this->value . '::class';
    }

    #[Override]
    public function getAssertionString(): string
    {
        return $this->getKey();
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     */
    #[Override]
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format,
    ): string {
        if ($use_phpdoc_format) {
            return 'string';
        }

        if ($this->value === 'static') {
            return 'static::class';
        }

        if ($this->value === $this_class) {
            return 'self::class';
        }

        if ($namespace && stripos($this->value, $namespace . '\\') === 0) {
            return preg_replace(
                '/^' . preg_quote($namespace . '\\') . '/i',
                '',
                $this->value,
            ) . '::class';
        }

        if (!$namespace && !str_contains($this->value, '\\')) {
            return $this->value . '::class';
        }

        if (isset($aliased_classes[strtolower($this->value)])) {
            return $aliased_classes[strtolower($this->value)] . '::class';
        }

        return '\\' . $this->value . '::class';
    }
}
