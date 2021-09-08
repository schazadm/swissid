<?php

namespace OSR\Training\Controller\Admin;

use OSR\Training\Filter\ArticleFilters;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;

class TrainingArticleController extends FrameworkBundleAdminController
{
    public function indexAction(Request $request, ArticleFilters $articleFilters)
    {
        $presenter = $this->get('prestashop.core.grid.presenter.grid_presenter');
        $articleGrid = $this->get('training.grid.factory')->getGrid($articleFilters);
        return $this->render('@Modules/training/views/templates/admin/article/index.html.twig',
            [
                'articleGrid' => $presenter->present($articleGrid)
            ]
        );
    }
}