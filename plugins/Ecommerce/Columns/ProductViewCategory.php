<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Ecommerce\Columns;

use Piwik\Columns\DimensionSegmentFactory;
use Piwik\Columns\Discriminator;
use Piwik\Columns\Join\ActionNameJoin;
use Piwik\Common;
use Piwik\Db;
use Piwik\Log;
use Piwik\Plugin\Dimension\ActionDimension;
use Piwik\Plugin\Segment;
use Piwik\Segment\SegmentsList;
use Piwik\Tracker\Action;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visitor;

class ProductViewCategory extends ActionDimension
{
    protected $type = self::TYPE_TEXT;
    protected $nameSingular = 'Goals_ProductCategory';
    protected $columnName = 'idaction_product_cat';
    protected $segmentName = 'productViewCategory';
    protected $columnType = 'INT(10) UNSIGNED NULL';
    protected $category = 'Goals_Ecommerce';
    protected $categoryNumber = 1;

    public function configureSegments(SegmentsList $segmentsList, DimensionSegmentFactory $dimensionSegmentFactory)
    {
        $individualProductCategorySegments = $this->getProductCategorySegments(ProductCategory::PRODUCT_CATEGORY_COUNT);

        // add individual productCategoryN segments for use as a union (these segments are not available through the UI/API)
        foreach ($individualProductCategorySegments as $i => $productCategoryName) {
            $productCategoryColumnName = 'idaction_product_cat';
            if ($i > 0) {
                $productCategoryColumnName .= $i + 1;
            }

            $segment = new Segment();
            $segment->setCategory($this->category);
            $segment->setType('dimension');
            $segment->setName($this->getName() . ' ' . ($i + 1));
            $segment->setSegment($productCategoryName);
            $segment->setSqlFilter('\\Piwik\\Tracker\\TableLogAction::getIdActionFromSegment');
            $segment->setSqlSegment('log_link_visit_action.' . $productCategoryColumnName);
            $segment->setIsInternal(true);
            $segmentsList->addSegment($dimensionSegmentFactory->createSegment($segment));
        }

        // add a union of these individual columns as productCategory
        $segment = new Segment();
        $segment->setCategory($this->category);
        $segment->setType('dimension');
        $segment->setSegment('productViewCategory');
        $segment->setName($this->getName());
        $segment->setUnionOfSegments($individualProductCategorySegments);
        $segmentsList->addSegment($dimensionSegmentFactory->createSegment($segment));
    }

    private function getProductCategorySegments($categoryCount)
    {
        $result = [];
        for ($i = 0; $i < $categoryCount; ++$i) {
            $segmentName = 'productViewCategory' . ($i + 1);
            $result[] = $segmentName;
        }
        return $result;
    }

    public function getDbColumnJoin()
    {
        return new ActionNameJoin();
    }

    public function getDbDiscriminator()
    {
        return new Discriminator('log_action', 'type', Action::TYPE_ECOMMERCE_ITEM_CATEGORY);
    }

    public function onLookupAction(Request $request, Action $action)
    {
        $categories = Common::unsanitizeInputValue($request->getParam('_pkc'));

        if ($request->hasParam('_pkc')) {
            $categories = $this->handleCategoryParam($categories);

            return $categories[$this->categoryNumber - 1] ?? false;
        }

        // fall back to custom variables (might happen if old logs are replayed)
        $customVariables = $request->getCustomVariablesInPageScope();
        if (isset($customVariables['custom_var_k5']) && $customVariables['custom_var_k5'] === '_pkc') {
            $categories = $this->handleCategoryParam($customVariables['custom_var_v5'] ?? '');

            return $categories[$this->categoryNumber - 1] ?? false;
        }

        return parent::onLookupAction($request, $action);
    }

    protected function handleCategoryParam($categories)
    {
        if (0 === strpos($categories, '["')) {
            $categories = array_values(array_filter(@\json_decode($categories, true)));
        } else {
            $categories = [$categories];
        }

        return $categories;
    }

    public function getActionId()
    {
        return Action::TYPE_ECOMMERCE_ITEM_CATEGORY;
    }
}