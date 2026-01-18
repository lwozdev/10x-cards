<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\Model\CardOrigin;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Doctrine DBAL Type for CardOrigin enum.
 *
 * Maps PostgreSQL enum type 'card_origin' to PHP enum CardOrigin.
 *
 * PostgreSQL enum definition:
 * CREATE TYPE card_origin AS ENUM ('ai', 'manual');
 *
 * This type must be registered in doctrine.yaml:
 * doctrine:
 *   dbal:
 *     types:
 *       card_origin: App\Infrastructure\Doctrine\Type\CardOriginType
 */
class CardOriginType extends Type
{
    public const NAME = 'card_origin';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'card_origin';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?CardOrigin
    {
        if (null === $value) {
            return null;
        }

        return CardOrigin::from($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof CardOrigin) {
            return $value->value;
        }

        throw new \InvalidArgumentException(sprintf('Expected %s, got %s', CardOrigin::class, get_debug_type($value)));
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
