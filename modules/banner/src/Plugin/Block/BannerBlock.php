<?php

namespace Drupal\banner\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Component\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[Block(
  id: 'banner',
  admin_label: new TranslatableMarkup('Banner'),
  category: new TranslatableMarkup('Pragma'),
)]
final class BannerBlock extends BlockBase implements ContainerFactoryPluginInterface{

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The UUID service.
   *
   * @var \Drupal\Core\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Constructs a BannerBlock object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, UuidInterface $uuid_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
    $this->uuidService = $uuid_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create($container, array $configuration, $plugin_id, $plugin_definition) {
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
  public function defaultConfiguration() {
    return [
      'unique_id' => '',
      'banner_color' => 'default',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Read the stored unique_id (may be empty if new block).
    $block_id = $this->configuration['unique_id'];

    // Fetch banner data if we have a block_id set.
    $banner_data = [];
    if (!empty($block_id)) {
      $banner_data = $this->connection->select('banner_data', 'b')
        ->fields('b', [
          'banner_image',
          'banner_heading',
          'banner_body',
          'body_format',
          'banner_color',
          'banner_color_picker',
        ])
        ->condition('block_id', $block_id)
        ->execute()
        ->fetchAssoc() ?? [];
    }

    // Provide defaults if nothing in DB (or block not saved yet).
    $banner_data += [
      'banner_image' => '',
      'banner_heading' => '',
      'banner_body' => '',
      'body_format' => 'basic_html',
      'banner_color' => 'default',
      'banner_color_picker' => '',
    ];

    // Banner fields.
    $form['banner_image'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['image'],
      '#title' => $this->t('Banner Image'),
      '#default_value' => $banner_data['banner_image'] ?? '',
    ];

    $form['banner_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Banner Heading'),
      '#default_value' => $banner_data['banner_heading'] ?? '',
    ];

    $form['banner_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Banner Body'),
      '#default_value' => $banner_data['banner_body'] ?? '',
      '#format' => $banner_data['body_format'] ?? 'basic_html',
    ];

    // Buttons.
    // Fetch buttons data if we have a block_id set.
    $buttons = [];
    if (!empty($block_id)) {
      $buttons = $this->connection->select('banner_buttons', 'bb')
        ->fields('bb', ['id', 'title', 'url'])
        ->condition('block_id', $block_id)
        ->execute()
        ->fetchAllAssoc('id');
    }

    // Make the buttons wrapper.
    $form['buttons'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Links'),
      '#prefix' => '<div id="buttons-wrapper">',
      '#suffix' => '</div>',
    ];

    // Add fetched buttons to the form.
    $i = 0;
    foreach ($buttons as $id => $button) {
      $form['buttons'][$i] = [
        '#type' => 'details',
        '#title' => $this->t($button->title ?? 'Link @num', ['@num' => $i + 1]),
        '#open' => FALSE,
      ];
      $form['buttons'][$i]['id'] = [
        '#type' => 'hidden',
        '#value' => (int) $id,
      ];
      $form['buttons'][$i]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $button->title ?? '',
      ];

      $form['buttons'][$i]['url'] = [
        '#type' => 'linkit',
        '#title' => $this->t('Link'),
        '#description' => $this->t('Start typing to see a list of results. Click to select.'),
        '#autocomplete_route_name' => 'linkit.autocomplete',
        '#autocomplete_route_parameters' => [
          'linkit_profile_id' => 'default',
        ],
        '#default_value' => $button->url ?? '',
      ];

      $form['buttons'][$i]['remove_button'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove_existing_button_' . $id,
        '#submit' => [[$this, 'removeExistingButtonAjax']],
        '#ajax' => [
          'callback' => [$this, 'ajaxCallback'],
          'wrapper' => 'buttons-wrapper',
        ],
        // Custom attribute to know which button ID is being removed:
        '#attributes' => [
          'data-button-id' => $id,
        ],
      ];

      $i++;
    }

    // Add empty forms for new buttons if any.
    $additional_buttons = $form_state->get('additional_buttons') ?? 0;
    for ($j = $i; $j < $i + $additional_buttons; $j++) {
      $form['buttons'][$j] = [
        '#type' => 'details',
        '#title' => $this->t('Link @num', ['@num' => $j + 1]),
        '#open' => TRUE,
      ];

      $form['buttons'][$j]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
      ];

