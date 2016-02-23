<?php

namespace MakinaCorpus\Ucms\User\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Portlet\AbstractPortlet;

class AccountPortlet extends AbstractPortlet
{
    use StringTranslationTrait;

    /**
     * @var AccountInterface
     */
    private $account;

    /**
     * Return the title of this portlet.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->t("My account");
    }

    /**
     * Return the path for the main page of this portlet.
     *
     * @return null|string
     */
    public function getPath()
    {
        return null;
    }

    /**
     * @return Action[]
     */
    public function getActions()
    {
        return [
            new Action('Edit my information', 'admin/dashboard/user/edit_mine', 'dialog', 'edit'),
        ];
    }

    /**
     * Return the render array for this portlet.
     * @return array
     */
    public function getContent()
    {
        // Prevent any modification of the global object
        $items[] = [
            $this->t('Username'),
            check_plain(format_username($this->account)),
        ];

        $items[] = [
            $this->t('E-mail'),
            $this->account->mail,
        ];

        $items[] = [
            $this->formatPlural(count($this->account->roles), 'Role', 'Roles'),
            [
                '#theme' => 'item_list',
                '#items' => $this->account->roles,
                '#attributes' => ['class' => 'list-unstyled'],
            ],
        ];

        // TODO
        $sites = ['@todo'];
        $items[] = [
            $this->formatPlural(count($sites), 'Site', 'Sites'),
            [
                '#theme' => 'item_list',
                '#items' => $sites,
                '#attributes' => ['class' => 'list-unstyled'],
            ],
        ];

        return [
            '#theme' => 'description_list',
            '#items' => $items,
        ];
    }

    /**
     * Return true if portlet if visible for user.
     *
     * @param AccountInterface $account
     *
     * @return boolean
     */
    public function userIsAllowed(AccountInterface $account)
    {
        // @TODO should be in a constructor
        $this->account = $account;

        return true;
    }
}
