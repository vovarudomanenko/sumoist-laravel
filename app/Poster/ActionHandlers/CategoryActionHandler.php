<?php

namespace App\Poster\ActionHandlers;

use App\Poster\PosterCategory;
use App\Poster\SalesboxCategory;
use App\Salesbox\Facades\SalesboxApi;

class CategoryActionHandler extends AbstractActionHandler
{
    public $pendingCategoryIdsForCreation = [];
    public $pendingCategoryIdsForUpdate = [];

    public function handle(): bool
    {
        if ($this->isAdded() || $this->isRestored() || $this->isChanged()) {
            SalesboxApi::authenticate(salesbox_fetchAccessToken());

            $salesbox_categoryIds = collect(salesbox_fetchCategories())
                ->filter(function ($id) {
                    // todo: should I ignore all salesbox categories without external id?
                    // todo: or should I delete them as well in the synchronization process
                    return !empty($id);
                })
                ->pluck('externalId');

            $posterId = $this->getObjectId();

            $poster_category = poster_fetchCategory($posterId);

            if ($salesbox_categoryIds->contains($posterId) && !in_array($posterId, $this->pendingCategoryIdsForUpdate)) {
                $this->pendingCategoryIdsForUpdate[] = $posterId;
            }

            if (!$salesbox_categoryIds->contains($posterId) && !in_array($posterId, $this->pendingCategoryIdsForCreation)) {
                $this->pendingCategoryIdsForCreation[] = $posterId;
            }

            if (!!$poster_category->parent_category) {
                $this->checkParent($poster_category->parent_category);
            }

            // make updates
            if (count($this->pendingCategoryIdsForCreation) > 0) {
                $categories = collect(poster_fetchCategories())
                    ->whereIn('category_id', $this->pendingCategoryIdsForCreation)
                    ->map(function($attributes) {
                        return new PosterCategory($attributes);
                    })
                    ->map(function(PosterCategory $poster_category) {
                        $salesbox_category = $poster_category->asSalesboxCategory();

                        if($poster_category->hasParentCategory()) {
                            // check if parent category already exists in salesbox
                            // and set its internalId as parentId
                            // I do this because, there may be situation when parent category was created manually
                            // therefore internalId was autogenerated and doesn't match our posterId
                            $salesbox_parentCategoryAttributes = salesbox_fetchCategory($poster_category->getParentCategory());
                            if ($salesbox_parentCategoryAttributes) {
                                $salesbox_category->setParentId($salesbox_parentCategoryAttributes->internalId);
                            }
                        }
                        return $salesbox_category;
                    })
                    ->map(function (SalesboxCategory $category) {
                        return [
                            'available'         => $category->getAvailable(),
                            'externalId'        => $category->getExternalId(),
                            'names'             => $category->getNames(),
                            'descriptions'      => $category->getDescriptions(),
                            'photos'            => $category->getPhotos(),
                            'internalId'        => $category->getInternalId(),
                            'previewURL'        => $category->getPreviewUrl(),
                            'originalURL'       => $category->getOriginalUrl(),
                            'parentId'          => $category->getParentId(),
                        ];
                    })
                    ->values()// array must be property indexed, otherwise salesbox api will fail
                    ->toArray();

                SalesboxApi::createManyCategories([
                    'categories' => $categories
                ]);
            }

            if (count($this->pendingCategoryIdsForUpdate) > 0) {

                $categories = collect(poster_fetchCategories())
                    ->whereIn('category_id', $this->pendingCategoryIdsForUpdate)
                    ->map(function($attributes) {
                        return new PosterCategory($attributes);
                    })
                    ->map(function(PosterCategory $poster_category) {
                        $salesbox_category = $poster_category->asSalesboxCategory();


                        if($poster_category->hasParentCategory()) {
                            $salesbox_parentCategoryAttributes = salesbox_fetchCategory($poster_category->getParentCategory());
                            if ($salesbox_parentCategoryAttributes) {
                                $salesbox_category->setParentId($salesbox_parentCategoryAttributes->internalId);
                            }
                        }

                        // internal id is used to reference parent id
                        $salesbox_categoryAttributes = salesbox_fetchCategory($poster_category->getCategoryId());

                        if ($salesbox_categoryAttributes) {
                            $salesbox_category->setInternalId($salesbox_categoryAttributes->internalId);
                            $salesbox_category->setId($salesbox_categoryAttributes->id);
                        }
                        return $salesbox_category;
                    })
                    ->map(function (SalesboxCategory $category) {
                        $salesbox_category = salesbox_fetchCategory($category->getExternalId());

                        $json =  [
                            'id'                => $category->getId(),
                            'externalId'        => $category->getExternalId(),
                            'internalId'        => $category->getInternalId(),
                            'parentId'          => $category->getParentId(),
                            'names'             => $category->getNames(),
                            'previewURL'        => $category->getPreviewUrl(),
                            'originalURL'       => $category->getOriginalUrl(),
                            'available'         => $category->getAvailable(),
                            //'descriptions'      => $category->getDescriptions(),
                            //'photos'            => $category->getPhotos(),
                        ];
                        // update photo if it isn't already present in salesbox
                        if(!$salesbox_category->previewURL) {
                            $json['previewURL'] = $category->getPreviewUrl();
                            $json['originalURL' ] = $category->getOriginalUrl();
                        }

                        return $json;
                    })
                    ->values() // array must be property indexed, otherwise salesbox api will fail
                    ->toArray();

                SalesboxApi::updateManyCategories([
                    'categories' => $categories
                ]);
            }


        }

        if ($this->isRemoved()) {
            SalesboxApi::authenticate(salesbox_fetchAccessToken());

            $salesbox_category = salesbox_fetchCategory($posterId);

            if (!$salesbox_category) {
                // todo: should I throw exception if category doesn't exist?
                return false;
            }

            // recursively=true is important,
            // without this param salesbox will throw an error if the category being deleted has child categories
            SalesboxApi::deleteCategory([
                'id' => $salesbox_category->id,
                'recursively' => true
            ], []);
        }

        return true;
    }

    public function checkParent($posterId)
    {
        $salesbox_category = salesbox_fetchCategory($posterId);
        $poster_category = poster_fetchCategory($posterId);

        if (!$salesbox_category) {
            $this->pendingCategoryIdsForCreation[] = $posterId;
        }

        if (!!$poster_category->parent_category) {
            $this->checkParent($poster_category->parent_category);
        }
    }


}
