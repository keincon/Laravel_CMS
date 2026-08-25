<?php

namespace App\Support;

trait HasSeoFields
{
    public function initializeHasSeoFields(): void
    {
        $this->fillable = array_values(array_unique(array_merge($this->fillable ?? [], [
            'seo_title',
            'seo_description',
            'seo_canonical',
            'seo_robots',
            'seo_image_id',
            'og_title',
            'og_description',
            'og_image_id',
            'og_type',
            'featured_image_id',
        ])));
    }

    public function seoImage()
    {
        return $this->belongsTo(\App\Models\Media::class, 'seo_image_id');
    }

    public function ogImage()
    {
        return $this->belongsTo(\App\Models\Media::class, 'og_image_id');
    }

    public function featuredImage()
    {
        return $this->belongsTo(\App\Models\Media::class, 'featured_image_id');
    }
}
