<?php

namespace MakinaCorpus\Ucms\Group\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\EventDispatcher\AdminTableEvent;
use MakinaCorpus\Ucms\Group\GroupManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Admin pages alteration
 */
class AdminEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    private $groupManager;

    public function __construct(GroupManager $groupManager)
    {
        $this->groupManager = $groupManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'admin:table:ucms_site_details' => [
                ['onSiteAdminDetails', 0]
            ],
        ];
    }

    public function onSiteAdminDetails(AdminTableEvent $event)
    {
        $table  = $event->getTable();
        $site   = $table->getAttribute('site');

        if (!$site) {
            return;
        }

        $groups = $this->groupManager->getAccess()->getSiteGroups($site);
        if (!$groups) {
            return;
        }

        $table->addHeader($this->t("Groups"));
        foreach ($this->groupManager->loadGroupsFrom($groups) as $group) {
            $table->addRow($this->t("Title"), l($group->getTitle(), 'admin/dashboard/group/' . $group->getId()));
        }
    }
}