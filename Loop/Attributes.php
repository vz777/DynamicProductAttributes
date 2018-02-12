<?php
/*************************************************************************************/
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace DynamicProductAttributes\Loop;

use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\AttributeTemplate;
use Thelia\Model\AttributeTemplateQuery;
use Thelia\Model\Product;
use Thelia\Model\ProductQuery;

/**
 * Class ExportedOrders
 * @package DynamicProductAttributes\Loop
 *
 * @method int getProductId()
 */
class Attributes extends BaseLoop implements PropelSearchLoopInterface
{
    public function buildModelCriteria()
    {
        $product = ProductQuery::create()->findPk($this->getProductId());

        return AttributeTemplateQuery::create()
            ->filterByTemplateId($product->getTemplateId())
                ->useAttributeQuery(null, Criteria::LEFT_JOIN)
                    ->useAttributeAvQuery(null, Criteria::LEFT_JOIN)
                        ->filterById(null, Criteria::ISNULL)
                    ->endUse()
                ->endUse()
            ;
    }

    /**
     * @param LoopResult $loopResult
     * @return LoopResult
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function parseResults(LoopResult $loopResult)
    {
        $locale = $this->getCurrentRequest()->getSession()->getLang()->getLocale();

        /** @var AttributeTemplate $at */
        foreach ($loopResult->getResultDataCollection() as $at) {
            $loopResultRow = new LoopResultRow();

            $attribute = $at->getAttribute();
            $attribute->setLocale($locale);

            $loopResultRow
                ->set("ATTRIBUTE_ID", $at->getAttributeId())
                ->set("ATTRIBUTE_TITLE", $attribute->getTitle())
                ->set("ATTRIBUTE_CHAPO", $attribute->getChapo())
                ->set("ATTRIBUTE_DESCRIPTION", $attribute->getDescription())
                ->set("ATTRIBUTE_POSTSCRIPTUM", $attribute->getPostscriptum())
                ;

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }

    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument("product_id", null, true)
        );
    }
}
