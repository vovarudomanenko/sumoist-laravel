<?php

namespace App\Poster;

use App\Poster\meta\PosterProduct_meta;

class PosterProduct implements AsSalesboxOffer {
    /** @property PosterProduct_meta $attributes */
    public $attributes;
    /**
     * @param PosterProduct_meta $attributes
     */
    public function __construct($attributes) {
        $this->attributes = $attributes;
    }

    public function getProductId() {
        return $this->attributes->product_id;
    }

    public function getPhotoOrigin() {
        return $this->attributes->photo_origin;
    }

    public function getPhoto() {
        return $this->attributes->photo;
    }

    public function getMenuCategoryId() {
        return $this->attributes->menu_category_id;
    }

    public function getSpots(): array {
        return $this->attributes->spots;
    }

    public function getFirstSpot() {
        return $this->getSpots()[0];
    }

    public function getCategoryName() {
        return $this->attributes->category_name;
    }

    public function getProductName() {
        return $this->attributes->product_name;
    }

    public function isHidden(): bool {
        // todo: allow choosing a different spot
        $spot = $this->getFirstSpot();
        return $spot->visible == "0";
    }

    public function hasModifications(): bool {
        return isset($this->attributes->modifications);
    }

    public function getPrice(): \stdClass {
        return $this->attributes->price;
    }

    public function hasPhoto(): bool {
        return !!$this->getPhoto();
    }

    public function hasPhotoOrigin(): bool {
        return !!$this->getPhotoOrigin();
    }


    public function asSalesboxOffer(): SalesboxOffer
    {
        $offer = new SalesboxOffer();

        $spot = $this->getFirstSpot();
        $price = intval($this->getPrice()->{$spot->spot_id}) / 100;

        $photos = [];
        if($this->getPhoto() && $this->getPhotoOrigin()) {
            $photos[] =      [
                'url' => Utils::poster_upload_url($this->getPhotoOrigin()),
                'previewURL' => Utils::poster_upload_url($this->getPhoto()),
                'order' => 0,
                'type' => 'image',
                'resourceType' => 'image'
            ];
        }
        $offer->setPhotos($photos);
        $offer->setDescriptions([]);
        $offer->setExternalId($this->getProductId());
        $offer->setCategories([]);
        // to find out categories ids I have to fetch them from salesbox
        // but I won't do it here, I'll do it later

        $offer->setAvailable(!$this->isHidden());
        $offer->setPrice($price);
        $offer->setStockType('endless');
        $offer->setUnits('pc');

        if($this->hasPhoto()) {
            $offer->setPreviewUrl(Utils::poster_upload_url($this->getPhoto()));
        }

        if($this->hasPhotoOrigin()) {
            $offer->setOriginalUrl(Utils::poster_upload_url($this->getPhotoOrigin()));
        }

        $offer->setNames([
            [
                'name' => $this->getProductName(),
                'lang' => 'uk' // todo: move this value to config, or fetch it from salesbox api
            ]
        ]);

        return $offer;
    }

}
