<?php
/**
 * This module is used for real time processing of
 * Novalnet payment module of customers.
 * Released under the GNU General Public License.
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * @author       Novalnet
 * @copyright(C) Novalnet. All rights reserved. <https://www.novalnet.de/>
 */

namespace Novalnet\Providers;

use Plenty\Plugin\Templates\Twig;

use Novalnet\Helper\PaymentHelper;
use Plenty\Modules\Comment\Contracts\CommentRepositoryContract;
use \Plenty\Modules\Authorization\Services\AuthHelper;

/**
 * Class NovalnetOrderConfirmationDataProvider
 *
 * @package Novalnet\Providers
 */
class NovalnetOrderConfirmationDataProvider
{
    /**
     * Setup the Novalnet transaction comments for the requested order
     *
     * @param Twig $twig
     * @param Arguments $arg
     * @return string
     */
    public function call(Twig $twig, $args)
    {
        $paymentHelper = pluginApp(PaymentHelper::class);
        $order = $args[0];

        if(isset($order->order))
            $order = $order->order;

        foreach($order->properties as $property)
        {
            if($property->typeId == '3' && $paymentHelper->isNovalnetPaymentMethod($property->value))
            {
                $orderId = (int) $order->id;

                $authHelper = pluginApp(AuthHelper::class);
                $orderComments = $authHelper->processUnguarded(
                        function () use ($orderId) {
                            $commentsObj = pluginApp(CommentRepositoryContract::class);
                            $commentsObj->setFilters(['referenceType' => 'order', 'referenceValue' => $orderId]);
                            return $commentsObj->listComments();
                        }
                );

                $comment = '';
                foreach($orderComments as $data)
                {
                    $comment .= (string)$data->text;
                    $comment .= '</br>';
                }

                return $twig->render('Novalnet::NovalnetOrderHistory', ['comments' => html_entity_decode($comment)]);
            }
        }
    }
}
