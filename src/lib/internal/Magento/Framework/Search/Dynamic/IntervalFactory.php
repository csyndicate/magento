<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\Framework\Search\Dynamic;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\ObjectManagerInterface;

class IntervalFactory
{
    /**
     * @var string
     */
    private $interval;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param ScopeConfigInterface $scopeConfig
     * @param string $configPath
     * @param string[] $intervals
     * @param string $scope
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        $configPath,
        $intervals,
        $scope = ScopeInterface::SCOPE_DEFAULT
    ) {
        $this->objectManager = $objectManager;
        $configValue = $scopeConfig->getValue($configPath, $scope);
        if (isset($intervals[$configValue])) {
            $this->interval = $intervals[$configValue];
        } else {
            throw new \LogicException("Interval not found by config {$configValue}");
        }
    }

    /**
     * Create interval
     *
     * @return IntervalInterface
     */
    public function create()
    {
        $interval = $this->objectManager->create($this->interval);
        if (!$interval instanceof IntervalInterface) {
            throw new \LogicException(
                'Interval not instance of interface \Magento\Framework\Search\Dynamic\IntervalInterface'
            );
        }
        return $interval;
    }
}
