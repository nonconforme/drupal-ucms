<?php

namespace MakinaCorpus\Ucms\Contrib\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class NodeFlag extends FormBase
{
    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_contrib_node_flag_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $node = null)
    {
        $form_state->setTemporaryValue('node', $node);

        return confirm_form([], $this->t("Flag %title has innapropriate ?", ['%title' => $node->title]), 'node/' . $node->nid);
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $node = $form_state->getTemporaryValue('node');
        $node->is_flagged = 1;
        node_save($node);

        drupal_set_message($this->t("%title has been flagged has innapropriate.", ['%title' => $node->title]), 'warning');
        $form_state->setRedirect('node/' . $node->nid);
    }
}
