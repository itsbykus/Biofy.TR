<?php

namespace App\Blocks;

use App\Blocks\Types;
use InvalidArgumentException;

class BlockRegistry
{
    /** @var array<string, BlockType> */
    private array $types = [];

    public function __construct()
    {
        foreach ([
            Types\LinkBlock::class,
            Types\SocialIconsBlock::class,
            Types\HeadingBlock::class,
            Types\TextBlock::class,
            Types\DividerBlock::class,
            Types\ImageBlock::class,
            Types\VideoBlock::class,
            Types\YoutubeBlock::class,
            Types\MusicBlock::class,
            Types\EmailBlock::class,
            Types\PhoneBlock::class,
            Types\WhatsappBlock::class,
            Types\MapBlock::class,
            Types\ContactBlock::class,
            Types\FaqBlock::class,
            Types\ProductBlock::class,
            Types\GalleryBlock::class,
            Types\DonationBlock::class,
            Types\CalendarBlock::class,
            Types\LinkGroupBlock::class,
        ] as $class) {
            $type = new $class;
            $this->types[$type->key()] = $type;
        }
    }

    public function get(string $key): BlockType
    {
        return $this->types[$key] ?? throw new InvalidArgumentException("Unknown block type [{$key}].");
    }

    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }

    /** @return array<string, BlockType> */
    public function all(): array
    {
        return $this->types;
    }

    public function keys(): array
    {
        return array_keys($this->types);
    }

    /** @return list<string> */
    public function linkTypes(): array
    {
        return array_keys(array_filter($this->types, fn (BlockType $t) => $t->countsAsLink()));
    }

    /** @return array<string, array<string, BlockType>> */
    public function grouped(): array
    {
        $groups = [];

        foreach ($this->types as $key => $type) {
            $groups[$type->group()][$key] = $type;
        }

        return $groups;
    }
}
