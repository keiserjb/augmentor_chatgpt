<?php

namespace Drupal\augmentor_chatgpt;

use Drupal\augmentor\AugmentorBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Orhanerday\OpenAi\OpenAi as OrhanerdayOpenAi;
use OpenAI\Client;

/**
 * ChatGPT Base augmentor plugin implementation.
 */
/**
 * Provides a base class for ChatGPT augmentors.
 *
 * @see \Drupal\augmentor\Annotation\Augmentor
 * @see \Drupal\augmentor\AugmentorInterface
 * @see \Drupal\augmentor\AugmentorManager
 * @see \Drupal\augmentor\AugmentorBase
 * @see plugin_api
 */
class ChatGptBase extends AugmentorBase implements ContainerFactoryPluginInterface {

  /**
   * The OpenAI API client.
   *
   * @var \Orhanerday\OpenAi\OpenAi|\OpenAI\Client|null
   */
  private $client = null;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'sdk' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['sdk'] = [
      '#type' => 'select',
      '#title' => $this->t('SDK to use'),
      '#options' => [
        'orhanerday' => 'orhanerday/open-ai',
        'openai_php' => 'openai-php/client',
      ],
      '#description' => $this->t('Choose the OpenAI PHP SDK to use:
        <a href=":link">orhanerday/open-ai</a> or <a href=":link2">openai-php/client</a>', [
          ':link' => 'https://github.com/orhanerday/open-ai',
          ':link2' => 'https://github.com/openai-php/client',
        ]),
      '#default_value' => $this->configuration['sdk'] ?? 'orhanerday',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['sdk'] = $form_state->getValue('sdk');
  }

  /**
   * Gets the OpenAI SDK API client to use.
   *
   * @return mixed
   *   The OpenAI SDK.
   */
  public function getClient(): mixed {
    if ($this->getSdk() === 'orhanerday') {
      return $this->getOrhanerdayClient();
    }

    return $this->getOpenAiClient();
  }

  /**
   * Gets an OpenAI client using the "orhanerday/open-ai" SDK.
   *
   * @return \Orhanerday\OpenAi\OpenAi
   *   The "orhanerday/open-ai" OpenAI SDK.
   */
  private function getOrhanerdayClient(): OrhanerdayOpenAi {

    // Only if not initialized yet.
    if (empty($this->client)) {
      $api_key = $this->getKeyValue();

      // Initialize API client.
      $this->client = new OrhanerdayOpenAi($api_key);
    }
    return $this->client;
  }

  /**
   * Gets an OpenAI client using the "openai-php/client" SDK.
   *
   * @return \OpenAI\Client
   *   The "openai-php/client" OpenAI SDK.
   */
  private function getOpenAiClient(): Client {

    // Only if not initialized yet.
    if (empty($this->client)) {
      $api_key = $this->getKeyValue();

      // Initialize API client.
      $this->client = \OpenAI::client($api_key);
    }
    return $this->client;
  }

  /**
   * Gets the name of the selected SDK to use.
   *
   * @return string
   *   The name of the SDK to use.
   */
  protected function getSdk() {
    $sdk_to_use = $this->configuration['sdk'] ?? 'orhanerday';

    return $sdk_to_use;
  }


  /**
   * Fetches models using the orhanerday SDK.
   *
   * @return array
   */
  protected function listModelsOrhanerday(): array {
    $openAi = $this->getOrhanerdayClient();
    $result = $openAi->listModels();

    // Assuming $result is a JSON string; decode it first.
    $decodedResult = json_decode($result, true);

    // Check if decoding was successful and the expected data is in an array format.
    if (is_array($decodedResult) && isset($decodedResult['data'])) {
      $models = [];
      foreach ($decodedResult['data'] as $model) {
        if (isset($model['id'])) {
          // Assuming each model has an 'id' you want to use as both key and value.
          $models[$model['id']] = $model['id'];
        }
      }
      return $models;
    }

    // Log an error or handle the case where $result isn't in the expected format.
    \Drupal::logger('augmentor_chatgpt')->error('Unexpected API response format.');
    return [];
  }


  /**
   * Fetches models using the openai-php SDK.
   *
   * @return array
   */
  protected function listModelsOpenAiPhp(): array {
    $client = $this->getOpenAiClient(); // Ensures the openai-php client is initialized.
    $response = $client->models()->list();
    // Transform $response to an associative array for form options, if needed.
    $models = [];
    foreach ($response['data'] as $model) {
      $models[$model['id']] = $model['id']; // Adjust according to actual response structure.
    }
    return $models;
  }
}


