<?php

namespace MakinaCorpus\Ucms\Site;

/**
 * Site finder service
 */
class SiteFinder
{
    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var Site
     */
    private $context;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * Set current site context
     *
     * @param Site $site
     */
    public function setContext(Site $site)
    {
        $this->context = $site;
    }

    /**
     * Get current context
     *
     * @return Site
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Remove current context
     */
    public function dropContext()
    {
        $this->context = null;
    }

    /**
     * Fix site instance
     *
     * @param Site $site
     */
    public function prepareInstance(Site $site)
    {
        $site->state = (int)$site->state;
        $site->ts_created = \DateTime::createFromFormat('Y-m-d H:i:s', $site->ts_created);
        $site->ts_changed = \DateTime::createFromFormat('Y-m-d H:i:s', $site->ts_changed);
    }

    /**
     * Find by hostname
     *
     * @param string $hostname
     * @param boolean $setAsContext
     *
     * @return Site
     *   Site instance, or null if not found
     */
    public function findByHostname($hostname, $setAsContext = false)
    {
        $site = $this
            ->db
            ->query(
                "SELECT * FROM {ucms_site} WHERE http_host = :host LIMIT 1 OFFSET 0",
                [':host' => $hostname]
            )
            ->fetchObject('MakinaCorpus\\Ucms\\Site\\Site')
        ;

        if ($site) {
            $this->prepareInstance($site);
        }

        if ($setAsContext) {
            if ($site) {
                $this->setContext($site);
            } else {
                $this->dropContext();
            }
        }

        return $site;
    }

    /**
     * Load site by identifier
     *
     * @param int $id
     *
     * @return Site
     *
     * @throws \InvalidArgumentException
     */
    public function findOne($id)
    {
        $site = $this
            ->db
            ->query(
                "SELECT * FROM {ucms_site} WHERE id = :id LIMIT 1 OFFSET 0",
                [':id' => $id]
            )
            ->fetchObject('MakinaCorpus\\Ucms\\Site\\Site')
        ;

        if ($site) {
            $this->prepareInstance($site);
        }

        if (!$site) {
            throw new \InvalidArgumentException("Site does not exists");
        }

        return $site;
    }

    /**
     * Load all sites from the given identifiers
     *
     * @param array $idList
     *
     * @return Site[]
     */
    public function loadAll($idList = [])
    {
        $ret = [];

        if (empty($idList)) {
            return $ret;
        }

        $sites = $this
            ->db
            ->select('ucms_site', 's')
            ->fields('s')
            ->condition('s.id', $idList)
            ->execute()
            ->fetchAll(\PDO::FETCH_CLASS, 'MakinaCorpus\\Ucms\\Site\\Site')
        ;

        // Ensure order is the same
        // FIXME: find a better way
        $sort = [];
        foreach ($sites as $site) {
            $this->prepareInstance($site);
            $sort[$site->id] = $site;
        }
        foreach ($idList as $id) {
            if (isset($sort[$id])) {
                $ret[$id] = $sort[$id];
            }
        }

        return $ret;
    }

    /**
     * Save given site
     *
     * If the given site has no identifier, its identifier will be set
     *
     * @param Site $site
     * @param array $fields
     *   If set, update only the given fields
     */
    public function save(Site $site, array $fields = null)
    {
        $eligible = [
            'title_admin',
            'title',
            'state',
            'theme',
            'http_host',
            'http_redirects',
            'replacement_of',
            'uid',
            'type',
            'ts_created',
            'ts_changed',
        ];

        if (null === $fields) {
            $fields = $eligible;
        } else {
            $fields = array_intersect($eligible, $fields);
        }

        $values = [];
        foreach ($fields as $field) {
            switch ($field) {

                case 'ts_changed':
                case 'ts_created':
                    if (!$site->{$field} instanceof \DateTime) {
                        $site->{$field} = new \DateTime();
                    }
                    $values[$field] = $site->{$field}->format('Y-m-d H:i:s');
                    break;

                default:
                    $values[$field] = $site->{$field};
                    break;
            }
        }

        if ($site->id) {
            $this
                ->db
                ->merge('ucms_site')
                ->key(['id' => $site->id])
                ->fields($values)
                ->execute()
            ;
        } else {

            $id = $this
                ->db
                ->insert('ucms_site')
                ->fields($values)
                ->execute()
            ;

            $site->id = $id;
        }
    }
}
