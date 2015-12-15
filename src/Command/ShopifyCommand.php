<?php

namespace Drupal\shopify\Command;

use Drupal\Component\Serialization\Json;
use Drupal\Console\Command\ContainerAwareCommand;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShopifyCommand extends ContainerAwareCommand {

  protected function configure() {
    $this->setName('shopify:api')
      ->setDescription($this->trans('View/Create/Update/Delete a Shopify resource.'))
      ->addArgument('method', InputArgument::REQUIRED, $this->trans('Either GET, POST, PUT, or DELETE.'))
      ->addArgument('resource', InputArgument::REQUIRED, $this->trans('Resource, such as "product", "order", etc.'))
      ->addArgument('opts', InputArgument::OPTIONAL, $this->trans('Options to pass to the API request.'));
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $client = shopify_api_client();
    $opts = $input->getArgument('opts');
    parse_str($opts, $opts);
    $response = $client->request($input->getArgument('method'), $input->getArgument('resource'), (array) $opts);
    if ($response instanceof Response) {
      $output->write($response->getBody()->getContents(), TRUE);
    }
  }

}