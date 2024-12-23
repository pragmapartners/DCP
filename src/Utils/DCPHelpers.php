<?php

namespace Drupal\dcp\Utils;

class DCPHelpers
{
  public static function buildSdcDataProps(
    array $block_config,
    array $component_keys = [],
    array $boolean_keys = []
  ): array {
    // Keys we always want to ignore:
    $base_config_keys = ['id', 'label', 'label_display', 'provider', 'context_mapping'];
    $keys = array_merge($base_config_keys, $component_keys);

    $props = [];
    foreach ($block_config as $key => $value) {
      if (!in_array($key, $keys, TRUE)) {
        // If this key should be a boolean, cast accordingly.
        if (in_array($key, $boolean_keys, TRUE)) {
          // Convert '0' or 0 => false, '1' or 1 => true.
          $value = ($value === '1' || $value === 1) ? TRUE : FALSE;
        }

        $props[$key] = $value;
      }
    }

    return $props;
  }
}
