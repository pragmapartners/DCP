<?php

declare(strict_types=1);

// helps tell where we are
namespace Drupal\crumby\Plugin\Block;

// import classes
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;

/**
 * Provides a crumby block.
 *
 * @Block(
 *   id = "crumby_crumby",
 *   admin_label = @Translation("Crumby"),
 *   category = @Translation("Pragma"),
 * )
 */


final class CrumbyBlock extends BlockBase
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
      'separator' => '/',
      'menu' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array
  {
    // create an entity reference field for menus
    $form['menu'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'menu',
      '#title' => $this->t('Menu'),
      '#default_value' => \Drupal::entityTypeManager()->getStorage('menu')->load($this->configuration['menu']) ?? NULL,
      '#required' => TRUE,
    ];
    $form['separator'] = [
      '#title' => $this->t('Separator'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['separator'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void
  {
    $this->configuration['separator'] = $form_state->getValue('separator');
    $this->configuration['menu'] = $form_state->getValue('menu');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array
  {
    $breadcrumb = $this->build_breadcrumb();
    return [
      '#type' => 'component',
      '#component' => 'crumby:breadcrumb',
      '#props' => [
        'breadcrumb' => $breadcrumb,
        'separator' => $this->configuration['separator'],
      ],
    ];
  }

  public function build_breadcrumb()
  {
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $node = \Drupal::routeMatch()->getParameter('node');
    $breadcrumb = [];

    // Always add 'Home' as the first breadcrumb item.
    $breadcrumb[] = [
      'text' => 'Home',
      'url' => Url::fromRoute('<front>'),
    ];

    $menu = \Drupal::entityTypeManager()->getStorage('menu')->load($this->configuration['menu']);
    $menu_name = $menu->id();
    $trailIds = \Drupal::service('menu.active_trail')->getActiveTrailIds($menu_name);

    $trailItems = [];
    foreach (array_reverse($trailIds) as $key => $value) {
      if ($value) {
        $trailItems[] = [
          'text' => $menu_link_manager->createInstance($value)->getTitle(),
          'url' => $key === array_key_last(array_reverse($trailIds)) ? null : $menu_link_manager->createInstance($value)->getUrlObject(),
        ];
      }
    }

    $maxDisplayItems = 4;

    if (count($trailItems) > $maxDisplayItems) {
      $numItemsToShow = (int)round(($maxDisplayItems - 3) / 2, 0);
      $numItemsToShow = max($numItemsToShow, 1);
      $breadcrumb = array_merge($breadcrumb, array_slice($trailItems, 0, $numItemsToShow));
      $breadcrumb[] = ['text' => '...', 'url' => null];
      $breadcrumb = array_merge($breadcrumb, array_slice($trailItems, -$numItemsToShow));
    } else {
      $breadcrumb = array_merge($breadcrumb, $trailItems);
    }

    // Ensure the current page (if not already added) is the last item.
    if (!empty($trailItems) && !in_array(end($trailItems), $breadcrumb)) {
      $breadcrumb[] = end($trailItems);
    }

    return $breadcrumb;
  }
}
