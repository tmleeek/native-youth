<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

class CronTest extends \PHPUnit_Framework_TestCase
{
    private $objectManagerHelper;
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Cron
     */
    private $cronModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory |\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderCollectionFactoryMock;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionFactoryMock;

    /**
     * @var \Magento\Sales\Api\OrderPaymentRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderPaymentRepositoryMock;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Fraud|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fraudHelper;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->fraudHelper = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Fraud')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $selectMock = $this
            ->getMockBuilder('Magento\Framework\DB\Select')
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->any())
            ->method('from')
            ->willReturnSelf();
        $selectMock->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $selectMock->expects($this->any())
            ->method('limit')
            ->willReturnSelf();

        $this->connectionMock = $this
            ->getMockBuilder('Magento\Framework\DB\Adapter\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($selectMock);

        $resourceMock = $this
            ->getMockBuilder('Magento\Framework\App\ResourceConnection')
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->connectionMock));

        $this->orderCollectionFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\ResourceModel\Order\CollectionFactory')
            ->setMethods(["create", 'addFieldToFilter', 'load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderCollectionFactoryMock->method('create')->willReturnSelf();
        $this->orderCollectionFactoryMock->method('addFieldToFilter')->willReturnSelf();

        $this->transactionFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderPaymentRepositoryMock = $this
            ->getMockBuilder('Magento\Sales\Api\OrderPaymentRepositoryInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }
    // @codingStandardsIgnoreEnd

    public function testCancelPendingPaymentOrders()
    {
        $fraudModelMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\ResourceModel\Fraud::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fraudModelMock
            ->expects($this->once())
            ->method('getOrdersToCancel')
            ->willReturn([39, 139]);

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(1));

        $orderMock1 = $this
        ->getMockBuilder('Magento\Sales\Model\Order')
        ->disableOriginalConstructor()
        ->getMock();
        $orderMock2 = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderCollectionFactoryMock->method('load')->willReturn([$orderMock1, $orderMock2]);

        $this->cronModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Cron',
            [
                "config"                 => $this->configMock,
                "orderCollectionFactory" => $this->orderCollectionFactoryMock,
                "transactionFactory"     => $this->transactionFactoryMock,
                "orderPaymentRepository" => $this->orderPaymentRepositoryMock,
                "fraudHelper"            => $this->fraudHelper,
                "fraudModel"             => $fraudModelMock
            ]
        );

        $this->cronModel->cancelPendingPaymentOrders();
    }

    public function testCancelOrders()
    {
        $fraudModelMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\ResourceModel\Fraud::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fraudModelMock
            ->expects($this->once())
            ->method('getOrdersToCancel')
            ->willReturn([39, 139]);

        $paymentMock1 = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock1->expects($this->any())
            ->method('getLastTransId')
            ->willReturn("463B3DE6-443F-585B-E75C-C727476DE98F");

        $paymentMock2 = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock2->expects($this->any())
            ->method('getLastTransId')
            ->willReturn("B5690B3B-599B-49DB-AF36-780A7A53F09B");

        $orderMock1 = $this
            ->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->setMethods(['getPayment', 'getEntityId'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock1->expects($this->once())->method('getPayment')->willReturn($paymentMock1);
        $orderMock1->expects($this->once())->method('getEntityId')->willReturn(39);

        $orderMock2 = $this
            ->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->setMethods(['cancel', 'save', 'getPayment', 'getEntityId'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock2->expects($this->once())->method('getPayment')->willReturn($paymentMock2);
        $orderMock2->expects($this->once())->method('cancel')->willReturnSelf();
        $orderMock2->expects($this->once())->method('save')->willReturnSelf();
        $orderMock2->expects($this->once())->method('getEntityId')->willReturn(139);

        $this->orderCollectionFactoryMock->method('load')->willReturn([$orderMock1, $orderMock2]);

        $loggerMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Logger\Logger::class)
            ->setMethods(['sageLog'])
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock
            ->expects($this->exactly(2))
            ->method('sageLog')
            ->withConsecutive(
                [
                    'Cron',
                    [
                        "OrderId" => 39,
                        "Result"  => "ERROR : Transaction found: 463B3DE6-443F-585B-E75C-C727476DE98F"
                    ]
                ],
                [
                    'Cron',
                    [
                        "OrderId" => 139,
                        "Result"  => "CANCELLED : No payment received."
                    ]
                ]
            )
            ->willReturn(true);

        $orderPaymentRepository = $this
            ->getMockBuilder(\Magento\Sales\Api\OrderPaymentRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerMock = $this
            ->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $trnMock = $this->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMock();
        $trnMock
            ->expects($this->any())
            ->method('getItems')
            ->willReturnOnConsecutiveCalls(
                [
                    $this->getMockBuilder(\Magento\Sales\Api\Data\TransactionInterface::class)
                        ->disableOriginalConstructor()
                        ->getMock()
                ],
                []
            );

        $trnRepoMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment\Transaction\Repository::class)
            ->setMethods(['getList'])
            ->disableOriginalConstructor()
            ->getMock();
        $trnRepoMock->expects($this->exactly(2))->method('getList')->willReturn($trnMock);

        $criteriaBuilderMock = $this
            ->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
            ->setMethods(['addFilters', 'create'])
            ->disableOriginalConstructor()
            ->getMock();
        $criteriaBuilderMock->expects($this->exactly(2))->method('addFilters')->willReturnSelf();
        $criteriaBuilderMock->expects($this->exactly(2))->method('create')->willReturn(
            $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock()
        );

        $filterBuilderMock = $this
            ->getMockBuilder(\Magento\Framework\Api\FilterBuilder::class)
            ->setMethods(['setField', 'setConditionType', 'create'])
            ->disableOriginalConstructor()
            ->getMock();
        $filterBuilderMock->method('setField')->with('txn_id')->willReturnSelf();
        $filterBuilderMock->method('setConditionType')->with('eq')->willReturnSelf();

        $cronMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Cron::class)
            ->setMethods(['checkFraud'])
            ->setConstructorArgs(
                [
                    "suiteLogger"            => $loggerMock,
                    "orderPaymentRepository" => $orderPaymentRepository,
                    "objectManager"          => $objectManagerMock,
                    "config"                 => $this->configMock,
                    "orderCollectionFactory" => $this->orderCollectionFactoryMock,
                    "transactionRepository"  => $trnRepoMock,
                    "fraudHelper"            => $this->fraudHelper,
                    "fraudModel"             => $fraudModelMock,
                    "criteriaBuilder"        => $criteriaBuilderMock,
                    "filterBuilder"          => $filterBuilderMock
                ]
            )
            ->getMock();

        $cronMock->cancelPendingPaymentOrders();
    }

    public function testCheckFraud()
    {
        $fraudModelMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\ResourceModel\Fraud::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fraudModelMock
            ->expects($this->once())
            ->method('getShadowPaidPaymentTransactions')
            ->willReturn([["transaction_id" => 67], ["transaction_id" => 389]]);

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderPaymentRepositoryMock->expects($this->any())
            ->method('get')
            ->will($this->returnValue($paymentMock));

        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $this->fraudHelper->expects($this->exactly(2))
            ->method("processFraudInformation");

        $trnInstanceMock = $this
            ->getMockBuilder(\Magento\Sales\Api\Data\TransactionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $trnInstanceMock->expects($this->exactly(2))->method('getPaymentId')->willReturn(1234);

        $trnRepoMock = $this
            ->getMockBuilder(\Magento\Sales\Api\TransactionRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $trnRepoMock->expects($this->exactly(2))->method('get')->willReturn($trnInstanceMock);

        $this->cronModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Cron',
            [
                "config"                 => $this->configMock,
                "orderFactory"           => $this->orderCollectionFactoryMock,
                "transactionFactory"     => $this->transactionFactoryMock,
                "orderPaymentRepository" => $this->orderPaymentRepositoryMock,
                "fraudHelper"            => $this->fraudHelper,
                "fraudModel"             => $fraudModelMock,
                "transactionRepository"  => $trnRepoMock
            ]
        );

        $this->cronModel->checkFraud();
    }

    public function testCancelPendingPaymentOrders1()
    {
        $fraudModelMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\ResourceModel\Fraud::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fraudModelMock
            ->expects($this->once())
            ->method('getOrdersToCancel')
            ->willReturn([]);

        $this->cronModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Cron',
            [
                "fraudModel"             => $fraudModelMock
            ]
        );

        $this->assertInstanceOf('\Ebizmarts\SagePaySuite\Model\Cron', $this->cronModel->cancelPendingPaymentOrders());
    }

    public function testCheckFraudException()
    {
        $fraudModelMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\ResourceModel\Fraud::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fraudModelMock
            ->expects($this->once())
            ->method('getShadowPaidPaymentTransactions')
            ->willReturn([["transaction_id" => 67]]);

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderPaymentRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn(null);

        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $this->fraudHelper->expects($this->never())
            ->method("processFraudInformation");

        $trnInstanceMock = $this
            ->getMockBuilder(\Magento\Sales\Api\Data\TransactionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $trnInstanceMock->expects($this->once())->method('getPaymentId')->willReturn(1234);

        $trnRepoMock = $this
            ->getMockBuilder(\Magento\Sales\Api\TransactionRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $trnRepoMock->expects($this->once())->method('get')->willReturn($trnInstanceMock);

        $loggerMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Logger\Logger::class)
            ->setMethods(['sageLog'])
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock->expects($this->once())->method('sageLog')
            ->with(
                'Cron',
                $this->logicalAnd(
                    $this->contains("Payment not found for this transaction."),
                    $this->arrayHasKey("ERROR"),
                    $this->arrayHasKey("Trace")
                )
            );

        $cronModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Cron',
            [
                "suiteLogger"            => $loggerMock,
                "config"                 => $this->configMock,
                "orderFactory"           => $this->orderCollectionFactoryMock,
                "transactionFactory"     => $this->transactionFactoryMock,
                "orderPaymentRepository" => $this->orderPaymentRepositoryMock,
                "fraudHelper"            => $this->fraudHelper,
                "fraudModel"             => $fraudModelMock,
                "transactionRepository"  => $trnRepoMock
            ]
        );

        $cronModel->checkFraud();
    }

    public function testCheckFraudExceptionApi()
    {
        $fraudModelMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\ResourceModel\Fraud::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fraudModelMock
            ->expects($this->once())
            ->method('getShadowPaidPaymentTransactions')
            ->willReturn([["transaction_id" => 67]]);

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderPaymentRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->returnValue($paymentMock));

        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $trnInstanceMock = $this
            ->getMockBuilder(\Magento\Sales\Api\Data\TransactionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $trnInstanceMock->expects($this->once())->method('getPaymentId')->willReturn(1234);

        $trnRepoMock = $this
            ->getMockBuilder(\Magento\Sales\Api\TransactionRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $trnRepoMock->expects($this->once())->method('get')->willReturn($trnInstanceMock);

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("No transaction found.")
        );

        $this->fraudHelper->expects($this->once())
            ->method("processFraudInformation")
            ->willThrowException($apiException);

        $loggerMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Logger\Logger::class)
            ->setMethods(['sageLog'])
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock->expects($this->once())->method('sageLog')
            ->with(
                'Cron',
                $this->logicalAnd(
                    $this->contains("No transaction found."),
                    $this->arrayHasKey("ERROR"),
                    $this->arrayHasKey("Trace")
                )
            );

        $cronModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Cron',
            [
                "suiteLogger"            => $loggerMock,
                "config"                 => $this->configMock,
                "orderFactory"           => $this->orderCollectionFactoryMock,
                "transactionFactory"     => $this->transactionFactoryMock,
                "orderPaymentRepository" => $this->orderPaymentRepositoryMock,
                "fraudHelper"            => $this->fraudHelper,
                "fraudModel"             => $fraudModelMock,
                "transactionRepository"  => $trnRepoMock
            ]
        );

        $cronModel->checkFraud();
    }
}
