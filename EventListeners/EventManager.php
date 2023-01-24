<?php
/*************************************************************************************/
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace DynamicProductAttributes\EventListeners;

use DynamicProductAttributes\Model\DynamicProductAttribute;
use DynamicProductAttributes\Model\DynamicProductAttributeQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Event\Cart\CartEvent;
use Thelia\Core\Event\Cart\CartItemDuplicationItem;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\TheliaFormEvent;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Translation\Translator;
use Thelia\Model\Attribute;
use Thelia\Model\AttributeTemplate;
use Thelia\Model\AttributeTemplateQuery;
use Thelia\Model\CartItem;
use Thelia\Model\CartItemQuery;
use Thelia\Model\OrderProduct;
use Thelia\Model\OrderProductAttributeCombination;
use Thelia\Model\OrderProductQuery;
use Thelia\Model\ProductQuery;
use Thelia\Tools\I18n;

class EventManager implements EventSubscriberInterface
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var Translator */
    protected $translator;

    /**
     * EventManager constructor.
     * @param RequestStack $requestStack
     * @param Translator $translator
     */
    public function __construct(RequestStack $requestStack, Translator $translator)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    /**
     * @param $productId
     * @return array|\Propel\Runtime\Collection\ObjectCollection
     */
    protected function getDynamicAttributes($productId)
    {
        if (! empty($productId) && null !== $product = ProductQuery::create()->findPk($productId)) {
            return AttributeTemplateQuery::create()
                ->filterByTemplateId($product->getTemplateId())
                    ->useAttributeQuery(null, Criteria::LEFT_JOIN)
                        ->useAttributeAvQuery(null, Criteria::LEFT_JOIN)
                            ->filterById(null, Criteria::ISNULL)
                        ->endUse()
                    ->endUse()
                ->find();
        } else {
            return [];
        }
    }
    /**
     * @param TheliaFormEvent $event
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function addFieldsToForm(TheliaFormEvent $event)
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        $productId = $request->get('product_id');

        if (! empty($productId) && null !== $product = ProductQuery::create()->findPk($productId)) {
            // Get all product attributes which do not have any AttributesAv
            $atList = $this->getDynamicAttributes($productId);

            $locale = $request->getSession()->getLang()->getLocale();

            /** @var AttributeTemplate $at */
            foreach ($atList as $at) {
                $event->getForm()->getFormBuilder()->add(
                    "dynamic_attribute_" . $at->getAttributeId(),
                    TextType::class,
                    [
                        'required' => true,
                        'constraints' => [ new NotBlank() ],
                        'label' => $at->getAttribute()->setLocale($locale)->getTitle(),
                        'label_attr'  => [
                            'help' => $at->getAttribute()->setLocale($locale)->getChapo()
                        ]
                    ]
                );
            }
        }
    }

    protected function getDynamicAttributeValuesFromCartAddForm()
    {
        $request = $this->requestStack->getCurrentRequest();

        $data = $request->get('thelia_cart_add');

        $result = [];

        foreach ($data as $name => $value) {
            if (0 === strpos($name, 'dynamic_attribute_')) {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    /**
     * @param CartEvent $event
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function processDynamicAttributes(CartEvent $event)
    {
        $cartItem = $event->getCartItem();

        // Si ce cart item a déjà une declibre définie, ne pas en rajouter une
        if (DynamicProductAttributeQuery::create()->filterByCartItemId($cartItem->getId())->count() == 0) {
            $atList = $this->getDynamicAttributes($cartItem->getProductId());

            $formData = $this->getDynamicAttributeValuesFromCartAddForm();

            /** @var AttributeTemplate $at */
            foreach ($atList as $at) {
                if (isset($formData['dynamic_attribute_' . $at->getAttributeId()])) {
                    $value = $formData['dynamic_attribute_' . $at->getAttributeId()];

                    if (!empty($value)) {
                        $dpa = new DynamicProductAttribute();
                        $dpa->setCartItemId($cartItem->getId())
                            ->setAttributeId($at->getAttributeId())
                            ->setAttributeValue($value)
                            ->save();
                    }
                }
            }
        }
    }

    /**
     * @param CartItemDuplicationItem $event
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function cartDuplicateItem(CartItemDuplicationItem $event)
    {
        $dpaList = DynamicProductAttributeQuery::create()->findByCartItemId($event->getOldItem()->getId());

        /** @var DynamicProductAttribute $original */
        foreach ($dpaList as $original) {
            $copy = new DynamicProductAttribute();

            $original->copyInto($copy);

            $copy
                ->setCartItemId($event->getNewItem()->getId())
                ->save();
        }
    }

    /**
     * @param OrderEvent $event
     *
     * @throws \Thelia\Exception\TheliaProcessException
     * @param $eventName
     * @param EventDispatcherInterface $dispatcher
     * @throws \Exception
     */
    public function createOrder(OrderEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        $locale = $this->requestStack->getCurrentRequest()->getSession()->getLang()->getLocale();

        $orderProducts = OrderProductQuery::create()->filterByOrderId($event->getOrder()->getId());

        /** @var OrderProduct $orderProduct */
        foreach ($orderProducts as $orderProduct) {
            $dpaList = DynamicProductAttributeQuery::create()->findByCartItemId($orderProduct->getCartItemId());

            /** @var DynamicProductAttribute $dpa */
            foreach ($dpaList as $dpa) {
                /** @var Attribute $attribute */
                $attribute = I18n::forceI18nRetrieving($locale, 'Attribute', $dpa->getAttributeId());

                $orderAttributeCombination = new OrderProductAttributeCombination();
                $orderAttributeCombination
                    ->setOrderProductId($orderProduct->getId())
                    ->setAttributeTitle($attribute->getTitle())
                    ->setAttributeChapo($attribute->getChapo())
                    ->setAttributeDescription($attribute->getDescription())
                    ->setAttributePostscriptum($attribute->getPostscriptum())
                    ->setAttributeAvTitle($dpa->getAttributeValue())
                    ->save();
            }
        }
    }

    /**
     * Find a specific record in CartItem table using the current CartEvent
     *
     * @param CartEvent $event the cart event
     */
    public function findCartItem(CartEvent $event)
    {
        $cartItems = CartItemQuery::create()
            ->filterByCartId($event->getCart()->getId())
            ->filterByProductId($event->getProduct())
            ->filterByProductSaleElementsId($event->getProductSaleElementsId())
            ->find()
        ;

        $formData = $this->getDynamicAttributeValuesFromCartAddForm();

        /** @var CartItem $cartItem */
        foreach ($cartItems as $cartItem) {
            // Check if all dynamic attributes values are the same.
            $dpaList = DynamicProductAttributeQuery::create()
                ->filterByCartItemId($cartItem->getId())
                ->find()
            ;

            $found = true;

            /** @var DynamicProductAttribute $dpa */
            foreach ($dpaList as $dpa) {
                if (isset($formData['dynamic_attribute_' . $dpa->getAttributeId()])) {
                    if ($dpa->getAttributeValue() != $formData['dynamic_attribute_' . $dpa->getAttributeId()]) {
                        // Not equal = no match.
                        $found = false;
                        break;
                    }
                }
            }

            if ($found) {
                $event->setCartItem($cartItem);

                break;
            }
        }

        // We did the job.
        $event->stopPropagation();
    }


    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::FORM_BEFORE_BUILD . ".thelia_cart_add" => ['addFieldsToForm', 128],
            TheliaEvents::CART_ADDITEM => ['processDynamicAttributes', 10],
            TheliaEvents::CART_ITEM_DUPLICATE  => ['cartDuplicateItem', 130 ],
            TheliaEvents::ORDER_BEFORE_PAYMENT  => ['createOrder', 130 ],
            TheliaEvents::CART_FINDITEM => array("findCartItem", 130),
        ];
    }
}
