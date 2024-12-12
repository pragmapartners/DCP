<?php

declare(strict_types=1);

namespace Drupal\tabs\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Component\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\filter\FilterFormatInterface;

/**
 * Provides a tabs block.
 */
#[Block(
  id: 'dcp_tabs',
  admin_label: new TranslatableMarkup('Tabs'),
  category: new TranslatableMarkup('Pragma'),
)]
final class TabsBlock extends BlockBase implements ContainerFactoryPluginInterface
{
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $connection;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  private UuidInterface $uuidService;


  /**
   * Constructs the plugin instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $connection,
    UuidInterface $uuid_service,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
    $this->uuidService = $uuid_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self
  {
    return new self(
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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array
  {
    $form = parent::blockForm($form, $form_state);
    $form['#tree'] = TRUE;

    // Ensure 'unique_id' is set
    if (empty($this->configuration['unique_id'])) {
      $this->configuration['unique_id'] = $this->uuidService->generate();
    }
    $block_id = $this->configuration['unique_id'];

    // Load existing tabs from the database.
    $query = $this->connection->select('tabs_data', 't')
      ->fields('t', ['id', 'title', 'content', 'format', 'icon'])
      ->condition('block_id', $block_id)
      ->execute();

    $tabs = $query->fetchAllAssoc('id');

    $form['tabs'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Tabs'),
      '#prefix' => '<div id="tabs-wrapper">',
      '#suffix' => '</div>',
    ];

    $i = 0;
    foreach ($tabs as $id => $tab) {
      $form['tabs'][$i] = [
        '#type' => 'details',
        '#title' => $this->t($tab->title ?? 'Tab @num', ['@num' => $i + 1]),
        '#open' => FALSE,
      ];
      $form['tabs'][$i]['id'] = [
        '#type' => 'hidden',
        '#value' => $id,
      ];
      $form['tabs'][$i]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $tab->title,
      ];
      $form['tabs'][$i]['icon'] = [
        '#type' => 'icon_autocomplete',
        '#title' => $this->t('Icon'),
        '#default_value' => $tab->icon,
        '#allowed_icon_pack' => [
          'heroicons',
        ],
        '#show_settings' => TRUE,
      ];
      $form['tabs'][$i]['content'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Content'),
        '#default_value' => $tab->content,
        // @phpstan-ignore function.notFound
        '#format' => $tab->format ?? filter_default_format(),
      ];
      $i++;
    }

    // Add empty forms for new tabs if any.
    $additional_tabs = $form_state->get('additional_tabs') ?? 0;
    for ($j = $i; $j < $i + $additional_tabs; $j++) {
      $form['tabs'][$j] = [
        '#type' => 'details',
        '#title' => $this->t('Tab @num', ['@num' => $j + 1]),
        '#open' => TRUE,
      ];
      $form['tabs'][$j]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
      ];
      $form['tabs'][$j]['icon'] = [
        '#type' => 'icon_autocomplete',
        '#title' => $this->t('Icon'),
        '#allowed_icon_pack' => [
          'heroicons',
        ],
        '#show_settings' => TRUE,
      ];
      $form['tabs'][$j]['content'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Content'),
        // @phpstan-ignore function.notFound
        '#format' => filter_default_format(),
      ];
    }

    $form['add_tab'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Tab'),
      '#submit' => [[$this, 'addTab']],
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'tabs-wrapper',
      ],
    ];

    return $form;
  }

  public function addTab(array &$form, FormStateInterface $form_state)
  {
    $additional_tabs = $form_state->get('additional_tabs') ?? 0;
    $form_state->set('additional_tabs', $additional_tabs + 1);
    $form_state->setRebuild();
  }

  public static function ajaxCallback(array &$form, FormStateInterface $form_state)
  {
    return $form['settings']['tabs'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void
  {
    // Ensure 'unique_id' is set
    if (empty($this->configuration['unique_id'])) {
      $this->configuration['unique_id'] = $this->uuidService->generate();
    }
    $block_id = $this->configuration['unique_id'];

    $tabs = $form_state->getValue('tabs');

    // Fetch existing tab IDs for this block.
    $existing_tab_ids = \Drupal\Core\Database\Database::getConnection()->select('tabs_data', 't')
      ->fields('t', ['id'])
      ->condition('block_id', $block_id)
      ->execute()
      ->fetchCol();

    $submitted_tab_ids = [];

    foreach ($tabs as $tab) {
      if (isset($tab['id'])) {
        $submitted_tab_ids[] = $tab['id'];

        // Update existing tab.
        \Drupal\Core\Database\Database::getConnection()->update('tabs_data')
          ->fields([
            'title' => $tab['title'],
            'content' => $tab['content']['value'],
            'format' => $tab['content']['format'],
            'icon' => $tab['icon']['icon']->getId(),
          ])
          ->condition('id', $tab['id'])
          ->execute();
      } else {
        // Insert new tab.
        $insert_id = \Drupal\Core\Database\Database::getConnection()->insert('tabs_data')
          ->fields([
            'block_id' => $block_id,
            'title' => $tab['title'],
            'content' => $tab['content']['value'],
            'format' => $tab['content']['format'],
            'icon' => $tab['icon']['icon']->getId(),
          ])
          ->execute();
        $submitted_tab_ids[] = $insert_id;
      }
    }

    // Delete tabs that were removed.
    $tabs_to_delete = array_diff($existing_tab_ids, $submitted_tab_ids);
    if (!empty($tabs_to_delete)) {
      $this->connection->delete('tabs_data')
        ->condition('id', $tabs_to_delete, 'IN')
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array
  {
    // Ensure 'unique_id' is set
    if (empty($this->configuration['unique_id'])) {
      $this->configuration['unique_id'] = $this->uuidService->generate();
    }
    $block_id = $this->configuration['unique_id'];

    // Load tabs from the database.
    $query = $this->connection->select('tabs_data', 't')
      ->fields('t', ['title', 'content', 'format', 'icon'])
      ->condition('block_id', $block_id)
      ->execute();

    $tabs = [];
    foreach ($query as $record) {
      // $icon_details = $this->iconFinder->getIconDetails($record->icon);
      // @todo: Implement IconFinder service to get icon details.
      $pluginManagerIconPack = \Drupal::service('plugin.manager.icon_pack');

      $tabs[] = [
        'title' => $record->title,
        'content' => [
          '#type' => 'processed_text',
          '#text' => $record->content,
          '#format' => $record->format,
        ],
        'icon' => [
          '#type' => 'icon',
          '#pack_id' => $pluginManagerIconPack->getIcon($record->icon)->getPackId(),
          '#icon_id' => $pluginManagerIconPack->getIcon($record->icon)->getIconId(),
          '#settings' => [
            'size' => 64,
          ],
        ],
      ];
    }
    $num_tabs = count($tabs);

    return [
      '#type' => 'component',
      '#component' => 'tabs:tabs',
      '#props' => [
        'items' => $tabs,
        'tab_count' => $num_tabs,
        'attributes' => [
          'class' => 'tabs',
        ],
      ],
    ];
  }
}
