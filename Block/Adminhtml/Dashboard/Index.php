<?php

namespace Siteguarding\Security\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Widget\Container;

class Index extends Container
{
    public function __construct(\Magento\Backend\Block\Widget\Context $context, \Magento\Framework\Filesystem\DirectoryList $dir, \Magento\Store\Model\StoreManagerInterface $storeManager, array $data = [])
    {
        parent::__construct($context, $data);
        
        if(!file_exists($dir->getRoot() . DIRECTORY_SEPARATOR . 'siteguarding_tools.php')){
            copy(str_replace('Block' . DIRECTORY_SEPARATOR . 'Adminhtml' . DIRECTORY_SEPARATOR . 'Dashboard', '', __DIR__ ) . 'siteguarding_tools.php', $dir->getRoot() . DIRECTORY_SEPARATOR . 'siteguarding_tools.php');
        }
        
        $this->root_path = $dir->getRoot();
        $this->webanalyze_path = $dir->getRoot() . DIRECTORY_SEPARATOR . 'webanalyze';
        $this->storeManager = $storeManager;
    }
    
    public function check_sg_tools() 
    {
        $tools_file = $this->root_path . DIRECTORY_SEPARATOR . 'siteguarding_tools.php';

        if (is_file($tools_file)) {
            chmod($tools_file, 0644);
            return true;
        } else {
            return false;
        }
    }
    
    public function check_sg_conf() 
    {
        $conf_file = $this->webanalyze_path . DIRECTORY_SEPARATOR . 'website-security-conf.php';

        if (file_exists($conf_file)) {
            chmod($conf_file, 0644);
            return true;
        } else {
            if (!file_exists($this->webanalyze_path)) {
                mkdir($this->webanalyze_path);
            }
            return false;
        }
    }
    
    public function get_autologin_id()
    {
        include_once($this->webanalyze_path . DIRECTORY_SEPARATOR . 'website-security-conf.php');
    }
    
    public function get_base_url()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
    }
}
