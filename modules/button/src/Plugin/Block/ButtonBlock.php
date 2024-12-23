<?php

declare(strict_types=1);

namespace Drupal\button\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

#[Block(
  id: 'dcp_button',
  admin_label: new TranslatableMarkup('Button'),
  category: new TranslatableMarkup('Pragma'),
)]
final class ButtonBlock extends BlockBase
{

  /**
   * Constructs a new ImageBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array
  {
    return [
      'link' => NULL,
      'link_title' => NULL,
      'outline' => FALSE,
      'size' => 'md',
      'wide' => FALSE,
      'disabled' => FALSE,
      'square' => FALSE,
      'circle' => FALSE
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array
  {

    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['link'] = [
      '#title' => $this->t('Link'),
      '#description' => $this->t('Start typing to see a list of results. Click to select.'),
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => 'default',
      ],
      '#element_validate' => [
        [
          'Drupal\link\Plugin\Field\FieldWidget\LinkWidget',
          'validateUriElement',
        ],
      ],
      '#type' => 'linkit',
      '#default_value' => $config['link'] ?? '',
      '#required' => TRUE,
    ];
    $form['link_title'] = [
      '#title' => $this->t('Link Title'),
      '#type' => 'textfield',
      '#default_value' => $config['link_title'] ?? '',
      '#required' => TRUE,
    ];
    $form['config'] = [
      '#type' => 'details',
      '#title' => $this->t('Button Configuration'),
      '#open' => FALSE,
    ];
    $form['config']['outline'] = [
      '#title' => $this->t('Outline'),
      '#type' => 'checkbox',
      '#default_value' => $config['outline'] ?? FALSE,
    ];
    $form['config']['size'] = [
      '#title' => $this->t('Size'),
      '#type' => 'select',
      '#options' => [
        'sm' => $this->t('Small'),
        'md' => $this->t('Medium'),
        'lg' => $this->t('Large'),
      ],
      '#default_value' => $config['size'] ?? 'md',
    ];
    $form['config']['wide'] = [
      '#title' => $this->t('Wide'),
      '#type' => 'checkbox',
      '#default_value' => $config['wide'] ?? FALSE,
    ];
    $form['config']['disabled'] = [
      '#title' => $this->t('Disabled'),
      '#type' => 'checkbox',
      '#default_value' => $config['disabled'] ?? FALSE,
    ];
    $form['config']['square'] = [
      '#title' => $this->t('Square'),
      '#type' => 'checkbox',
      '#default_value' => $config['square'] ?? FALSE,
    ];
    $form['config']['circle'] = [
      '#title' => $this->t('Circle'),
      '#type' => 'checkbox',
      '#default_value' => $config['circle'] ?? FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void
  {
    $link = $form_state->getValue('link');
    $link_title = $form_state->getValue('link_title');
    $outline = !empty($form_state->getValue('outline')) ? $form_state->getValue('outline') : 0;
    $size = !empty($form_state->getValue('size')) ? $form_state->getValue('size') : 'md';
    $wide = !empty($form_state->getValue('wide')) ? $form_state->getValue('wide') : 0;
    $disabled = !empty($form_state->getValue('disabled')) ? $form_state->getValue('disabled') : 0;
    $square = !empty($form_state->getValue('square')) ? $form_state->getValue('square') : 0;
    $circle = !empty($form_state->getValue('circle')) ? $form_state->getValue('circle') : 0;

    $this->setConfigurationValue('link', $link);
    $this->setConfigurationValue('link_title', $link_title);
    $this->setConfigurationValue('outline', $outline);
    $this->setConfigurationValue('size', $size);
    $this->setConfigurationValue('wide', $wide);
    $this->setConfigurationValue('disabled', $disabled);
    $this->setConfigurationValue('square', $square);
    $this->setConfigurationValue('circle', $circle);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array
  {
    $config = $this->getConfiguration();

    $link = $config['link'] ?? '';
    $link_title = $config['link_title'] ?? '';
    $outline = $config['outline'] ?? FALSE;
    $size = $config['size'] ?? 'md';
    $wide = $config['wide'] ?? FALSE;
    $disabled = $config['disabled'] ?? FALSE;
    $square = $config['square'] ?? FALSE;
    $circle = $config['circle'] ?? FALSE;

    $link = \Drupal\Core\Link::fromTextAndUrl($link_title, \Drupal\Core\Url::fromUri($link));
    $link = $link->toRenderable();

    $link_attributes = new Attribute();

    foreach (_build_sdc_data_props($config, ['link', 'link_title'], ['outline', 'wide', 'disabled', 'square', 'circle']) as $key => $value) {
      $link_attributes->setAttribute($key, $value);
    }

    return [
      '#type' => 'component',
      '#component' => 'button:button',
      '#props' => [
        'link' => $link,
        'outline' => $outline ? TRUE : FALSE,
        'size' => $size,
        'wide' => $wide ? TRUE : FALSE,
        'disabled' => $disabled ? TRUE : FALSE,
        'square' => $square ? TRUE : FALSE,
        'circle' => $circle ? TRUE : FALSE,
        'attributes' => $link_attributes,
      ],
    ];
  }
}
