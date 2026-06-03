<?php
// Thumbnail generation disabled: this server does not have ffmpeg.
// Video uploads still work normally; thumbnails must be provided manually.

function generate_thumbnail_for_video(string $videoUrl, string $id): ?string {
    return null;
}
