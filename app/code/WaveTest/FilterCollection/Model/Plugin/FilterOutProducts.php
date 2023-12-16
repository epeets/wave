<?php
/**
 *
 * Project: Wave Test
 * Author: Eshcole Peets
 *
 */

namespace WaveTest\FilterCollection\Model\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\OpenSearch\Model\SearchClient;

class FilterOutProducts
{
    private ProductRepositoryInterface $productRepository;

    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductRepositoryInterface $productRepository
    )
    {
        $this->productRepository = $productRepository;
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
        foreach ($hits as $key => $hit) {
            $productId = $hit['fields']['_id'][0];
            if ($this->priceLimit($productId)) {
                unset($result['hits']['hits'][$key]);
            }
        }
        return $result;
    }

    /**
     * @param $id
     * @return bool
     */
    private function priceLimit($id): bool
    {
        try {
            return $this->productRepository->getById($id)->getPrice() >= 100.00;
        } catch (\Exception $e) {
            // Do nothing.
        }

        return false;
    }

}
