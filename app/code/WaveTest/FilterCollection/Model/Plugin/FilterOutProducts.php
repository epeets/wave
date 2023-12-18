<?php
/**
 *
 * Project: Wave Test
 * Author: Eshcole Peets
 *
 */

namespace WaveTest\FilterCollection\Model\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\OpenSearch\Model\SearchClient;

class FilterOutProducts
{
    private ProductRepositoryInterface $productRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder      $searchCriteriaBuilder
    )
    {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param SearchClient $subject
     * @param $result
     * @return array
     */
    public function afterQuery(SearchClient $subject, $result): array
    {
        if (!isset($result['hits']['hits'])) {
            return $result;
        }

        $hits = $result['hits']['hits'];
        $resultProductIds = [];
        foreach ($hits as $key => $hit) {
            $productId = $hit['fields']['_id'][0];
            $resultProductIds[] = $productId;
        }

        $overLimitProducts = $this->priceLimit($resultProductIds);

        if ($overLimitProducts) {
            foreach ($hits as $key => $hit) {
                $productId = $hit['fields']['_id'][0];
                if (in_array($productId, $overLimitProducts) !== false) {
                    unset($result['hits']['hits'][$key]);
                }
            }
        }

        return $result;
    }

    /**
     * @param $resultProductIds
     * @return array|null
     */
    private function priceLimit($resultProductIds): ?array
    {
        $productSearchCriteria = $this->searchCriteriaBuilder->addFilter('entity_id', $resultProductIds, 'in')
            ->addFilter('price', 100.00, 'qteq')
            ->create();

        $collection = $this->productRepository->getList($productSearchCriteria)->getItems();

        if (count($collection) > 0) {
            $ids = [];
            foreach ($collection as $product) {
                $ids[] = $product->getId();
            }

            return $ids;
        }

        return null;
    }

}
