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

/**
 * Core layout utility
 */
namespace Magento\Framework\View\Utility;

/**
 * Class Layout
 */
class Layout
{
    /**
     * @var \PHPUnit_Framework_TestCase
     */
    protected $_testCase;

    /**
     * @param \PHPUnit_Framework_TestCase $testCase
     */
    public function __construct(\PHPUnit_Framework_TestCase $testCase)
    {
        $this->_testCase = $testCase;
    }

    /**
     * Retrieve new layout update model instance with XML data from a fixture file
     *
     * @param string|array $layoutUpdatesFile
     * @return \Magento\Framework\View\Layout\ProcessorInterface
     */
    public function getLayoutUpdateFromFixture($layoutUpdatesFile)
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /** @var \Magento\Framework\View\File\Factory $fileFactory */
        $fileFactory = $objectManager->get('Magento\Framework\View\File\Factory');
        $files = [];
        foreach ((array)$layoutUpdatesFile as $filename) {
            $files[] = $fileFactory->create($filename, 'Magento_View');
        }
        $fileSource = $this->_testCase->getMockForAbstractClass('Magento\Framework\View\File\CollectorInterface');
        $fileSource->expects(
            \PHPUnit_Framework_TestCase::any()
        )->method(
            'getFiles'
        )->will(
            \PHPUnit_Framework_TestCase::returnValue($files)
        );
        $pageLayoutFileSource = $this->_testCase->getMockForAbstractClass(
            'Magento\Framework\View\File\CollectorInterface'
        );
        $pageLayoutFileSource->expects(\PHPUnit_Framework_TestCase::any())
            ->method('getFiles')
            ->willReturn([]);
        $cache = $this->_testCase->getMockForAbstractClass('Magento\Framework\Cache\FrontendInterface');
        return $objectManager->create(
            'Magento\Framework\View\Layout\ProcessorInterface',
            ['fileSource' => $fileSource, 'pageLayoutFileSource' => $pageLayoutFileSource, 'cache' => $cache]
        );
    }

    /**
     * Retrieve new layout model instance with layout updates from a fixture file
     *
     * @param string|array $layoutUpdatesFile
     * @param array $args
     * @return \Magento\Framework\View\Layout|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getLayoutFromFixture($layoutUpdatesFile, array $args = [])
    {
        $layout = $this->_testCase->getMock('Magento\Framework\View\Layout', ['getUpdate'], $args);
        $layoutUpdate = $this->getLayoutUpdateFromFixture($layoutUpdatesFile);
        $layoutUpdate->asSimplexml();
        $layout->expects(
            \PHPUnit_Framework_TestCase::any()
        )->method(
            'getUpdate'
        )->will(
            \PHPUnit_Framework_TestCase::returnValue($layoutUpdate)
        );
        return $layout;
    }

    /**
     * Retrieve object that will be used for layout instantiation
     *
     * @return array
     */
    public function getLayoutDependencies()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        return [
            'processorFactory' => $objectManager->get('Magento\Framework\View\Layout\ProcessorFactory'),
            'eventManager' => $objectManager->get('Magento\Framework\Event\ManagerInterface'),
            'structure' => $objectManager->create('Magento\Framework\View\Layout\Data\Structure', []),
            'scheduledStructure' => $objectManager->create('Magento\Framework\View\Layout\ScheduledStructure', []),
            'messageManager' => $objectManager->get('Magento\Framework\Message\ManagerInterface'),
            'themeResolver' => $objectManager->get('Magento\Framework\View\Design\Theme\ResolverInterface'),
            'pageConfigStructure' => $objectManager->create('Magento\Framework\View\Page\Config\Structure'),
            'reader' => $objectManager->get('commonRenderPool'),
            'generatorPool' => $objectManager->get('Magento\Framework\View\Layout\GeneratorPool'),
        ];
    }
}
