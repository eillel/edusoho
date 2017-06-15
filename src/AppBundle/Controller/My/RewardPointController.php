<?php

namespace AppBundle\Controller\My;

use Symfony\Component\HttpFoundation\Request;
use AppBundle\Common\Paginator;
use AppBundle\Controller\BaseController;

class RewardPointController extends BaseController
{
    public function indexAction(Request $request)
    {
        $user = $this->getCurrentUser();
        $conditions['userId'] = $user['id'];
        $accountFlowCount = $this->getAccountFlowService()->countAccountFlows($conditions);
        $paginator = new Paginator(
            $request,
            $accountFlowCount,
            10
        );

        $accountFlows = $this->getAccountFlowService()->searchAccountFlows(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $settings = $this->getSettingService()->get('reward_point', array());
        $account = $this->getAccountService()->getAccountByUserId($user['id']);

        return $this->render(
            'reward-point/index.html.twig',
            array(
            'accountFlows' => $accountFlows,
            'paginator' => $paginator,
            'account' => $account,
            'settings' => $settings,
            )
        );
    }

    public function mallAction(Request $request)
    {
        $conditions = $request->query->all();
        $conditions['status'] = 'published';

        $count = $this->getRewardPointProductService()->countProducts($conditions);

        $paginator = new Paginator(
            $request,
            $count,
            16
        );

        $products = $this->getRewardPointProductService()->searchProducts(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $settings = $this->getSettingService()->get('reward_point', array());

        return $this->render(
            'reward-point/mall.html.twig',
            array(
                'products' => $products,
                'paginator' => $paginator,
                'count' => $count,
                'settings' => $settings,
            )
        );
    }

    public function recordAction(Request $request)
    {
        $user = $this->getCurrentUser();
        $conditions['userId'] = $user['id'];
        $productOrderCount = $this->getProductOrderService()->countProductOrders($conditions);
        $paginator = new Paginator(
            $request,
            $productOrderCount,
            10
        );

        $productOrders = $this->getProductOrderService()->searchProductOrders(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $settings = $this->getSettingService()->get('reward_point', array());

        return $this->render(
            'reward-point/record.html.twig',
            array(
                'productOrders' => $productOrders,
                'paginator' => $paginator,
                'settings' => $settings,
            )
        );
    }

    public function ruleAction(Request $request)
    {
        $settings = $this->getSettingService()->get('reward_point', array());

        return $this->render(
            'reward-point/rule.html.twig',
            array(
                'settings' => $settings,
            )
        );
    }

    public function exchangeAction(Request $request, $productId)
    {
        if ($request->getMethod() == 'POST') {
            $order = $request->request->all();
            $user = $this->getCurrentUser();
            $order['userId'] = $user['id'];
            $order['productId'] = $productId;

            $result = $this->getRewardPointProductOrderService()->exchangeProduct($order);

            if ($result) {
                $result = array('success' => true, 'message' => '兑换成功');
            } else {
                $result = array('success' => false, 'message' => '余额不足，兑换失败');
            }

            return $this->createJsonResponse($result);
        }

        $product = $this->getRewardPointProductService()->getProduct($productId);
        $settings = $this->getSettingService()->get('reward_point', array());

        return $this->render(
            'reward-point/exchange-product-modal.html.twig',
            array(
                'product' => $product,
                'settings' => $settings,
            )
        );
    }

    public function productDetailAction(Request $request, $productId)
    {
        $product = $this->getRewardPointProductService()->getProduct($productId);
        $settings = $this->getSettingService()->get('reward_point', array());

        return $this->render(
            'reward-point/product-detail.html.twig',
            array(
                'product' => $product,
                'settings' => $settings,
            )
        );
    }

    protected function getRewardPointProductOrderService()
    {
        return $this->createService('RewardPoint:ProductOrderService');
    }

    protected function getRewardPointProductService()
    {
        return $this->createService('RewardPoint:ProductService');
    }

    protected function getAccountFlowService()
    {
        return $this->createService('RewardPoint:AccountFlowService');
    }

    protected function getProductOrderService()
    {
        return $this->createService('RewardPoint:ProductOrderService');
    }

    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    protected function getAccountService()
    {
        return $this->createService('RewardPoint:AccountService');
    }
}
