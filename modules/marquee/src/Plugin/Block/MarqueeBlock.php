<?php

declare(strict_types=1);

namespace Drupal\marquee\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\dhs\Service\HelperFunctions;
use Drupal\gin_lb\HookHandler\Help;

/**
 * Provides a marquee block.
 *
 * @Block(
 *   id = "dcp_marquee",
 *   admin_label = @Translation("Marquee"),
 *   category = @Translation("Pragma"),
 * )
 */


final class MarqueeBlock extends BlockBase
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
      'images' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array
  {
    $form['images'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['image'],
      '#title' => $this->t('Upload infinite images'),
      '#default_value' => $this->configuration['images'],
      '#description' => $this->t('Upload or select unlimited images.'),
      '#cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void
  {
    $this->configuration['images'] = $form_state->getValue('images') ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array
  {
    $mediaHelper = \Drupal::service('dhs.helper_functions');
    $image_ids = explode(',', $this->configuration['images']);
    $images = [];
    foreach ($image_ids as $media_id) {
      $media = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
      $images[] = $mediaHelper->buildMediaObject($media);
    }

    return [
      '#type' => 'component',
      '#component' => 'marquee:marquee',
      '#props' => [
        'images' => $images,
      ],
    ];
  }
}
