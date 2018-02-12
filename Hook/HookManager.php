<?php
/*************************************************************************************/
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace DynamicProductAttributes\Hook;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class HookManager extends BaseHook
{
    public function onCartJavascriptInitialization(HookRenderEvent $event)
    {
        $event
            ->add($this->render('dynamic-product-attributes/cart-item-content.html'))
            ->add($this->addJS('dynamic-product-attributes/assets/js/cart.js'));
    }

    public function onOrderInvoiceJavascriptInitialization(HookRenderEvent $event)
    {
        $event
            ->add($this->render('dynamic-product-attributes/cart-item-content.html'))
            ->add($this->addJS('dynamic-product-attributes/assets/js/order.js'));
    }

    /**
     * Insert the cart view extensions, and the javascript to insert them.
     *
     * @param HookRenderEvent $event
     */
    public function onProductJavascriptInitialization(HookRenderEvent $event)
    {
        $productId = $this->getRequest()->get('product_id');

        if (! empty($productId)) {
            $event
                ->add($this->render(
                    'dynamic-product-attributes/product-add-to-cart.html',
                    [
                        'product_id' => $productId
                    ]
                ))
                ->add($this->addJS('dynamic-product-attributes/assets/js/product.js'));
        }
    }
}
