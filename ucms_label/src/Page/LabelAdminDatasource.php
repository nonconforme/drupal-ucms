<?php

namespace MakinaCorpus\Ucms\Label\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Drupal\Dashboard\Page\Filter;
use MakinaCorpus\Drupal\Dashboard\Page\PageState;
use MakinaCorpus\Drupal\Dashboard\Page\QueryExtender\DrupalPager;
use MakinaCorpus\Drupal\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Label\LabelManager;

class LabelAdminDatasource extends AbstractDatasource
{
    use StringTranslationTrait;


    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var LabelManager
     */
    private $manager;


    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param LabelManager $manager
     */
    public function __construct(\DatabaseConnection $db, LabelManager $manager)
    {
        $this->db = $db;
        $this->manager = $manager;
    }


    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        $categories = [];
        foreach ($this->manager->loadRootLabels() as $label) {
            $categories[$label->tid] = $label->name;
        }

        $statuses = [
            0 => $this->t("Editable"),
            1 => $this->t("Non editable"),
        ];

        return [
            (new Filter('category', $this->t("Category")))->setChoicesMap($categories),
            (new Filter('status', $this->t("Status")))->setChoicesMap($statuses),
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            't.tid'       => $this->t("identifier"),
            't.name'      => $this->t("name"),
            't.is_locked' => $this->t("status"),
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['t.name', SortManager::ASC];
    }


    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        $q = $this->db->select('taxonomy_term_data', 't');
        // fields() must be call prior to orderBy() because orderBy() will
        // also add the fields to the field list, thus change the order they
        // are SELECT'ed and make fetchCol() return the sorted field instead
        // of the one added right here.
        $q->fields('t', ['tid']);
        $q->join('taxonomy_term_hierarchy', 'h', "h.tid = t.tid");

        if (isset($query['category'])) {
            $q->condition('h.parent', $query['category']);
        }
        if (isset($query['status'])) {
            $q->condition('t.is_locked', $query['status']);
        }

        if ($pageState->hasSortField()) {
            $q->orderBy($pageState->getSortField(), SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');
        }

        $ids = $q
            ->condition('t.vid', $this->manager->getVocabularyId())
            ->extend(DrupalPager::class)
            ->setPageState($pageState)
            ->execute()
            ->fetchCol()
        ;

        return $this->manager->loadLabels($ids);
    }


    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return false;
    }
 }

