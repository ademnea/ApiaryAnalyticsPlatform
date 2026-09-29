<?php

namespace App\Enums;

use App\Models\HiveAudio;
use App\Models\HivePhoto;
use App\Models\HiveVideo;
use Illuminate\Database\Eloquent\Model;

/** The three media streams, shown as galleries on the Sensor Monitoring pages. */
enum MediaKind: string
{
    case Photo = 'photos';
    case Audio = 'audio';
    case Video = 'video';

    public function label(): string
    {
        return match ($this) {
            self::Photo => 'Photos',
            self::Audio => 'Audio',
            self::Video => 'Video',
        };
    }

    public function singular(): string
    {
        return match ($this) {
            self::Photo => 'photo',
            self::Audio => 'audio clip',
            self::Video => 'video',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Photo => 'bi-images',
            self::Audio => 'bi-mic',
            self::Video => 'bi-camera-video',
        };
    }

    public function routeName(): string
    {
        return 'admin.monitoring.'.$this->value;
    }

    /** @return class-string<Model> */
    public function modelClass(): string
    {
        return match ($this) {
            self::Photo => HivePhoto::class,
            self::Audio => HiveAudio::class,
            self::Video => HiveVideo::class,
        };
    }

    /** Only audio and video rows carry duration_seconds. */
    public function hasDuration(): bool
    {
        return $this !== self::Photo;
    }
}
