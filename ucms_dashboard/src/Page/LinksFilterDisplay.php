<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

/**
 * Default implementation that will convert a single hashmap to a set of links
 */
class LinksFilterDisplay
{
    /**
     * @var string[]
     */
    private $choicesMap = [];

    /**
     * @var string
     */
    private $queryParameter;

    /**
     * @var string
     */
    private $title;

    /**
     * Default constructor
     *
     * @param string $queryParameter
     *   Query parameter name
     * @param string $title
     *   $title
     */
    public function __construct($queryParameter, $title = null)
    {
        $this->queryParameter = $queryParameter;
        $this->title = $title;
    }

    /**
     * Set choices map
     *
     * Choice map is a key-value array in which keys are indexed values and
     * values are human readable names that will supplant the indexed values
     * for end-user display, this has no effect on the query.
     *
     * @param string[] $choicesMap
     *   Keys are filter value, values are human readable labels
     *
     * @return LinksFilterDisplay
     */
    public function setChoicesMap($choicesMap)
    {
        $this->choicesMap = $choicesMap;

        return $this;
    }

    /**
     * {inheritdoc}
     */
    public function getTitle()
    {
        if (!$this->title) {
            return $this->queryParameter;
        }

        return $this->title;
    }

    /**
     * Get selected values from query
     *
     * @param string[] $query
     *
     * @return string[]
     */
    protected function getSelectedValues($query)
    {
        $values = [];

        if (isset($query[$this->queryParameter])) {

            $values = $query[$this->queryParameter];

            if (!is_array($values)) {
                if (false !== strpos($values, ',')) {
                    $values = explode(',', $values);
                } else {
                    $values = [$values];
                }
            }
        }

        return array_map('trim', $values);
    }

    /**
     * Get query parameters for a singe link
     *
     * @param string[] $query
     *   Contextual query that represents the current page state
     * @param string $value
     *   Value for the given link
     * @param boolean $remove
     *   Instead of adding the value, it must removed from the query
     *
     * @return string[]
     *   New query with value added or removed
     */
    protected function getParametersForLink($query, $value, $remove = false)
    {
        if (isset($query[$this->queryParameter])) {
            $actual = explode(',', $query[$this->queryParameter]);
        } else {
            $actual = [];
        }

        if ($remove) {
            if (false !== ($pos = array_search($value, $actual))) {
                unset($actual[$pos]);
            }
        } else {
            if (false === array_search($value, $actual)) {
                $actual[] = $value;
            }
        }

        if (empty($actual)) {
            unset($query[$this->queryParameter]);
            return $query;
        } else {
            sort($actual);
            return [$this->queryParameter => implode(',', $actual)] + $query;
        }
    }

    /**
     * {inheritdoc}
     */
    public function build($query)
    {
        $links = [];
        $selectedValues = $this->getSelectedValues($query);

        foreach ($this->choicesMap as $value => $label) {

            $link = [
                'href'  => current_path(),
                'title' => filter_xss($label),
                'html'  => true,
            ];

            if (in_array($value, $selectedValues)) {
                $link['attributes']['class'][] = 'active';
                $link['query'] = $this->getParametersForLink($query, $value, true);
            } else {
                $link['query'] = $this->getParametersForLink($query, $value);
            }

            $links[$value] = $link;
        }

        if (empty($links)) {
            // This is no links to display, do not leave an empty title
            $links['_empty'] = [
                'title'       => t("No values"),
                'href'        => current_path(),
                'query'       => $query,
                'attributes'  => ['class' => ['disabled']],
            ];
        }

        // Forces the l() function to skip the 'active' class by adding empty
        // attributes array and settings a stupid language onto the link (this
        // is Drupal 7 specific and exploit a Drupal weird behavior)
        foreach ($links as &$link) {
            if (empty($link['attributes'])) {
                $link['attributes'] = [];
            }
            $link['language'] = (object)['language' => LANGUAGE_NONE];
        }

        return [
            '#theme'    => 'links__ucms__dashboard__filter',
            '#heading'  => $this->getTitle(),
            '#links'    => $links,
        ];
    }
}

