<?php

declare(strict_types=1);

// helps tell where we are
namespace Drupal\button\Plugin\Block;

// import classes
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;

/**
 * Provides a button block.
 *
 * @Block(
 *   id = "dcp_button",
 *   admin_label = @Translation("Button"),
 *   category = @Translation("Pragma"),
 * )
 */


final class ButtonBlock extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge()
  {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array
  {
    return [
      'link' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array
  {
    // create an entity reference field for menus
    $form['link'] = [
      '#title' => $this->t('Link'),
      '#type' => 'url',
      '#default_value' => $this->configuration['link'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void
  {
    $this->configuration['link'] = $form_state->getValue('link');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array
  {
    return [
      '#type' => 'component',
      '#component' => 'button:button',
      '#props' => [
        'link' => $this->configuration['link'],
      ],
    ];
  }
}
