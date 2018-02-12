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

use DynamicProductAttributes\Model\DynamicProductAttribute;
use DynamicProductAttributes\Model\DynamicProductAttributeQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\AttributeTemplate;
use Thelia\Model\AttributeTemplateQuery;

/**
 * Class ExportedOrders
 * @package DynamicProductAttributes\Loop
 *
 * @method int getCartItemId()
 * @method int getAttributeId()
 */
class AttributeValue extends BaseLoop implements PropelSearchLoopInterface
{
    public function buildModelCriteria()
    {
        $query = DynamicProductAttributeQuery::create()
            ->filterByCartItemId($this->getCartItemId());

        if (null !== $attributeId = $this->getAttributeId()) {
            $query->filterByAttributeId($attributeId);
        }

        return $query;
    }

    /**
     * @param LoopResult $loopResult
     * @return LoopResult
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function parseResults(LoopResult $loopResult)
    {
        $locale = $this->getCurrentRequest()->getSession()->getLang()->getLocale();

        /** @var DynamicProductAttribute $at */
        foreach ($loopResult->getResultDataCollection() as $at) {
            $loopResultRow = new LoopResultRow();

            $attribute = $at->getAttribute();
            $attribute->setLocale($locale);

            $loopResultRow
                ->set("ATTRIBUTE_ID", $at->getAttributeId())
                ->set("ATTRIBUTE_VALUE", $at->getAttributeValue())

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
            Argument::createIntTypeArgument("cart_item_id", null, true),
            Argument::createIntTypeArgument("attribute_id")
        );
    }
}
