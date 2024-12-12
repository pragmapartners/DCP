<?php

declare(strict_types=1);

namespace Drupal\dcp_image\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Uuid\UuidInterface;

/**
 * Provides an image block.
 */
#[Block(
  id: 'dcp_image',
  admin_label: new TranslatableMarkup('Image'),
  category: new TranslatableMarkup('Pragma'),
)]
final class ImageBlock extends BlockBase implements ContainerFactoryPluginInterface
{

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected UuidInterface $uuidService;

  /**
   * Constructs a new ImageBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $connection,
    UuidInterface $uuid_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
    $this->uuidService = $uuid_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array
  {
    return [
      'unique_id' => '',
      'media' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array
  {
    $form = parent::blockForm($form, $form_state);

    if (empty($this->configuration['unique_id'])) {
      $this->configuration['unique_id'] = $this->uuidService->generate();
    }
    $block_id = $this->configuration['unique_id'];

    // Load existing media data from the database.
    $query = $this->connection->select('dcp_image_data', 'd')
      ->fields('d', ['media', 'fullwidth'])
      ->condition('block_id', $block_id)
      ->execute()
      ->fetchAssoc();

    $media = $query['media'] ?? '';

    $form['media'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['image'],
      '#title' => $this->t('Media'),
      '#default_value' => $media,
      '#required' => TRUE,
    ];
    $form['fullwidth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fullwidth'),
      '#default_value' => $query['fullwidth'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void
  {
    if (empty($this->configuration['unique_id'])) {
      $this->configuration['unique_id'] = $this->uuidService->generate();
    }
    $block_id = $this->configuration['unique_id'];
    $media = $form_state->getValue('media');

    // Check if the block already has an entry in the database.
    $query = $this->connection->select('dcp_image_data', 'd')
      ->fields('d', ['id'])
      ->condition('block_id', $block_id)
      ->execute()
      ->fetchAssoc();

    if ($query) {
      // Update existing entry.
      $this->connection->update('dcp_image_data')
        ->fields(['media' => $media, 'fullwidth' => $form_state->getValue('fullwidth')])
        ->condition('block_id', $block_id)
        ->execute();
    } else {
      // Insert new entry.
      $this->connection->insert('dcp_image_data')
        ->fields([
          'block_id' => $block_id,
          'media' => $media,
          'fullwidth' => $form_state->getValue('fullwidth'),
        ])
        ->execute();
    }

    $this->setConfigurationValue('media', $media);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array
  {
    $block_id = $this->configuration['unique_id'] ?? NULL;

    // Load existing media data from the database.
    $query = $this->connection->select('dcp_image_data', 'd')
      ->fields('d', ['media', 'fullwidth'])
      ->condition('block_id', $block_id)
      ->execute()
      ->fetchAssoc();

    $media = $query['media'] ?? '';

    $media_entity = \Drupal::entityTypeManager()->getStorage('media')->load($media);
    $media_entity = \Drupal::entityTypeManager()->getViewBuilder('media')->view($media_entity);

    return [
      '#type' => 'component',
      '#component' => 'dcp_image:image',
      '#props' => [
        'media' => $media_entity,
        'fullwidth' => $query['fullwidth'] ?? FALSE,
      ],
    ];
  }
}
