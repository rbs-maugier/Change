<?php
namespace Rbs\Commerce\Cart;

/**
* @name \Rbs\Commerce\Cart\DefaultCartValidation
*/
class DefaultCartValidation
{
	/**
	 * Event Params: cart, errors, lockForOwnerId, commerceServices
	 * @param \Change\Events\Event $event
	 */
	public function execute(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof \Rbs\Commerce\Interfaces\Cart)
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();

			/* @var $errors \ArrayObject */
			$errors = $event->getParam('errors');

			if (!$cart->getWebStoreId())
			{
				$message = $i18nManager->trans('m.rbs.commerce.front.cart_without_webstore', array('ucf'));
				$err = new CartError($message, null);
				$errors[] = $err;
				return;
			}

			foreach ($cart->getLines() as $line)
			{
				if (!$line->getQuantity())
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_quantity', array('ucf'), array('number' => $line->getNumber()));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
				elseif (count($line->getItems()) === 0)
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_sku', array('ucf'), array('number' => $line->getNumber()));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
				elseif ($line->getUnitPriceValue() === null)
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_price', array('ucf'), array('number' => $line->getNumber()));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
			}

			$reservations = $cart->getCommerceServices()->getCartManager()->getReservations($cart);
			if (count($reservations))
			{
				$unreserved = $cart->getCommerceServices()->getStockManager()->setReservations($cart->getIdentifier(), $reservations);
				if (count($unreserved))
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.cart_reservation_error', array('ucf'));
					$err = new CartError($message);
					$errors[] = $err;
				}
			}
			else
			{
				$cart->getCommerceServices()->getStockManager()->unsetReservations($cart->getIdentifier());
			}
		}
	}
}