<?php

namespace App\Poster\ActionHandlers;

use App\Poster\Entities\Category;
use App\Poster\PosterApiException;
use poster\src\PosterApi;
use App\Salesbox\Facades\SalesboxApi;

class CategoryAddedActionHandler extends AbstractActionHandler {
    public function handle(): bool
    {
        SalesboxApi::authenticate();

        $salesboxCategory = $this->createSalesboxCategoryByPosterId($this->getObjectId());

        return !!$salesboxCategory;
    }

    public function createSalesboxCategoryByPosterId($posterId): ?array {

        $posterCategoryRes = PosterApi::menu()->getCategory([
            'category_id' => $posterId
        ]);

        if (!isset($posterCategoryRes->response)) {
            throw new PosterApiException($posterCategoryRes->error);
        }

        $posterEntity = new Category($posterCategoryRes->response);

        $salesBoxCategoriesRes = SalesboxApi::getCategories();

        $collection = collect($salesBoxCategoriesRes['data']);

        $saleboxCategory = $collection->firstWhere('externalId', $posterId);

        // if it already exists
        if($saleboxCategory) {
            return null;
        }

        $names = [
            [
                'name' => $posterEntity->getName(),
                'lang' => 'uk'
            ]
        ];

        $newSalesBoxCategory = [
            'available' => !$posterEntity->isHidden(),
            'names' => $names,
            'externalId' => $posterId
        ];

        if(!!$posterEntity->getParentCategory()) {
            $salesboxParentCategory = $collection->firstWhere('externalId', $posterEntity->getParentCategory());

            if($salesboxParentCategory) {
                $newSalesBoxCategory['parentId'] = $salesboxParentCategory['internalId'];
            } else {
                $newlyCreatedParent = $this->createSalesboxCategoryByPosterId($posterEntity->getParentCategory());
                if(!is_null($newlyCreatedParent)) {
                    $newSalesBoxCategory['parentId'] = $newlyCreatedParent['internalId'];
                }
            }
        }

        if($posterEntity->getPhoto()) {
            $url = config('poster.url') . $posterEntity->getPhoto();
            $newSalesBoxCategory['previewURL'] = $url;
            $newSalesBoxCategory['originalURL'] = $url;
        }

        $createManyRes = SalesboxApi::createCategory($newSalesBoxCategory);

        return $createManyRes['data']['ids'][0];
    }

}
