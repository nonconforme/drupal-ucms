<?php

namespace MakinaCorpus\Ucms\Site\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Portlet\AbstractPortlet;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Page\SiteAdminDatasource;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

class SitesPortlet extends AbstractPortlet
{
    use StringTranslationTrait;

    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param SiteManager $siteManager
     */
    public function __construct(\DatabaseConnection $db, SiteManager $siteManager)
    {
        $this->db = $db;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle()
    {
        return $this->t("Sites");
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return 'admin/dashboard/site';
    }

    /**
     * {@inheritDoc}
     */
    public function getActions()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getContent()
    {
        $datasource = new SiteAdminDatasource($this->db, $this->siteManager);
        $pageState  = new PageState();
        $states     = SiteState::getList();
        $rows       = [];

        $pageState->setRange(6);
        $pageState->setSortField('s.ts_changed');
        $pageState->setSortOrder(PageState::SORT_DESC);

        $items = $datasource->getItems([], $pageState);

        foreach ($items as $item) {
            /* @var $item Site */
            $options = ['attributes' => ['class' => ['btn-sm']]];

            if ($item instanceof Site) {
                if ($item->state == SiteState::ON) {
                    // $this->t("Go to site")
                    $options += ['absolute' => true];
                    $action = new Action("", $item->http_host, $options, 'share-alt');
                } else {
                    // $this->t("Go to request")
                    $action = new Action("", 'admin/dashboard/site/'.$item->id, $options, 'edit');
                }
                $rows[] = [
                    check_plain($item->title_admin),
                    $item->ts_created->format('d/m H:i'),
                    check_plain($states[$item->state]),
                    ['#theme' => 'ucms_dashboard_actions', '#actions' => [$action]],
                ];
            }
        }

        return [
            '#theme' => 'table',
            '#header' => [
                $this->t('Title'),
                $this->t('Request date'),
                $this->t('Status'),
                $this->t('Link'),
            ],
            '#rows' => $rows,
            '#empty' => $this->t("No site created yet."),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function userIsAllowed(AccountInterface $account)
    {
        return $account->hasPermission(Access::PERM_SITE_MANAGE_ALL);
    }
}
