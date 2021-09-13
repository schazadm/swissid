<?php


namespace OSR\Swissid\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;

class ConfigurationController extends FrameworkBundleAdminController
{
    public function indexAction()
    {
        return $this->render('@Modules/swissid/views/templates/admin/configuration/index.html.twig');
    }
}