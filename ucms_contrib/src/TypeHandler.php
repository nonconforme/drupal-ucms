<?php

namespace MakinaCorpus\Ucms\Contrib;

class TypeHandler
{
    /**
     * Get service name for page type
     *
     * @todo unstatic this
     *
     * @param string $tab
     *   'content' or 'media' or anything that the type handler knows about
     * @param string $page
     *   'mine', 'global', etc...
     *
     * @return string
     */
    static public function getServiceName($tab, $page)
    {
        return 'ucms_contrib.page_type.' . $tab . '.' . $page;
    }

    private $tabs = [];
    private $adminPages = [];

    /**
     * Default constructor
     *
     * @param string[] $tabs
     *   Keys are path component, values are names
     * @param string[] $adminPages
     *   Keys are path component, values are names
     */
    public function __construct(array $tabs = [], array $adminPages = [])
    {
        $this->tabs = $tabs;
        $this->adminPages = $adminPages;
    }

    /**
     * Cleans variable value
     *
     * @param $name
     * @return mixed
     */
    protected function filterVariable($name)
    {
        return array_filter(variable_get($name, []));
    }

    /**
     * Get tab list.
     *
     * @return array
     */
    public function getTabs()
    {
        return $this->tabs;
    }

    /**
     * Get admin pages definitions
     *
     * @todo
     *   - how to handler permission for those pages?
     *   - tie permissions with Drupal menu system
     *   - better an a variable, what could it be?
     *   - tabs content types could be configured too?
     *
     * @param string[]
     *   Keys are path component, values are names
     */
    public function getAdminPages()
    {
        return $this->adminPages;
    }

    /**
     * Given a tab name, get its corresponding types.
     *
     * @param $tab
     *
     * @return \string[]
     */
    public function getTabTypes($tab)
    {
        switch ($tab) {
            case 'content':
                return $this->getContentTypes();
            case 'media':
                return $this->getMediaTypes();
            default:
                throw new \Exception("Tab not implemented");
        }
    }


    /**
     * Get all media types.
     *
     * @return string[]
     */
    public function getMediaTypes()
    {
        return $this->filterVariable('ucms_contrib_tab_media_type');
    }

    /**
     * Get editorial content types.
     *
     * @return string[]
     */
    public function getEditorialContentTypes()
    {
        return $this->filterVariable('ucms_contrib_editorial_types');
    }

    /**
     * Get component types.
     *
     * @return string[]
     */
    public function getComponentTypes()
    {
        return $this->filterVariable('ucms_contrib_component_types');
    }

    /**
     * Get component types.
     *
     * @return string[]
     */
    public function getLockedTypes()
    {
        return $this->filterVariable('ucms_contrib_locked_types');
    }

    /**
     * Get all other types than components.
     *
     * @return string[]
     */
    public function getUnlockedTypes()
    {
        return array_diff($this->getAllTypes(), $this->filterVariable('ucms_contrib_locked_types'));
    }

    /**
     * Get all  types.
     *
     * @return string[]
     */
    public function getAllTypes()
    {
        return array_merge($this->getContentTypes(), $this->getMediaTypes());
    }

    /**
     * Get all editorial (media + editorial content) types.
     *
     * @return string[]
     */
    public function getEditorialTypes()
    {
        return array_merge($this->getEditorialContentTypes(), $this->getMediaTypes());
    }

    /**
     * Get all content types.
     *
     * @return string[]
     */
    public function getContentTypes()
    {
        return array_merge($this->getComponentTypes(), $this->getEditorialContentTypes());
    }

    /**
     * Set all media types.
     *
     * @param array $types
     */
    public function setMediaTypes(array $types)
    {
        variable_set('ucms_contrib_tab_media_type', $types);
    }

    /**
     * Set editorial content types.
     *
     * @param array $types
     */
    public function setEditorialContentTypes(array $types)
    {
        variable_set('ucms_contrib_editorial_types', $types);
    }

    /**
     * Set component types.
     *
     * @param array $types
     */
    public function setComponentTypes(array $types)
    {
        variable_set('ucms_contrib_component_types', $types);
    }

    /**
     * Set component types.
     *
     * @param array $types
     */
    public function setLockedTypes(array $types)
    {
        variable_set('ucms_contrib_locked_types', $types);
    }

    /**
     * Given an array of type, return the human-readable types keyed by type.
     *
     * @param array $types
     *
     * @return mixed
     */
    public function getTypesAsHumanReadableList(array $types)
    {
        return array_intersect_key(node_type_get_names(), drupal_map_assoc($types));
    }
}
