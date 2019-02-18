<?php

namespace Siteguarding\Security\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;

class Index extends Action
{
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }
}