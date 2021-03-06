<?php

namespace MakinaCorpus\Ucms\Group\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Drupal\Dashboard\Page\PageState;
use MakinaCorpus\Drupal\Dashboard\Page\QueryExtender\DrupalPager;
use MakinaCorpus\Drupal\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Group\GroupManager;

class GroupAdminDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    private $database;
    private $groupManager;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param GroupManager $groupManager
     */
    public function __construct(\DatabaseConnection $database, GroupManager $groupManager)
    {
        $this->database = $database;
        $this->groupManager = $groupManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            'g.id'          => $this->t("identifier"),
            'g.title'       => $this->t("title"),
            'g.ts_changed'  => $this->t("lastest update date"),
            'g.ts_created'  => $this->t("creation date"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['g.ts_changed', SortManager::DESC];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        $q = $this
            ->database
            ->select('ucms_group', 'g')
            ->fields('g', ['id'])
            ->addTag('ucms_group_access')
        ;

        if (!empty($query['uid'])) {
            $q->join('ucms_group_user', 'gu', "gu.group_id = g.id");
            $q->condition('gu.user_id', $query['uid']);
        }
        if (!empty($query['site'])) {
            $q->join('ucms_site', 'gs', "gs.group_id = g.id");
            $q->condition('gs.id', $query['site']);
        }

        if ($pageState->hasSortField()) {
            $q->orderBy($pageState->getSortField(), SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');
        }
        $q->orderBy('g.id', SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');

        $sParam = $pageState->getSearchParameter();
        if (!empty($query[$sParam])) {
            $q->condition('g.title', '%' . db_like($query[$sParam]) . '%', 'LIKE');
        }

        $idList = $q
            ->groupBy('g.id')
            ->extend(DrupalPager::class)
            ->setPageState($pageState)
            ->execute()
            ->fetchCol()
        ;

        return $this->groupManager->getStorage()->loadAll($idList);
    }

    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return true;
    }
 }