      $form['buttons'][$j]['url'] = [
        '#type' => 'linkit',
        '#title' => $this->t('Link'),
        '#description' => $this->t('Start typing to see a list of results. Click to select.'),
        '#autocomplete_route_name' => 'linkit.autocomplete',
        '#autocomplete_route_parameters' => [
          'linkit_profile_id' => 'default',
        ],
        '#default_value' => "" ,
      ];
    }

    // Ajax Add button.
    $form['add_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Link'),
      '#submit' => [[$this, 'addButton']],
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'buttons-wrapper',
      ],
    ];

    // Banner color.
    $form['banner_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Banner Color'),
      '#options' => [
        'default' => $this->t('Default'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => $banner_data['banner_color'] ?? 'default',
    ];

    $form['banner_color_picker'] = [
      '#type' => 'color',
      '#title' => $this->t('Banner Custom Color'),
      '#default_value' => $banner_data['banner_color_picker'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="settings[banner_color]"]' => ['value' => 'custom'],
        ],
      ],
      '#attributes' => [
        'class' => ['color-picker'],
        'style' => 'height: 50px;',
      ],
    ];

    return $form;
  }

  /**
   * Ajax callbacks
   */
  public function addButton(array &$form, FormStateInterface $form_state) {
    $additional_buttons = $form_state->get('additional_buttons') ?? 0;
    $form_state->set('additional_buttons', $additional_buttons + 1);
    $form_state->setRebuild();
  }

  /**
  * AJAX callback to remove an existing button from DB.
  */
  public function removeExistingButtonAjax(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    //button ID in an HTML attribute in buildConfigurationForm().
    if (!empty($triggering_element['#attributes']['data-button-id'])) {
      $button_id = $triggering_element['#attributes']['data-button-id'];
      $block_id = $this->configuration['unique_id'];

      $this->connection->delete('banner_buttons')
        ->condition('id', $button_id)
        ->condition('block_id', $block_id)
        ->execute();
    }

    $form_state->setRebuild(TRUE);
  }

  public static function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['settings']['buttons'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);

    // Generate the 'unique_id' once if not set.
    if (empty($this->configuration['unique_id'])) {
      $this->configuration['unique_id'] = $this->uuidService->generate();
    }
    $block_id = $this->configuration['unique_id'];

    // Save the banner data.
    $banner_body = $form_state->getValue('banner_body');
    $banner_data = [
      'block_id' => $block_id,
      'banner_image' => $form_state->getValue('banner_image'),
      'banner_heading' => $form_state->getValue('banner_heading'),
      'banner_body' => $banner_body['value'],
      'body_format' => $banner_body['format'],
      'banner_color' => $form_state->getValue('banner_color'),
      'banner_color_picker' => $form_state->getValue('banner_color_picker'),
    ];

    // Check if a record exists already.
    $existing_banner = $this->connection->select('banner_data', 'b')
      ->fields('b', ['id'])
      ->condition('block_id', $block_id)
      ->execute()
      ->fetchField();

    if ($existing_banner) {
      // Update existing.
      $this->connection->update('banner_data')
        ->fields($banner_data)
        ->condition('block_id', $block_id)
        ->execute();
    }
    else {
      // Insert new.
      $this->connection->insert('banner_data')
        ->fields($banner_data)
        ->execute();
    }

    // Handle buttons (insert/update).
    $buttons = $form_state->getValue('buttons') ?? [];
    $existing_button_ids = $this->connection->select('banner_buttons', 'b')
      ->fields('b', ['id'])
      ->condition('block_id', $block_id)
      ->execute()
      ->fetchCol();

    $submitted_button_ids = [];

    foreach ($buttons as $button) {
      if (!empty($button['id'])) {
        $submitted_button_ids[] = $button['id'];
        $this->connection->update('banner_buttons')
          ->fields([
            'title' => $button['title'],
            'url' => $button['url'],
          ])
          ->condition('id', $button['id'])
          ->execute();
      }
      else {
        $insert_id = $this->connection->insert('banner_buttons')
          ->fields([
            'block_id' => $block_id,
            'title' => $button['title'],
            'url' => $button['url'],
          ])
          ->execute();
        $submitted_button_ids[] = $insert_id;
      }
    }

    $buttons_to_delete = array_diff($existing_button_ids, $submitted_button_ids);
    if (!empty($buttons_to_delete)) {
      $this->connection->delete('banner_buttons')
        ->condition('id', $buttons_to_delete, 'IN')
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // Get the stable block_id.
    $block_id = $this->configuration['unique_id'];

    // Fetch banner data from DB.
    $banner_data = [];
    if (!empty($block_id)) {
      $banner_data = \Drupal::database()->select('banner_data', 'b')
        ->fields('b', [
          'banner_image',
          'banner_heading',
          'banner_body',
          'body_format',
          'banner_color',
          'banner_color_picker',
        ])
        ->condition('block_id', $block_id)
        ->execute()
        ->fetchAssoc();
    }

    // Provide defaults if no data is found.
    if (!$banner_data) {
      $banner_data = [
        'banner_image' => '',
        'banner_heading' => '',
        'banner_body' => '',
        'body_format' => 'basic_html',
        'banner_color' => 'default',
        'banner_color_picker' => '',
      ];
    }

    // Fetch button data from DB.
    $buttons_query = [];
    if (!empty($block_id)) {
      $buttons_query = \Drupal::database()->select('banner_buttons', 'bb')
        ->fields('bb', ['id', 'title', 'url'])
        ->condition('block_id', $block_id)
        ->execute()
        ->fetchAll();
    }

    $buttons = [];
    foreach ($buttons_query as $record) {
      $buttons[] = [
        'id' => (int) $record->id,
        'title' => $record->title,
        'url' => $record->url,
      ];
    }

    // Build media object
    $banner_image = NULL;
    if (!empty($banner_data['banner_image'])) {
      $media = \Drupal::entityTypeManager()
        ->getStorage('media')
        ->load($banner_data['banner_image']);
      if ($media) {
        $banner_image = \Drupal::service('dhs.helper_functions')
          ->buildMediaObject($media);
      }
    }


    // get current path
    $current_path = \Drupal::service('path.current')->getPath();

    // Return the render array for the Banner component.
    return [
      '#type' => 'component',
      '#component' => 'banner:banner',
      '#props' => [
        'banner_image' => $banner_image,
        'banner_heading' => $banner_data['banner_heading'],
        'banner_body' => $banner_data['banner_body'],
        'body_format' => $banner_data['body_format'],
        'banner_color' => $banner_data['banner_color'],
        'banner_color_picker' => $banner_data['banner_color_picker'],
        'buttons' => $buttons,
        'menu' => $current_path,
        'attributes' => [
          'class' => ['banner'],
        ],
      ],
    ];
  }

}
