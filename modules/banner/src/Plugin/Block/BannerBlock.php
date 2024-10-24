<?php

declare(strict_types=1);

// helps tell where we are
namespace Drupal\banner\Plugin\Block;

// import classes
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\Media;

use Drupal\dhs\Service\HelperFunctions;

/**
 * Provides a banner block.
 *
 * @Block(
 *   id = "banner_banner",
 *   admin_label = @Translation("banner"),
 *   category = @Translation("pragma"),
 * )
 */


final class BannerBlock extends BlockBase
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
      'banner_image' => [],
      'banner_heading' => '',
      'banner_body' => [
        'value' => '',
        'format' => 'basic_html',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array
  {
    // add media_library field
    $form['banner_image'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['image'],
      '#default_value' => $this->configuration['banner_image'],
      '#title' => $this->t('Banner Image'),
      '#description' => $this->t('Select an image to display as the banner.'),
    ];

    // add a heading filed
    $form['banner_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Heading'),
      '#default_value' => $this->configuration['banner_heading'],
    ];

    // add a body field
    $form['banner_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => $this->configuration['banner_body']['value'],
      '#format' => $this->configuration['banner_body']['format'] ?? 'basic_html',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void
  {
    $this->configuration['banner_image'] = $form_state->getValue('banner_image');
    $this->configuration['banner_heading'] = $form_state->getValue('banner_heading');
    $this->configuration['banner_body'] = $form_state->getValue('banner_body');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array
  {
    if ($this->configuration['banner_image']) {
      $alt_logo_mid = $this->configuration['banner_image'];
      $media_entity = Media::load($alt_logo_mid);
      $mediaHelper = \Drupal::service('dhs.helper_functions');
      $mediaObject = $mediaHelper->buildMediaObject($media_entity);
    }

    return [
      '#theme' => 'banner',
      '#banner_image' => $mediaObject ?? [],
      '#banner_heading' => $this->configuration['banner_heading'],
      '#banner_body' => $this->configuration['banner_body'],
    ];
  }
}
