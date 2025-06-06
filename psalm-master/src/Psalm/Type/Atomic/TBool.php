<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes the `bool` type where the exact value is unknown.
 *
 * @psalm-immutable
 */
class TBool extends Scalar
{
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'bool';
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
    ): ?string {
        return $analysis_php_version_id >= 7_00_00 ? 'bool' : null;
    }
}
