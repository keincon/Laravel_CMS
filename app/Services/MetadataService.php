<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MetaType;
use App\Models\CmsSetting;
use App\Models\Content;
use App\Models\ContentMeta;
use App\Models\Media;
use App\Models\MediaMeta;
use App\Models\Term;
use App\Models\TermMeta;
use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Generic metadata read/write for content, user, term, media, and site settings.
 */
final class MetadataService
{
    public function get(Model $owner, string $key, mixed $default = null): mixed
    {
        $row = $this->queryFor($owner)->where('key', $key)->first();

        if ($row === null) {
            return $default;
        }

        $type = $row->type instanceof MetaType ? $row->type : MetaType::tryFrom((string) $row->type) ?? MetaType::String;

        return $type->cast($row->value);
    }

    public function set(Model $owner, string $key, mixed $value, MetaType|string $type = MetaType::String): Model
    {
        $metaType = $type instanceof MetaType ? $type : MetaType::from($type);
        $serialized = $metaType->serialize($value);

        return $this->queryFor($owner)->updateOrCreate(
            $this->ownerAttributes($owner) + ['key' => $key],
            [
                'type' => $metaType->value,
                'value' => $serialized,
            ],
        );
    }

    public function forget(Model $owner, string $key): void
    {
        $this->queryFor($owner)->where('key', $key)->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function all(Model $owner): array
    {
        $result = [];

        foreach ($this->queryFor($owner)->get() as $row) {
            $type = $row->type instanceof MetaType ? $row->type : MetaType::tryFrom((string) $row->type) ?? MetaType::String;
            $result[$row->key] = $type->cast($row->value);
        }

        return $result;
    }

    private function queryFor(Model $owner)
    {
        return match (true) {
            $owner instanceof Content => ContentMeta::query()->where('content_id', $owner->getKey()),
            $owner instanceof User => UserMeta::query()->where('user_id', $owner->getKey()),
            $owner instanceof Term => TermMeta::query()->where('term_id', $owner->getKey()),
            $owner instanceof Media => MediaMeta::query()->where('media_id', $owner->getKey()),
            $owner instanceof CmsSetting => throw new InvalidArgumentException('Use SettingsService for site meta.'),
            default => throw new InvalidArgumentException('Unsupported metadata owner: '.$owner::class),
        };
    }

    /**
     * @return array<string, int>
     */
    private function ownerAttributes(Model $owner): array
    {
        return match (true) {
            $owner instanceof Content => ['content_id' => (int) $owner->getKey()],
            $owner instanceof User => ['user_id' => (int) $owner->getKey()],
            $owner instanceof Term => ['term_id' => (int) $owner->getKey()],
            $owner instanceof Media => ['media_id' => (int) $owner->getKey()],
            default => throw new InvalidArgumentException('Unsupported metadata owner: '.$owner::class),
        };
    }
}
