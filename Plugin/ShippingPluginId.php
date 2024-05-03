<?php
namespace Allfasteners\ShippingPlugin\Plugin;


class ShippingPluginId
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
	protected $_customerSession;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */   
    public function __construct(
		\Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Catalog\Model\Product $product
    ) {
		$this->_customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
		$this->product = $product;
    }

    /**
     * Hide FlatRate shipping method
     *
     * @param \Magento\Quote\Api\ShipmentEstimationInterface $subject
     * @param \Magento\Quote\Api\Data\ShippingMethodInterface[] $methods
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[] $methods
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
	
	public function afterEstimateByAddressId($shippingMethodManagement, $methods)
    {
		$freeshipping = false;
		if($this->_customerSession->isLoggedIn()) {
			$customerGroup = $this->_customerSession->getCustomer()->getGroupId();
			if($customerGroup == 46) {
				$freeshipping = true;
			}
		}

        $shipping_ups_grd = false;
		$shipping_ups_3d = false;
		$shipping_ups_2d = false;
		$shipping_ups_1d = false;
		$shipping_ups_4d = false;
		$shipping_alltruck = false;
		$shipping_ltl = false;
		$shipping = '';
		$weight = 0;
        foreach($methods as $key => &$method) {
			if($freeshipping) {
				if($method->getMethodCode() == 'freeshipping') {
				} else {
					unset($methods[$key]);
				}
			} else {
			$items = $this->checkoutSession->getQuote()->getAllVisibleItems();
			if($shipping == '') {
				foreach($items as $item)
				{
					$_product = $this->product->load($item->getData('product_id'));
					$shippingval = $_product->getResource()->getAttributeRawValue($item->getData('product_id'),'shipping',0);
					if(!$shippingval == '') {
						$shipping = $shippingval.','.$shipping;
					}
					$weight += ($item->getWeight() * $item->getQty());
				}
			}
			
			if($shipping == '') {
				if($weight > 150) {
					$shipping = "5676";
				} else {
					$shipping = "5672,5673,5674,5675,5677,5911";
				}
			}
			
			if(!$shipping == '') {

			$shippingvalues = explode(',',$shipping);

			if($weight > 150) {
				if(!in_array("5676", $shippingvalues)) {
					array_push($shippingvalues, "5676");
				}
			}
			
			if(in_array("5676", $shippingvalues)) {
				if($method->getMethodCode() == '03' || $method->getMethodCode() == '12' || $method->getMethodCode() == '02' || $method->getMethodCode() == '01' || $method->getMethodCode() == '14') {
					unset($methods[$key]);
				}
			} else {
				$shipping_u = array_unique($shippingvalues);
				$shipping_unique = array_diff_assoc($shippingvalues,$shipping_u);
				if(empty($shipping_unique)) {
					$shipping_unique = $shipping_u;
				}
				if(in_array("5672", $shipping_unique)) {
					$shipping_ups_grd = '03';
				}
				if(in_array("5673", $shipping_unique)) {
					$shipping_ups_3d = '12';
				}
				if(in_array("5674", $shipping_unique)) {
					//$shipping_ups_2d = '2DA';
					$shipping_ups_2d = '03';
				}
				if(in_array("5675", $shipping_unique)) {
					//$shipping_ups_1d = '1DA';
					$shipping_ups_1d = '03';
				}
				if(in_array("5677", $shipping_unique)) {
					$shipping_alltruck = 'bestway';
				}
				if(in_array("5911", $shipping_unique)) {
					$shipping_ups_grd = '03';
					$shipping_ups_3d = '12';
					$shipping_ups_2d = '02';
					$shipping_ups_1d = '01';
					$shipping_ups_4d = '14';
				}

            if($method->getMethodCode() == $shipping_ups_grd || $method->getMethodCode() == $shipping_ups_3d || $method->getMethodCode() == $shipping_ups_2d || $method->getMethodCode() == $shipping_ups_1d || $method->getMethodCode() == $shipping_alltruck || $method->getMethodCode() == $shipping_ups_4d) {
				if($method->getMethodCode() == $shipping_alltruck) {
					foreach($methods as $key => &$method) {
						if($method->getMethodCode() == $shipping_alltruck) {
						} else {
							unset($methods[$key]);
						}
					}
					break;
				}
            } else {
				if($method->getMethodCode() == 'bestway') {
					foreach($methods as $key => &$method) {
						if($method->getMethodCode() == 'bestway') {
						} else {
							unset($methods[$key]);
						}
					}
					break;
				}
                unset($methods[$key]);
			}
			}
			}
        }
		}
        return $methods;
    }
}