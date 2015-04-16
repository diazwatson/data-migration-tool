<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Migration\Mode;

class SettingsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Settings
     */
    protected $settings;

    /**
     * @var \Migration\App\Mode\StepList|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $stepList;

    /**
     * @var \Migration\Logger\Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var \Migration\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var \Migration\App\Step\Progress|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $progress;

    public function setUp()
    {
        $this->stepList = $this->getMockBuilder('\Migration\App\Mode\StepList')->disableOriginalConstructor()
            ->setMethods(['getSteps'])
            ->getMock();
        $this->logger = $this->getMockBuilder('\Migration\Logger\Logger')->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMock();
        $this->progress = $this->getMockBuilder('\Migration\App\Step\Progress')->disableOriginalConstructor()
            ->setMethods(['saveResult', 'isCompleted', 'clearLockFile', 'reset'])
            ->getMock();

        $this->settings = new Settings($this->progress, $this->logger, $this->stepList);
    }

    public function testRunStepsIntegrityFail()
    {
        $this->setExpectedException('Migration\Exception', 'Integrity Check failed');
        $step = $this->getMockBuilder('\Migration\App\Step\StepInterface')->getMock();
        $step->expects($this->any())->method('getTitle')->will($this->returnValue('Title'));
        $step->expects($this->once())->method('integrity')->will($this->returnValue(false));
        $step->expects($this->never())->method('run');
        $step->expects($this->never())->method('volumeCheck');
        $this->progress->expects($this->any())->method('saveResult')->willReturnSelf();
        $this->progress->expects($this->any())->method('isCompleted')->willReturn(false);
        $this->stepList->expects($this->once())->method('getSteps')
            ->willReturn([$step]);
        $this->assertSame($this->settings, $this->settings->run());
    }

    public function testRunStepsVolumeFail()
    {
        $this->setExpectedException('Migration\Exception', 'Volume Check failed');
        $step = $this->getMockBuilder('\Migration\App\Step\StepInterface')->getMock();
        $step->expects($this->any())->method('getTitle')->will($this->returnValue('Title'));
        $step->expects($this->once())->method('integrity')->will($this->returnValue(true));
        $step->expects($this->once())->method('run')->will($this->returnValue(true));
        $step->expects($this->once())->method('volumeCheck')->will($this->returnValue(false));
        $this->progress->expects($this->any())->method('saveResult')->willReturnSelf();
        $this->progress->expects($this->any())->method('isCompleted')->willReturn(false);
        $this->progress->expects($this->any())->method('reset')->with($step);
        $this->logger->expects($this->any())->method('info');
        $this->stepList->expects($this->once())->method('getSteps')
            ->willReturn([$step]);
        $this->assertSame($this->settings, $this->settings->run());
    }

    public function testRunStepsDataMigrationFail()
    {
        $this->setExpectedException('Migration\Exception', 'Data Migration failed');
        $step = $this->getMockBuilder('\Migration\App\Step\StepInterface')->getMock();
        $step->expects($this->any())->method('getTitle')->will($this->returnValue('Title'));
        $step->expects($this->once())->method('integrity')->will($this->returnValue(true));
        $step->expects($this->once())->method('run')->will($this->returnValue(false));
        $step->expects($this->never())->method('volumeCheck');
        $this->progress->expects($this->any())->method('saveResult')->willReturnSelf();
        $this->progress->expects($this->any())->method('isCompleted')->willReturn(false);
        $this->progress->expects($this->any())->method('reset')->with($step);
        $this->logger->expects($this->any())->method('info');
        $this->stepList->expects($this->once())->method('getSteps')
            ->willReturn([$step]);
        $this->assertSame($this->settings, $this->settings->run());
    }

    public function testRunStepsSuccess()
    {
        $step = $this->getMockBuilder('\Migration\App\Step\StepInterface')->getMock();
        $step->expects($this->any())->method('getTitle')->will($this->returnValue('Title'));
        $step->expects($this->once())->method('integrity')->will($this->returnValue(true));
        $step->expects($this->once())->method('run')->will($this->returnValue(true));
        $step->expects($this->once())->method('volumeCheck')->will($this->returnValue(true));
        $this->progress->expects($this->any())->method('saveResult')->willReturnSelf();
        $this->progress->expects($this->any())->method('isCompleted')->willReturn(false);
        $this->progress->expects($this->once())->method('clearLockFile')->willReturnSelf();
        $this->logger->expects($this->at(0))->method('info')->with(PHP_EOL . "Title: integrity check");
        $this->logger->expects($this->at(1))->method('info')->with(PHP_EOL . "Title: data migration");
        $this->logger->expects($this->at(2))->method('info')->with(PHP_EOL . "Title: volume check");
        $this->logger->expects($this->at(3))->method('info')->with(PHP_EOL . "Migration completed");
        $this->stepList->expects($this->once())->method('getSteps')
            ->willReturn([$step]);
        $this->assertTrue($this->settings->run());
    }

    public function testRunStepsWithSuccessProgress()
    {
        $step = $this->getMockBuilder('\Migration\App\Step\StepInterface')->getMock();
        $step->expects($this->any())->method('getTitle')->will($this->returnValue('Title'));
        $step->expects($this->never())->method('integrity');
        $step->expects($this->never())->method('run');
        $step->expects($this->never())->method('volumeCheck');
        $this->progress->expects($this->never())->method('saveResult');
        $this->progress->expects($this->any())->method('isCompleted')->willReturn(true);
        $this->progress->expects($this->once())->method('clearLockFile')->willReturnSelf();
        $this->logger->expects($this->at(0))->method('info')->with(PHP_EOL . "Title: integrity check");
        $this->logger->expects($this->at(1))->method('info')->with(PHP_EOL . "Title: data migration");
        $this->logger->expects($this->at(2))->method('info')->with(PHP_EOL . "Title: volume check");
        $this->logger->expects($this->at(3))->method('info')->with(PHP_EOL . "Migration completed");
        $this->stepList->expects($this->once())->method('getSteps')
            ->willReturn([$step]);
        $this->assertTrue($this->settings->run());
    }
}