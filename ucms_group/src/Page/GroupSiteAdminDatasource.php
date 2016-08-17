<?php

namespace MakinaCorpus\Ucms\Group\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SearchForm;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Group\GroupSite;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

class GroupSiteAdminDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    private $database;
    private $groupManager;
    private $siteManager;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param GroupManager $groupManager
     */
    public function __construct(\DatabaseConnection $database, GroupManager $groupManager, SiteManager $siteManager)
    {
        $this->database = $database;
        $this->groupManager = $groupManager;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        $states = SiteState::getList(SiteState::ARCHIVE);

        foreach ($states as $key => $label) {
          $states[$key] = $this->t($label);
        }

        return [
            (new LinksFilterDisplay('state', $this->t("State")))->setChoicesMap($states),
            // @todo missing site type registry or variable somewhere
            (new LinksFilterDisplay('theme', $this->t("Theme")))->setChoicesMap($this->siteManager->getAllowedThemesOptionList()),
            (new LinksFilterDisplay('template', $this->t("Template")))->setChoicesMap($this->siteManager->getTemplateList()),
            (new LinksFilterDisplay('other', $this->t("Other")))->setChoicesMap(['t' => "template"]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            'gs.site_id'    => $this->t("identifier"),
            's.title'       => $this->t("title"),
            's.title_admin' => $this->t("administrative title"),
            's.state'       => $this->t("state"),
            's.type'        => $this->t("type"),
            's.http_host'   => $this->t("domain name"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['s.title', SortManager::ASC];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        $q = $this
            ->database
            ->select('ucms_group_site', 'gs')
            ->fields('gs', ['site_id', 'group_id'])
        ;

        $q->join('ucms_site', 's', "s.id = gs.site_id");

        if (!empty($query['group'])) {
            $q->condition('gs.group_id', $query['group']);
        }
        if (!empty($query['site'])) {
            $q->condition('gs.site_id', $query['site']);
        }

        // Filters
        if (isset($query['state'])) {
            $q->condition('s.state', $query['state']);
        }
        if (isset($query['theme'])) {
            $q->condition('s.theme', $query['theme']);
        }
        if (isset($query['template'])) {
            $q->condition('s.template_id', $query['template']);
        }

        if ($pageState->hasSortField()) {
            $q->orderBy($pageState->getSortField(), SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');
        }
        $q->orderBy('s.title', SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');

        $sParam = SearchForm::DEFAULT_PARAM_NAME;
        if (!empty($query[$sParam])) {
            $q->condition(
                (new \DatabaseCondition('OR'))
                    ->condition('s.title', '%' . db_like($query[$sParam]) . '%', 'LIKE')
                    ->condition('s.title_admin', '%' . db_like($query[$sParam]) . '%', 'LIKE')
                    ->condition('s.http_host', '%' . db_like($query[$sParam]) . '%', 'LIKE')
            );
        }

        $r = $q
            ->addTag('ucms_group_access')
            ->addTag('ucms_site_access')
            ->groupBy('gs.site_id')
            ->extend('PagerDefault')
            ->limit($pageState->getLimit())
            ->execute()
        ;

        $r->setFetchMode(\PDO::FETCH_CLASS, GroupSite::class);

        $ret = $r->fetchAll();

        // Preload all sites, we will need it in display
        $siteIdList = array_map(function (GroupSite $item) { return $item->getSiteId(); }, $ret);
        $sites = $this->siteManager->getStorage()->loadAll($siteIdList, false);
        /** @var \MakinaCorpus\Ucms\Group\GroupSite $record */
        foreach ($ret as $record) {
            $siteId = $record->getSiteId();
            if (isset($sites[$siteId])) {
                $record->setSite($sites[$siteId]);
            }
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return true;
    }
}
